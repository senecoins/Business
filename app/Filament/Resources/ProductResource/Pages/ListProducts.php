<?php

namespace App\Filament\Resources\ProductResource\Pages;
use App\Filament\Imports\ProductImporter;
use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\ImportAction;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\ImportAction::make()
                ->importer(ProductImporter::class)
                ->chunkSize(100) // Taille des lots pour le traitement
                ->csvDelimiter(',') // Délimiteur du CSV
        ];

    }
}
