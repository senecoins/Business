<?php
namespace App\Filament\Resources;
use App\Filament\Imports\ProductImporter;
use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use DesignTheBox\BarcodeField\Forms\Components\BarcodeInput;
use Faker\Provider\Text;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\ImportAction;
class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make()
                            ->schema([
                                Forms\Components\TextInput::make('name') ->label('Nom du produit')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('barcode')->label('Code-barres')
                                    ->nullable()
                                    ->unique(Product::class, 'barcode', ignoreRecord: true),
                                Forms\Components\MarkdownEditor::make('description')->label('Description')
                                    ->columnSpan('full'),
                                FileUpload::make('image')->label('Image')
                                    ->image()
                                    ->imageEditor()
                                    ->columnSpan('full'),
                            ])
                            ->columns(2),
                        Forms\Components\Section::make('Prix')
                            ->schema([
                                Forms\Components\TextInput::make('purchase_price')->label('Prix d\'achat')->prefix('FCFA')
                                    ->numeric()
                                    ->nullable(),
                                Forms\Components\TextInput::make('selling_price')->label('Prix de vente')->prefix('FCFA')
                                    ->numeric()
                                    ->nullable(),
                            ])
                            ->columns(2),
                        Forms\Components\Section::make('Inventaire')
                            ->schema([
                                Forms\Components\TextInput::make('quantity')->label('Stock')
                                    ->numeric()
                                    ->default(0)
                                    ->nullable(),
                                Forms\Components\TextInput::make('security_stock')->label('Stock de sécurité')
                                    ->numeric()
                                    ->default(0)
                                    ->nullable(),
                                Forms\Components\Checkbox::make('active')
                                    ->default(true),
                            ])
                            ->columns(2),
                        Forms\Components\Section::make('Associations')
                            ->schema([
                                Forms\Components\Select::make('suppliers')
                                    ->relationship('suppliers', 'name')
                                    ->label('Fournisseur')
                                    ->multiple()
                                    ->searchable()
                                    ->createOptionForm(self::supplierForm()),
                                Forms\Components\Select::make('categories')->label('Catégories')
                                    ->relationship('categories', 'name')
                                    ->multiple()
                                    ->searchable(),
                            ])
                            ->columns(2),
                    ])
                    ->columnSpan(['lg' => 2]),
            ]);
    }

    protected static function supplierForm(): array
    {
        return [
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->label('Nom du fournisseur'),
            Forms\Components\TextInput::make('phone')
                ->tel()
                ->label('Téléphone'),
            Forms\Components\TextInput::make('email')
                ->email()
                ->label('Email')
                ->maxLength(255),
            Forms\Components\TextInput::make('address')
                ->label('Adresse')
                ->maxLength(255),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')->label('Image')->height(50)->width(50),
                Tables\Columns\TextColumn::make('name')->label('Nom')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('barcode')->label('Code-Barres')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('selling_price')->label('Prix de vente')->sortable(),
                Tables\Columns\TextColumn::make('quantity')->label('Stock')->sortable(),
                Tables\Columns\TextColumn::make('security_stock')->label('Stock de sécurité')->sortable(),
                Tables\Columns\BooleanColumn::make('active')->sortable(),
            ])
            ->filters([
                // Ajoutez ici les filtres nécessaires
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Vos gestionnaires de relations existants...
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
