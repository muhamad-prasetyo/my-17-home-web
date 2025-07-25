<?php

namespace App\Filament\Resources\FakeGpsLogResource\Pages;

use App\Filament\Resources\FakeGpsLogResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFakeGpsLog extends EditRecord
{
    protected static string $resource = FakeGpsLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
