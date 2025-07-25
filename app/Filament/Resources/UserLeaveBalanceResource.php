<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserLeaveBalanceResource\Pages;
use App\Filament\Resources\UserLeaveBalanceResource\RelationManagers;
use App\Models\UserLeaveBalance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Auth;
class UserLeaveBalanceResource extends Resource
{
    protected static ?string $model = UserLeaveBalance::class;

    // protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Leave Balances';
    protected static ?string $navigationGroup = 'Manajemen Karyawan';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('leave_type_id')
                    ->relationship('leaveType', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('year')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('entitled_days')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('taken_days')
                    ->numeric()
                    ->required(),
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
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('leaveType.name')
                    ->label('Leave Type')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('year')
                    ->sortable(),
                Tables\Columns\TextColumn::make('entitled_days')
                    ->sortable(),
                Tables\Columns\TextColumn::make('taken_days')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diupdate')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListUserLeaveBalances::route('/'),
            'create' => Pages\CreateUserLeaveBalance::route('/create'),
            'edit' => Pages\EditUserLeaveBalance::route('/{record}/edit'),
        ];
    }
}
