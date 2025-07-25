<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransferRequestResource\Pages;
use App\Filament\Resources\TransferRequestResource\RelationManagers;
use App\Models\TransferRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Auth;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use App\Models\User;
use App\Models\Office;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Illuminate\Support\Facades\Log;
use App\Events\TransferRequestApproved;

class TransferRequestResource extends Resource
{
    protected static ?string $model = TransferRequest::class;

    // protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static ?string $navigationLabel = 'Transfer Requests';
    protected static ?string $navigationGroup = 'Manajemen Karyawan';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('current_schedule_id')
                    ->label('Current Schedule')
                    ->relationship('currentSchedule', 'schedule_name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('target_schedule_id')
                    ->label('Target Schedule')
                    ->relationship('targetSchedule', 'schedule_name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Textarea::make('reason')
                    ->label('Reason')
                    ->columnSpanFull(),
                Forms\Components\DatePicker::make('request_date')
                    ->label('Request Date')
                    ->required(),
                Forms\Components\DatePicker::make('effective_date')
                    ->label('Effective Date')
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'completed' => 'Completed',
                    ])
                    ->required()
                    ->default('pending'),
                Forms\Components\Select::make('approved_by_user_id')
                    ->label('Approved By')
                    ->relationship('approver', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),
                Forms\Components\DateTimePicker::make('approval_date')
                    ->label('Approval Date'),
                Forms\Components\Textarea::make('rejection_reason')
                    ->label('Rejection Reason')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('currentSchedule.schedule_name')
                    ->label('From Schedule')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('targetSchedule.schedule_name')
                    ->label('To Schedule')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('request_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('effective_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('approver.name')
                    ->label('Approved By')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('approval_date')
                    ->dateTime()
                    ->sortable(),
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
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Detail Permintaan Transfer')
                    ->modalDescription('Informasi lengkap permintaan transfer karyawan')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (TransferRequest $record) {
                        // Cek apakah sudah disetujui atau ditolak sebelumnya
                        if ($record->status === 'approved') {
                            Notification::make()
                                ->title('Permintaan transfer sudah disetujui sebelumnya')
                                ->warning()
                                ->send();
                            return;
                        }
                        if ($record->status === 'rejected') {
                            Notification::make()
                                ->title('Permintaan transfer sudah ditolak sebelumnya')
                                ->warning()
                                ->danger()
                                ->send();
                            return;
                        }

                        // Update status permintaan transfer
                        $record->status = 'approved';
                        $record->approved_by_user_id = Auth::id();
                        $record->approval_date = Carbon::now();
                        $record->save();

                        // Trigger event to send FCM to the transferred user
                        event(new TransferRequestApproved($record));

                        // --- Mulai Logika Notifikasi Transfer ke Kasir dan Staff Tujuan ---
                        $currentOffice = $record->currentSchedule->office;
                        $targetOffice = $record->targetSchedule->office;

                        // Notifikasi ke role 'Kasir' di office lama
                        if ($currentOffice) {
                            $kasirUsersInCurrentOffice = $currentOffice->schedules->flatMap->users->filter(function ($user) {
                                return $user->hasRole('kasir');
                            });
                            $messageToKasir = "Pemberitahuan: Staff {$record->user->name} telah ditransfer dari Outlet ini ke {$targetOffice->name}.";
                            static::sendTransferNotification($kasirUsersInCurrentOffice, $messageToKasir);
                        }

                        // Notifikasi ke semua staff di office baru
                        if ($targetOffice) {
                            $allUsersInTargetOffice = $targetOffice->schedules->flatMap->users;
                            $approvalDateFormatted = Carbon::parse($record->effective_date)->format('d F Y');
                            $messageToTarget = "Pemberitahuan: Staff {$record->user->name} akan bergabung dengan Outlet ini efektif pada {$approvalDateFormatted}.";
                            static::sendTransferNotification($allUsersInTargetOffice, $messageToTarget);
                        }

                        // Tampilkan notifikasi sukses
                        Notification::make()
                            ->title('Permintaan transfer berhasil disetujui dan jadwal pengguna diperbarui')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (TransferRequest $record) => $record->status === 'pending'), // Hanya tampil jika status masih pending

                Tables\Actions\Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (TransferRequest $record) {
                        // Cek apakah sudah disetujui atau ditolak sebelumnya
                        if ($record->status === 'approved') {
                            Notification::make()
                                ->title('Permintaan transfer sudah disetujui sebelumnya')
                                ->warning()
                                ->success()
                                ->send();
                            return;
                        }
                        if ($record->status === 'rejected') {
                            Notification::make()
                                ->title('Permintaan transfer sudah ditolak sebelumnya')
                                ->warning()
                                ->danger()
                                ->send();
                            return;
                        }
                        // Update status
                        $record->status = 'rejected';
                        $record->approved_by_user_id = Auth::id(); // Optional: catat siapa yang menolak
                        $record->approval_date = Carbon::now(); // Optional: catat tanggal penolakan
                        $record->save();

                        // Tampilkan notifikasi sukses
                        Notification::make()
                            ->title('Permintaan transfer berhasil ditolak')
                            ->success()
                            ->danger()
                            ->send();
                    })
                    ->visible(fn (TransferRequest $record) => $record->status === 'pending'), // Hanya tampil jika status masih pending

                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListTransferRequests::route('/'),
            'view' => Pages\ViewTransferRequest::route('/{record}'),
            'edit' => Pages\EditTransferRequest::route('/{record}/edit'),
        ];
    }

    /**
     * Helper function to send transfer notification.
     */
    protected static function sendTransferNotification($users, string $message)
    {
        if ($users->isEmpty()) {
            Log::info("[FCM Send Transfer] Tidak ada user yang ditemukan untuk notifikasi transfer.");
            return;
        }

        // Log daftar user dan jumlah
        Log::info("[FCM Send Transfer] Daftar user yang akan dikirimi (count: " . $users->count() . "): " . $users->pluck('name')->toJson());

        $messaging = app('firebase.messaging');
        $notification = FirebaseNotification::create('Pemberitahuan Transfer Staff', $message);

        foreach ($users as $user) {
            // Gather all tokens: fcm_token field and deviceTokens relation
            $tokens = [];
            if (!empty($user->fcm_token)) {
                $tokens[] = $user->fcm_token;
            }
            if (method_exists($user, 'deviceTokens')) {
                $tokens = array_merge($tokens, $user->deviceTokens()->pluck('device_token')->toArray());
            }
            if (empty($tokens)) {
                Log::info("[FCM Send Transfer] Tidak ada token device ditemukan untuk User ID: {$user->id} ({$user->name}).");
                continue;
            }
            // Log tokens untuk debugging
            Log::info("[FCM Send Transfer] Tokens for User {$user->id}: " . json_encode($tokens));

            $dataPayload = [
                'type' => 'transfer',
                'user_id' => (string)$user->id,
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ];

            foreach ($tokens as $token) {
                if (empty($token)) {
                    continue;
                }
                Log::info("[FCM Send Transfer] Mencoba mengirim notifikasi ke User ID: {$user->id} dengan token: {$token}");

                try {
                    $msg = CloudMessage::withTarget('token', $token)
                        ->withNotification($notification)
                        ->withData($dataPayload);
                    $messaging->send($msg);
                    Log::info("[FCM Send Transfer] Notifikasi berhasil dikirim ke token: {$token}");
                } catch (\Throwable $e) {
                    Log::error("[FCM Send Transfer] Gagal mengirim ke token {$token}: " . $e->getMessage());
                }
            }
        }
    }
}
