<?php

namespace App\Filament\Resources\QrAbsenResource\Pages;

use App\Filament\Resources\QrAbsenResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Carbon\Carbon;
use App\Models\QrAbsen;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;

class CreateQrAbsen extends CreateRecord
{
    protected static string $resource = QrAbsenResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // Generate QR codes for all days in the selected month
        $month = Carbon::createFromFormat('Y-m', $data['month']);
        $daysInMonth = $month->daysInMonth;
        $firstRecord = null;

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = $month->copy()->day($day);
            // Simple random code generation
            $qrCheckin = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 6));
            $qrCheckout = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 6));

            $record = QrAbsen::create([
                'date' => $date->format('Y-m-d'),
                'qr_checkin' => $qrCheckin,
                'qr_checkout' => $qrCheckout,
            ]);
            if (! $firstRecord) {
                $firstRecord = $record;
            }
        }

        Notification::make()
            ->success()
            ->title('QR codes generated for ' . $month->format('F Y'))
            ->send();

        return $firstRecord;
    }
}
