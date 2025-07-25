<?php

namespace App\Filament\Resources\QrAbsenResource\Pages;

use App\Filament\Resources\QrAbsenResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQrAbsen extends EditRecord
{
    protected static string $resource = QrAbsenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
