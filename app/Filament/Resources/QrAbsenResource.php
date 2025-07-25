<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QrAbsenResource\Pages;
use App\Filament\Resources\QrAbsenResource\RelationManagers;
use App\Models\QrAbsen;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Filament\Tables\Actions\Action;

class QrAbsenResource extends Resource
{
    protected static ?string $model = QrAbsen::class;

    // protected static ?string $navigationIcon = 'heroicon-o-qr-code'; // Dihapus karena grup sudah punya ikon
    protected static ?string $navigationLabel = 'QR Absens';
    protected static ?string $navigationGroup = 'Manajemen Absensi';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('month')
                    ->type('month')
                    ->label('Bulan')
                    ->required()
                    ->helperText('Pilih bulan untuk generate QR absensi'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal')
                    ->date(),
                Tables\Columns\ImageColumn::make('qr_checkin')
                    ->label('QR Check-in')
                    ->getStateUsing(fn ($record): ?string =>
                        $record->qr_checkin
                            ? 'data:image/png;base64,' . base64_encode(
                                (new PngWriter())->write(
                                    QrCode::create($record->qr_checkin)
                                )->getString()
                            )
                            : null
                    )
                    ->height(100)
                    ->width(100),
                Tables\Columns\ImageColumn::make('qr_checkout')
                    ->label('QR Check-out')
                    ->getStateUsing(fn ($record): ?string =>
                        $record->qr_checkout
                            ? 'data:image/png;base64,' . base64_encode(
                                (new PngWriter())->write(
                                    QrCode::create($record->qr_checkout)
                                )->getString()
                            )
                            : null
                    )
                    ->height(100)
                    ->width(100),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Action::make('downloadPdf')
                    ->label('Download PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn (QrAbsen $record): string => route('qr_absens.download', ['id' => $record->id]))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->label('Delete Selected')
                    ->requiresConfirmation(),
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
            'index' => Pages\ListQrAbsens::route('/'),
            'create' => Pages\CreateQrAbsen::route('/create'),
            'view' => Pages\ViewQrAbsen::route('/{record}'),
            'edit' => Pages\EditQrAbsen::route('/{record}/edit'),
        ];
    }
}
