<?php

namespace App\Filament\Resources\FakeGpsLogResource\Pages;

use App\Filament\Resources\FakeGpsLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\Action;
use App\Models\User;

class ListFakeGpsLogs extends ListRecords
{
    protected static string $resource = FakeGpsLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Action::make('detail')
                ->label('Detail')
                ->icon('heroicon-o-eye')
                ->modalHeading('Detail Fake GPS Log')
                ->modalContent(function ($record) {
                    $user = $record->user;
                    $count = \App\Models\FakeGpsLog::where('user_id', $user->id)->count();
                    $banned = $user->status === 'banned';
                    return view('filament.components.fake-gps-detail', [
                        'record' => $record,
                        'user' => $user,
                        'count' => $count,
                        'banned' => $banned,
                    ]);
                })
                ->visible(fn ($record) => true),
            Action::make('ban')
                ->label('Ban User')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function ($record) {
                    $user = $record->user;
                    $user->status = 'banned';
                    $user->save();
                })
                ->visible(function ($record) {
                    $user = $record->user;
                    $count = \App\Models\FakeGpsLog::where('user_id', $user->id)->count();
                    return $count >= 3 && $user->status !== 'banned';
                }),
        ];
    }
}
