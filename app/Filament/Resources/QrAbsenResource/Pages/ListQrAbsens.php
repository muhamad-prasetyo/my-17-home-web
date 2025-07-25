<?php

namespace App\Filament\Resources\QrAbsenResource\Pages;

use App\Filament\Resources\QrAbsenResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQrAbsens extends ListRecords
{
    protected static string $resource = QrAbsenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
