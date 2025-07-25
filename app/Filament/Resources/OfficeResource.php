<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OfficeResource\Pages;
use App\Filament\Resources\OfficeResource\RelationManagers;
use App\Models\Office;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Humaidem\FilamentMapPicker\Fields\OSMMap;


class OfficeResource extends Resource
{
    protected static ?string $model = Office::class;

    protected static ?string $navigationGroup = 'Manajemen Absensi';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detail Kantor')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nama Kantor')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('address')
                                    ->label('Alamat')
                                    ->required()
                                    ->maxLength(255),
                            ]),
                    ]),

                Forms\Components\Section::make('Pengaturan Lokasi')
                    ->schema([
                        OSMMap::make('location')
                            ->label('Lokasi Peta')
                            ->showMarker()
                            ->draggable()
                            ->extraControl([
                                'zoomDelta'           => 1,
                                'zoomSnap'            => 0.25,
                                'wheelPxPerZoomLevel' => 60
                            ])
                           ->afterStateHydrated(function (Forms\Get $get, Forms\Set $set, $record) {
                             if (!$record) { return; }

                             $latitude = $record->latitude;
                             $longitude = $record->longitude;
                             
                             if($latitude && $longitude) {
                                 $set('location', ['lat' => $latitude, 'lng' => $longitude]);
                             }
                           })
                           ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                             $set('latitude', $state['lat']);
                             $set('longitude', $state['lng']);
                           })
                           ->maxZoom(19)
                           ->tilesUrl('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}'),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('latitude')
                                    ->label('Latitude')
                                    ->required()
                                    ->numeric()
                                    ->default(0),

                                Forms\Components\TextInput::make('longitude')
                                    ->label('Longitude')
                                    ->required()
                                    ->numeric()
                                    ->default(0),
                            ]),

                        Forms\Components\TextInput::make('radius_meter')
                            ->label('Radius (Meter)')
                            ->numeric()
                            ->required(),
                    ]),

                Forms\Components\Section::make('Jam Operasional')
                    ->schema([
                         Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TimePicker::make('start_time')
                                    ->label('Jam Buka')
                                    ->required(),
                                Forms\Components\TimePicker::make('end_time')
                                    ->label('Jam Tutup')
                                    ->required(),
                            ]),
                    ]),

                Forms\Components\Section::make('Tipe Absensi')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Tipe Absensi Office')
                            ->options([
                                'face_recognition' => 'Face Recognition',
                                'qr_code' => 'QR Code',
                            ])
                            ->required()
                            ->default('face_recognition'),
                        Forms\Components\Select::make('office_type')
                            ->label('Tipe Absensi')
                            ->options([
                                'ON_SITE' => 'On Site',
                                'OFF_SITE' => 'Off Site',
                            ])
                            ->required()
                            ->default('ON_SITE'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Kantor')
                    ->searchable(),
                Tables\Columns\TextColumn::make('address')
                    ->label('Alamat')
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('latitude')
                    ->label('Latitude'),
                Tables\Columns\TextColumn::make('longitude')
                    ->label('Longitude'),
                Tables\Columns\TextColumn::make('radius_meter')
                    ->label('Radius (Meter)')
                    ->numeric(),
                Tables\Columns\TextColumn::make('start_time')
                    ->label('Jam Buka')
                    ->time(),
                Tables\Columns\TextColumn::make('end_time')
                    ->label('Jam Tutup')
                    ->time(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe Absensi Office')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'face_recognition' => 'Face Recognition',
                        'qr_code' => 'QR Code',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('office_type')
                    ->label('Tipe Absensi'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Dihapus')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipe Absensi')
                    ->options([
                        'face_recognition' => 'Face Recognition',
                        'qr_code' => 'QR Code',
                    ]),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
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
            'index' => Pages\ListOffices::route('/'),
            'create' => Pages\CreateOffice::route('/create'),
            'edit' => Pages\EditOffice::route('/{record}/edit'),
        ];
    }
}
