<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;

class UserDayOffsRelationManager extends RelationManager
{
    protected static string $relationship = 'dayOffs'; // relasi di model User

    public function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\DatePicker::make('date')->required(),
            Forms\Components\TextInput::make('description')->maxLength(255),
        ]);
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('date')->date(),
            Tables\Columns\TextColumn::make('description'),
        ])
        ->headerActions([
            Tables\Actions\CreateAction::make(),
        ])
        ->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ]);
    }

    public function canCreate(): bool
    {
        return true;
    }
} 