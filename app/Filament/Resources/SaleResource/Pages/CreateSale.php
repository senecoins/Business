<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product;

class CreateSale extends CreateRecord
{
    protected static string $resource = SaleResource::class;

    // Ajoutez cette méthode pour gérer le stock après la création
    protected function afterCreate(): void
    {
        $this->updateProductStock($this->record);
    }

    // Ajoutez cette méthode pour mettre à jour le stock
    protected function updateProductStock(Model $sale): void
    {
        // Diminuer le stock pour chaque article vendu
        foreach ($sale->items as $item) {
            $product = Product::find($item->product_id);
            if ($product) {
                $product->quantity -= $item->quantity;
                $product->save();
            }
        }
    }
}
