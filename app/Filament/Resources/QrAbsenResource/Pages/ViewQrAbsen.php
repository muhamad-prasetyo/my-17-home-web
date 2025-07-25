<?php

namespace App\Filament\Resources\QrAbsenResource\Pages;

use App\Filament\Resources\QrAbsenResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Forms;
use Filament\Forms\Form;
use Illuminate\Support\HtmlString;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

class ViewQrAbsen extends ViewRecord
{
    protected static string $resource = QrAbsenResource::class;

    public function form(Form $form): Form
    {
        return $form->schema([
         
            Forms\Components\Placeholder::make('qr_checkin')
                ->label('QR Check-in')
                ->content(fn (): HtmlString => new HtmlString(
                    '<img src="data:image/png;base64,' .
                    base64_encode(
                        (new PngWriter())->write(
                            QrCode::create($this->getRecord()->qr_checkin)
                        )->getString()
                    ) . '" width="200" />'
                )),
            Forms\Components\Placeholder::make('qr_checkout')
                ->label('QR Check-out')
                ->content(fn (): HtmlString => new HtmlString(
                    '<img src="data:image/png;base64,' .
                    base64_encode(
                        (new PngWriter())->write(
                            QrCode::create($this->getRecord()->qr_checkout)
                        )->getString()
                    ) . '" width="200" />'
                )),
        ]);
    }
} 