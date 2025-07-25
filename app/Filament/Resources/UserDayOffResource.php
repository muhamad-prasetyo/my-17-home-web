<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserDayOffResource\Pages;
use App\Filament\Resources\UserDayOffResource\RelationManagers;
use App\Models\UserDayOff;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;

class UserDayOffResource extends Resource
{
    protected static ?string $model = UserDayOff::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    
    protected static ?string $navigationGroup = 'Manajemen Absensi';
    
    protected static ?string $navigationLabel = 'Hari Libur Karyawan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                DatePicker::make('date')
                    ->required(),
                TextInput::make('description')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->searchable()->sortable(),
                TextColumn::make('date')->date()->sortable(),
                TextColumn::make('description')->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListUserDayOffs::route('/'),
            'create' => Pages\CreateUserDayOff::route('/create'),
            'edit' => Pages\EditUserDayOff::route('/{record}/edit'),
        ];
    }
}
