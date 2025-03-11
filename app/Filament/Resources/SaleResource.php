<?php
namespace App\Filament\Resources;
use App\Filament\Resources\SaleResource\Pages;
use App\Models\Sale;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Product;
use Filament\Forms\Set;
use Filament\Forms\Get;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make()
                ->schema([
                    self::saleDetailsSection(),
                    self::saleItemsSection(),
                    self::notesSection(),
                ])
                ->columnSpan(['lg' => 3])
        ])
            ->columns(4);
    }

    protected static function saleDetailsSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Sale Details')
            ->columns(2)
            ->schema([
                Forms\Components\Select::make('customer_id')
                    ->relationship('customer', 'name')
                    ->required()
                    ->searchable()
                    ->createOptionForm(self::customerForm()),
                Forms\Components\TextInput::make('total_amount')
                    ->numeric()
                    ->disabled()
                    ->dehydrated()
                    ->default(0)
                    ->label('Total Amount')
                    ->prefix('FCFA')
                    ->live(),
                Forms\Components\ToggleButtons::make('payment_status')
                    ->options([
                        'pending' => 'Pending',
                        'partial' => 'Partial',
                        'paid' => 'Paid',
                    ])
                    ->required()
                    ->inline(),
            ]);
    }

    protected static function customerForm(): array
    {
        return [
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('email')
                ->label('Email address')
                ->email()
                ->maxLength(255)
                ->unique(),
            Forms\Components\TextInput::make('phone')
                ->maxLength(255),
        ];
    }

    protected static function saleItemsSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Sale Items')
            ->schema([
                Forms\Components\Repeater::make('items')
                    ->relationship()
                    ->schema(self::saleItemSchema())
                    ->itemLabel(function (array $state): ?string {
                        return !empty($state['product_id']) ? Product::find($state['product_id'])->name . " (×" . ($state['quantity'] ?? 1) . ")" : null;
                    })
                    ->columns(3)
                    ->defaultItems(1)
                    ->reorderable(false)
                    ->live()
                    ->afterStateUpdated(fn(Get $get, Set $set) => self::calculateTotalAmount($get, $set))
                    ->addActionLabel('Add Product')
                    ->deleteAction(fn(Action $action) => $action->after(fn(Get $get, Set $set) => self::calculateTotalAmount($get, $set))),
            ]);
    }

    protected static function saleItemSchema(): array
    {
        return [
            Forms\Components\Select::make('product_id')
                ->label('Product')
                ->relationship('product', 'name')
                ->required()
                ->searchable()
                ->live()
                ->afterStateUpdated(self::handleProductUpdate()),
            Forms\Components\TextInput::make('quantity')
                ->label('Quantity')
                ->numeric()
                ->default(1)
                ->minValue(1)
                ->required()
                ->live()
                ->afterStateUpdated(self::handleQuantityUpdate())
                ->validationAttribute('quantity')
                ->rules([
                    function (Get $get): \Closure {
                        return function (string $attribute, $value, \Closure $fail) use ($get) {
                            $productId = $get('product_id');
                            if (!$productId) return;

                            $product = Product::find($productId);
                            if (!$product) return;

                            if ($value > $product->quantity) {
                                $fail("Stock insuffisant. Seulement {$product->quantity} unités disponibles.");
                            }
                        };
                    }
                ]),
            Forms\Components\TextInput::make('unit_price')
                ->label('Unit Price')
                ->numeric()
                ->prefix('FCFA')
                ->required()
                ->live()
                ->afterStateUpdated(self::handleUnitPriceUpdate()),
            Forms\Components\TextInput::make('discount_value')
                ->label('Discount Amount')
                ->numeric()
                ->default(0)
                ->live()
                ->prefix('FCFA')
                ->afterStateUpdated(self::handleDiscountUpdate()),
            Forms\Components\TextInput::make('final_price')
                ->label('Final Price')
                ->numeric()
                ->prefix('FCFA')
                ->disabled()
                ->dehydrated(),
        ];
    }

    protected static function handleProductUpdate(): callable
    {
        return function (Get $get, Set $set, ?string $state) {
            if ($state) {
                $product = Product::find($state);
                if ($product) {
                    $set('unit_price', (float)$product->selling_price);
                    $quantity = (int)($get('quantity') ?: 1);
                    $discountValue = (float)($get('discount_value') ?: 0);
                    self::calculateFinalPrice($set, (float)$product->selling_price, $quantity, $discountValue);
                    self::calculateTotalAmount($get, $set);
                }
            }
        };
    }

    protected static function handleQuantityUpdate(): callable
    {
        return function (Get $get, Set $set, $state) {
            $unitPrice = (float)$get('unit_price');
            $discountValue = (float)$get('discount_value');
            if ($unitPrice) {
                self::calculateFinalPrice($set, $unitPrice, (int)$state, $discountValue);
                self::calculateTotalAmount($get, $set);
            }
        };
    }

    protected static function handleUnitPriceUpdate(): callable
    {
        return function (Get $get, Set $set, $state) {
            $quantity = (int)$get('quantity');
            $discountValue = (float)$get('discount_value');
            self::calculateFinalPrice($set, (float)$state, $quantity, $discountValue);
            self::calculateTotalAmount($get, $set);
        };
    }

    protected static function handleDiscountUpdate(): callable
    {
        return function (Get $get, Set $set, $state) {
            $unitPrice = (float)$get('unit_price');
            $quantity = (int)$get('quantity');
            if ($unitPrice) {
                self::calculateFinalPrice($set, $unitPrice, $quantity, (float)$state);
                self::calculateTotalAmount($get, $set);
            }
        };
    }

    protected static function notesSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Notes')
            ->schema([
                Forms\Components\MarkdownEditor::make('notes')
                    ->columnSpan('full'),
            ])
            ->columnSpan('full');
    }

    private static function calculateFinalPrice(Set $set, float $unitPrice, int $quantity, float $discountValue): void
    {
        $subtotal = $unitPrice * $quantity;
        $finalPrice = max(0, $subtotal - $discountValue);
        $set('final_price', $finalPrice); // Pas d'arrondi pour le FCFA
    }

    private static function calculateTotalAmount(Get $get, Set $set): void
    {
        $items = $get('items');
        $totalAmount = 0;

        if (is_array($items)) {
            foreach ($items as $itemKey => $item) {
                if (isset($item['unit_price']) && isset($item['quantity'])) {
                    $unitPrice = (float)$item['unit_price'];
                    $quantity = (int)$item['quantity'];
                    $discountValue = (float)($item['discount_value'] ?? 0);

                    $subtotal = $unitPrice * $quantity;
                    $finalPrice = max(0, $subtotal - $discountValue);

                    // Mise à jour du prix final dans l'élément
                    $set("items.{$itemKey}.final_price", $finalPrice);

                    $totalAmount += $finalPrice;
                } elseif (isset($item['final_price'])) {
                    $totalAmount += (float)$item['final_price'];
                }
            }
        }

        $set('total_amount', $totalAmount);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer.name')->sortable(),
                Tables\Columns\TextColumn::make('total_amount')->sortable(),
                Tables\Columns\TextColumn::make('discount_amount')->sortable(),
                Tables\Columns\TextColumn::make('payment_status')->searchable(),
            ])
            ->filters([
                // Additional filters can be added here.
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSales::route('/'),
            'create' => Pages\CreateSale::route('/create'),
            'edit' => Pages\EditSale::route('/{record}/edit'),
        ];
    }
}
