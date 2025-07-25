<?php

namespace App\Filament\Resources\AnnouncementResource\Pages;

use App\Filament\Resources\AnnouncementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAnnouncement extends EditRecord
{
    protected static string $resource = AnnouncementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\Action::make('hapus_banner')
                ->label('Hapus Banner')
                ->color('danger')
                ->visible(fn ($record) => filled($record?->banner_path))
                ->action(function ($record) {
                    if ($record && $record->banner_path) {
                        \Storage::disk('public')->delete($record->banner_path);
                        $record->banner_path = null;
                        $record->save();
                        \Filament\Notifications\Notification::make()
                            ->title('Banner berhasil dihapus')
                            ->success()
                            ->send();
                    }
                })
                ->requiresConfirmation()
                ->icon('heroicon-o-trash'),
        ];
    }
}
