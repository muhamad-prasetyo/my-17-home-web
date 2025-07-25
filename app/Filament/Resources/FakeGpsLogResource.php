<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FakeGpsLogResource\Pages;
use App\Filament\Resources\FakeGpsLogResource\RelationManagers;
use App\Models\FakeGpsLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\User;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Columns\ViewColumn;

class FakeGpsLogResource extends Resource
{
    protected static ?string $model = FakeGpsLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->formatStateUsing(function ($state, $record) {
                        // Hitung jumlah pelanggaran user
                        $count = \App\Models\FakeGpsLog::where('user_id', $record->user_id)->count();
                        if ($count > 3) {
                            return '<span style="color:red;font-weight:bold">' . $state . ' (' . $count . 'x)</span>';
                        }
                        return $state . ($count > 1 ? ' (' . $count . 'x)' : '');
                    })
                    ->html(),
                Tables\Columns\TextColumn::make('latitude')->label('Latitude')->sortable(),
                Tables\Columns\TextColumn::make('longitude')->label('Longitude')->sortable(),
                Tables\Columns\TextColumn::make('device_info')->label('Device')->searchable(),
                Tables\Columns\TextColumn::make('ip_address')->label('IP')->searchable(),
                Tables\Columns\TextColumn::make('detected_at')->label('Waktu')->dateTime()->sortable(),
                ViewColumn::make('map_preview')
                    ->label('Map')
                    ->view('filament.components.fake-gps-map'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('User')
                    ->options(User::all()->pluck('name', 'id')->toArray()),
                Tables\Filters\Filter::make('detected_at')
                    ->form([
                        Forms\Components\DatePicker::make('detected_at_from')->label('Dari'),
                        Forms\Components\DatePicker::make('detected_at_until')->label('Sampai'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['detected_at_from'], fn($q, $date) => $q->whereDate('detected_at', '>=', $date))
                            ->when($data['detected_at_until'], fn($q, $date) => $q->whereDate('detected_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('detail')
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
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
                Tables\Actions\Action::make('ban')
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
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                ExportAction::make(), // Export ke Excel/CSV
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFakeGpsLogs::route('/'),
            'create' => Pages\CreateFakeGpsLog::route('/create'),
            'edit' => Pages\EditFakeGpsLog::route('/{record}/edit'),
        ];
    }

    public static function getNavigationSort(): ?int
    {
        return 5; // Angka lebih besar dari Peta Lokasi agar muncul di bawahnya
    }
}
