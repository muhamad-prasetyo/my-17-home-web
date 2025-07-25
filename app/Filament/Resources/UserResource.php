<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Filament\Tables\Filters\Filter;
use Auth;
use App\Filament\Resources\UserResource\RelationManagers\UserDayOffsRelationManager;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    // protected static ?string $navigationIcon = 'heroicon-o-users';
    
    protected static ?string $navigationLabel = 'Pengguna';
    protected static ?string $navigationGroup = 'Manajemen Karyawan';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\FileUpload::make('image_url')
                ->label('Foto Profil')
                ->avatar()
                ->disk('public')
                ->directory('images/users')
                ->visibility('public')
                ->maxSize(1024)
                ->helperText('Maksimal ukuran 1MB (format: jpg,png)'),
                Forms\Components\TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create'),
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
                Forms\Components\DatePicker::make('tanggal_lahir')
                    ->label('Tanggal Lahir')
                    ->nullable(),
                Forms\Components\TextInput::make('kewarganegaraan')
                    ->label('Kewarganegaraan')
                    ->nullable()
                    ->maxLength(255),
                Forms\Components\TextInput::make('agama')
                    ->label('Agama')
                    ->nullable()
                    ->maxLength(255),
                Forms\Components\Select::make('jenis_kelamin')
                    ->label('Jenis Kelamin')
                    ->options([
                        'Laki-Laki' => 'Laki-Laki',
                        'Perempuan' => 'Perempuan',
                    ])
                    ->nullable(),
                Forms\Components\Select::make('status_pernikahan')
                    ->label('Status Pernikahan')
                    ->options([
                        'Lajang' => 'Lajang',
                        'Menikah' => 'Menikah',
                        'Bercerai' => 'Bercerai',
                    ])
                    ->nullable(),
                Forms\Components\TextInput::make('waktu_kontrak')
                    ->label('Waktu Kontrak')
                    ->nullable()
                    ->maxLength(255),
                Forms\Components\TextInput::make('tinggi_badan')
                    ->label('Tinggi Badan (cm)')
                    ->numeric()
                    ->nullable(),
                Forms\Components\TextInput::make('berat_badan')
                    ->label('Berat Badan (kg)')
                    ->numeric()
                    ->nullable(),
                Forms\Components\Select::make('golongan_darah')
                    ->label('Golongan Darah')
                    ->options([
                        'A' => 'A',
                        'B' => 'B',
                        'AB' => 'AB',
                        'O' => 'O',
                    ])
                    ->nullable(),
                Forms\Components\Select::make('gangguan_penglihatan')
                    ->label('Gangguan Penglihatan')
                    ->options([
                        'Ya' => 'Ya',
                        'Tidak' => 'Tidak',
                    ])
                    ->nullable(),
                Forms\Components\Select::make('buta_warna')
                    ->label('Buta Warna')
                    ->options([
                        'Ya' => 'Ya',
                        'Tidak' => 'Tidak',
                    ])
                    ->nullable(),
              
                Forms\Components\Select::make('roles')
                    ->label('Roles')
                    ->multiple()
                    ->relationship('roles', 'name')
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('schedule_id')
                    ->label('Schedule')
                    ->relationship('schedule', 'schedule_name')
                    ->searchable()
                    ->preload()
                    ->nullable(),
                Forms\Components\Toggle::make('is_wfa')
                    ->label('Allow WFA')
                    ->required()
                    ->default(false),
                Forms\Components\Toggle::make('is_approved')
                    ->label('Approved'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $is_super_admin = Auth::user()->hasRole('super_admin');
                $is_hrd = Auth::user()->hasRole('hrd');

                if (!$is_super_admin && !$is_hrd) {
                    $query->where('user_id', Auth::user()->id);
                }
            })
            ->columns([
                Tables\Columns\ImageColumn::make('image_url')
                ->label('Foto')
                ->disk('public')
                ->circular()
                ->defaultImageUrl('/images/users/default_avatar.png'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama') 
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_wfa')
                    ->label('Apakah WFA')
                    ->boolean(),
                    Tables\Columns\TextColumn::make('schedule.schedule_name')
                    ->label('Jadwal')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Roles')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Telepon')
                    ->searchable(),
                Tables\Columns\TextColumn::make('position')
                    ->label('Posisi')
                    ->searchable(),
                Tables\Columns\TextColumn::make('department')
                    ->label('Departemen')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_approved')
                    ->label('Approved')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->sortable(),
             
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('is_wfa')->label('Allow WFA')->toggle(),
                Filter::make('is_approved')->label('Approved')->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\AttendancesRelationManager::class,
            RelationManagers\PermissionsRelationManager::class,
            UserDayOffsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
