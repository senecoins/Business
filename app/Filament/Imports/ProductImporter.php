<?php

namespace App\Filament\Imports;

use App\Models\Product;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class ProductImporter extends Importer
{
    protected static ?string $model = Product::class;

    public static function getColumns(): array
    {
        return [
            // Champ 'name' requis
            ImportColumn::make('name')
                ->requiredMapping() // Ce champ est obligatoire
                ->rules(['required']),

            // Champ 'description' nullable
            ImportColumn::make('description')
                ->rules(['nullable']),

            // Champ 'barcode' nullable
            ImportColumn::make('barcode')
                ->rules(['nullable']),

            // Champ 'purchase_price' nullable
            ImportColumn::make('purchase_price')
                ->numeric()
                ->rules(['nullable', 'integer']),

            // Champ 'selling_price' nullable
            ImportColumn::make('selling_price')
                ->numeric()
                ->rules(['nullable', 'integer']),

            // Champ 'quantity' nullable
            ImportColumn::make('quantity')
                ->numeric()
                ->rules(['nullable', 'integer']),

            // Champ 'security_stock' nullable
            ImportColumn::make('security_stock')
                ->numeric()
                ->rules(['nullable', 'integer']),

            // Champ 'active' nullable
            ImportColumn::make('active')
                ->boolean()
                ->rules(['nullable', 'boolean']),

            // Champ 'image' nullable
            ImportColumn::make('image')
                ->rules(['nullable']),
        ];
    }

    public function resolveRecord(): ?Product
    {
        return new Product();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your product import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
