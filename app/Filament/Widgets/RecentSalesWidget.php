<?php
namespace App\Filament\Widgets;
use App\Models\SaleItem;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
class RecentSalesWidget extends TableWidget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Activités récentes';
    public function table(Table $table): Table
    {
        return $table
            ->query(
                SaleItem::query()
                    ->with(['product', 'sale'])
                    ->latest('created_at')
                    ->limit(5)
            )
            ->columns([
                ImageColumn::make('product.image')
                    ->label('Image')
                    ->circular()
                    ->defaultImageUrl(url('/images/default-product.png')),
                TextColumn::make('product.name')
                    ->label('Produit')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => 'Il y a ' . $record->created_at->diffForHumans(null, true))
                    ->extraAttributes(fn ($record) => [
                        'class' => 'flex flex-col',
                    ]),
                TextColumn::make('quantity')
                    ->label('Quantité')
                    ->sortable()
                    ->visibleFrom('md'),
                TextColumn::make('final_price')
                    ->label('Montant')
                    ->formatStateUsing(fn ($state) => '+' . number_format($state, 0, ',', ' ') . ' FCFA')
                    ->html()
                    ->sortable()
                    ->color('success')
                    ->alignEnd(),
                TextColumn::make('created_at')
                    ->label('Vendu')
                    ->dateTime()
                    ->since()
                    ->sortable()
                    ->visibleFrom('md'),
                TextColumn::make('sale.payment_status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'partial' => 'warning',
                        'pending' => 'danger',
                    })
                    ->visibleFrom('md'),
            ])
            ->contentGrid([
                'md' => 2,
                'lg' => 3,
                'xl' => 6,
            ])
            ->filters([
                // Add filters if needed
            ])
            ->actions([
                // Add actions if needed
            ])
            ->bulkActions([
                // Add bulk actions if needed
            ]);
    }
}
