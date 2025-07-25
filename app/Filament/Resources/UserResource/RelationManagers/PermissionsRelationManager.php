<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PermissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'permissionRequests';

    protected static ?string $recordTitleAttribute = 'date_permission';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('date_permission')
                    ->label('Tanggal Izin')
                    ->required(),
                Forms\Components\Textarea::make('reason')
                    ->label('Alasan')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\FileUpload::make('image')
                    ->label('Bukti')
                    ->image()
                    ->directory('permissions')
                    ->visibility('public')
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_approved')
                    ->label('Disetujui')
                    ->default(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date_permission')
                    ->label('Tanggal Izin')
                    ->date(),
                Tables\Columns\TextColumn::make('reason')
                    ->label('Alasan')
                    ->limit(50),
                Tables\Columns\IconColumn::make('is_approved')
                    ->label('Status')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(fn ($record) => $record->update(['is_approved' => true]))
                    ->visible(fn ($record) => !$record->is_approved),
                Tables\Actions\Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->action(fn ($record) => $record->update(['is_approved' => false]))
                    ->visible(fn ($record) => $record->is_approved),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
} 