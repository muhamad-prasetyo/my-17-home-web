<?php

namespace App\Filament\Resources\AttendanceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserRelationManager extends RelationManager
{
    protected static string $relationship = 'user';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->label('Telepon')
                    ->tel()
                    ->maxLength(255),
                Forms\Components\TextInput::make('position')
                    ->label('Posisi')
                    ->maxLength(255),
                Forms\Components\TextInput::make('department')
                    ->label('Departemen')
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama'),
                Tables\Columns\TextColumn::make('email'),
                Tables\Columns\TextColumn::make('position')
                    ->label('Posisi'),
                Tables\Columns\TextColumn::make('department')
                    ->label('Departemen'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Read-only relation, no actions needed
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // Read-only relation, no bulk actions needed
            ]);
    }
} 