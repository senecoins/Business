<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product;
use Illuminate\Support\Collection;

class EditSale extends EditRecord
{
    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function () {
                    // Restaurer le stock avant de supprimer la vente
                    $this->restoreStock($this->record);
                }),
        ];
    }

    // Ajoutez cette méthode pour restaurer le stock lors de la suppression
    protected function restoreStock(Model $sale): void
    {
        // Restaurer le stock pour chaque article de la vente
        foreach ($sale->items as $item) {
            $product = Product::find($item->product_id);
            if ($product) {
                $product->stock += $item->quantity;
                $product->save();
            }
        }
    }

    // Ajoutez cette méthode pour gérer le stock après la sauvegarde
    protected function afterSave(): void
    {
        // Récupérer les données originales avant la mise à jour
        $originalItems = $this->record->getOriginal('items') ?? new Collection();

        // Traiter les modifications de stock
        $this->handleStockChanges($originalItems, $this->record->items);
    }

    // Ajoutez cette méthode pour gérer les changements de stock
    protected function handleStockChanges(Collection $originalItems, Collection $newItems): void
    {
        // Créer un tableau associatif pour faciliter la comparaison
        $originalItemsMap = [];
        foreach ($originalItems as $item) {
            $originalItemsMap[$item->id] = $item;
        }

        // Parcourir les nouveaux éléments
        foreach ($newItems as $newItem) {
            $product = Product::find($newItem->product_id);
            if (!$product) continue;

            // Vérifier si l'élément existe dans les originaux
            if (isset($originalItemsMap[$newItem->id])) {
                $originalItem = $originalItemsMap[$newItem->id];

                // Si le produit a changé, restaurer le stock du produit original
                if ($originalItem->product_id != $newItem->product_id) {
                    $originalProduct = Product::find($originalItem->product_id);
                    if ($originalProduct) {
                        $originalProduct->stock += $originalItem->quantity;
                        $originalProduct->save();

                        // Déduire le stock du nouveau produit
                        $product->stock -= $newItem->quantity;
                        $product->save();
                    }
                } else {
                    // Même produit, mais quantité différente
                    $quantityDiff = $newItem->quantity - $originalItem->quantity;
                    if ($quantityDiff != 0) {
                        $product->stock -= $quantityDiff;
                        $product->save();
                    }
                }

                // Supprimer l'élément traité
                unset($originalItemsMap[$newItem->id]);
            } else {
                // Nouvel élément, déduire le stock
                $product->stock -= $newItem->quantity;
                $product->save();
            }
        }

        // Pour les éléments qui ont été supprimés, restaurer le stock
        foreach ($originalItemsMap as $originalItem) {
            $product = Product::find($originalItem->product_id);
            if ($product) {
                $product->quantity += $originalItem->quantity;
                $product->save();
            }
        }
    }
}
