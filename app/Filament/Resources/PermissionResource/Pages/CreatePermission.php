<?php

namespace App\Filament\Resources\PermissionResource\Pages;

use App\Filament\Resources\PermissionResource;
use App\Events\PermissionRequestCreated;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePermission extends CreateRecord
{
    protected static string $resource = PermissionResource::class;

    protected function afterCreate(): void
    {
        // Dispatch event untuk notifikasi admin
        event(new PermissionRequestCreated($this->record));
    }
}
