<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScheduleResource\Pages;
use App\Filament\Resources\ScheduleResource\RelationManagers;
use App\Models\Schedule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use App\Filament\Resources\ScheduleResource\RelationManagers\UsersRelationManager;

class ScheduleResource extends Resource
{
    protected static ?string $model = Schedule::class;

    // protected static ?string $navigationIcon = 'heroicon-o-calendar-days'; // Dihapus
    protected static ?string $navigationLabel = 'Schedules';
    protected static ?string $navigationGroup = 'Manajemen Absensi';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('office_id')
                    ->relationship('office', 'name')
                    ->searchable()->preload()
                    ->required()
                    ->label('Office'),
                Forms\Components\TextInput::make('schedule_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TimePicker::make('start_time')
                    ->label('Jam Mulai')
                    ->required(),
                Forms\Components\TimePicker::make('end_time')
                    ->label('Jam Selesai')
                    ->required(),
                Forms\Components\TextInput::make('working_days')
                    ->label('Hari Kerja')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Toggle::make('is_active')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            
            ->columns([
                Tables\Columns\TextColumn::make('office.name')
                    ->label('Office')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('schedule_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('start_time')
                    ->label('Jam Mulai'),
                Tables\Columns\TextColumn::make('end_time')
                    ->label('Jam Selesai'),
                Tables\Columns\TextColumn::make('working_days')
                    ->label('Hari Kerja')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('office')->relationship('office', 'name'),
                SelectFilter::make('working_days')
                    ->label('Hari Kerja')
                    ->options([
                        'Mon' => 'Senin',
                        'Tue' => 'Selasa',
                        'Wed' => 'Rabu',
                        'Thu' => 'Kamis',
                        'Fri' => 'Jumat',
                        'Sat' => 'Sabtu',
                        'Sun' => 'Minggu',
                    ])
                    ->multiple(),
                Filter::make('is_active')->label('Active')->toggle(),
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
            UsersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSchedules::route('/'),
            'create' => Pages\CreateSchedule::route('/create'),
            'edit' => Pages\EditSchedule::route('/{record}/edit'),
        ];
    }
}
