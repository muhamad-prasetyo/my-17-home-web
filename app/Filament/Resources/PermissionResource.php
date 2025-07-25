<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PermissionResource\Pages;
use App\Filament\Resources\PermissionResource\RelationManagers;
use App\Models\Permission;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\ApprovedPermissionConfirmation;
use Carbon\Carbon;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Support\Facades\Storage;
use Auth;
class PermissionResource extends Resource
{
    protected static ?string $model = Permission::class;

    protected static ?string $modelLabel = 'Laporan';
    protected static ?string $pluralModelLabel = 'Laporan Karyawan';

    // protected static ?string $navigationIcon = 'heroicon-o-document-check'; // Dihapus
    
    protected static ?string $navigationLabel = 'Laporan Karyawan';
    protected static ?string $navigationGroup = 'Manajemen Cuti & Izin';
    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'laporan-karyawan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required()
                    ->label('Karyawan'),
                Forms\Components\DatePicker::make('date_permission')
                    ->required()
                    ->label('Tanggal Laporan'),
                Forms\Components\Textarea::make('reason')
                    ->required()
                    ->label('Deskripsi Laporan')
                    ->columnSpanFull(),
                Forms\Components\FileUpload::make('image')
                    ->label('Bukti Laporan (Gambar)')
                    ->image()
                    ->disk('public')
                    ->directory('permissions_proof')
                    ->visibility('public')
                    ->maxSize(2048)
                    ->columnSpanFull(),
                Forms\Components\Select::make('is_approved')
                    ->label('Status Persetujuan')
                    ->options([
                        null => 'Pending',
                        0 => 'Ditolak',
                        1 => 'Disetujui',
                    ])
                    ->default(null),
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
                TextColumn::make('user.name')
                    ->label('Nama Karyawan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('date_permission')
                    ->label('Tanggal Laporan')
                    ->date('d F Y')
                    ->sortable(),
                Tables\Columns\ImageColumn::make('image')
                    ->label('Bukti')
                    ->disk('public')
                    ->visibility('public')
                    ->width(80)
                    ->height(80),
                TextColumn::make('reason')
                    ->label('Deskripsi Laporan')
                    ->limit(50)
                    ->tooltip(fn (Permission $record): string => $record->reason),
                IconColumn::make('is_approved')
                    ->label('Status')
                    ->icon(fn (string $state): string => match ((int) $state) {
                        1 => 'heroicon-o-check-circle',
                        0 => 'heroicon-o-x-circle',
                        default => 'heroicon-o-clock', // Default untuk null atau nilai lain
                    })
                    ->color(fn (string $state): string => match ((int) $state) {
                        1 => 'success',
                        0 => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('created_at')
                    ->label('Diajukan pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Tambahkan filter jika perlu, misal berdasarkan status
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Detail Laporan/Izin Karyawan')
                    ->modalDescription('Informasi lengkap laporan atau izin karyawan')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
                Tables\Actions\EditAction::make(), // Akan menggunakan form() di atas
                Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Permission $record) {
                        if ($record->is_approved === 1) {
                            FilamentNotification::make()
                                ->title('Izin sudah disetujui sebelumnya')
                                ->warning()
                                ->send();
                            return;
                        }
                        $record->is_approved = 1;
                        $record->save();
                        Log::info('PermissionResource: Permission ID ' . $record->id . ' approved.');
                        static::processApprovalNotification($record, true);
                        FilamentNotification::make()
                            ->title('Izin berhasil disetujui')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Permission $record) => $record->is_approved !== 1), // Hanya tampil jika belum disetujui

                Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Permission $record) {
                        if ($record->is_approved === 0) {
                            FilamentNotification::make()
                                ->title('Izin sudah ditolak sebelumnya')
                                ->warning()
                                ->send();
                            return;
                        }
                        $record->is_approved = 0;
                        $record->save();
                        Log::info('PermissionResource: Permission ID ' . $record->id . ' rejected.');
                        static::processApprovalNotification($record, false);
                        FilamentNotification::make()
                            ->title('Izin berhasil ditolak')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Permission $record) => $record->is_approved !== 0), // Hanya tampil jika belum ditolak
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

    protected static function processApprovalNotification(Permission $permission, bool $isApproved)
    {
        $user = $permission->user; // Asumsi relasi 'user' sudah di-load atau bisa di-load
        if (!$user) {
            Log::error('PermissionResource: User not found for permission ID ' . $permission->id . ', user ID ' . $permission->user_id);
            return;
        }

        $statusString = $isApproved ? 'Disetujui' : 'Ditolak';
        $notificationMessage = 'Status Izin anda pada tanggal ' . Carbon::parse($permission->date_permission)->translatedFormat('d F Y') . ' adalah ' . $statusString;

        Log::info('PermissionResource: Attempting to send notification for user ID ' . $user->id . ' for permission approval status: ' . $statusString);
        static::sendFCMNotificationToUser($user->id, $notificationMessage);

        if ($isApproved) {
            try {
                $date = Carbon::parse($permission->date_permission)->translatedFormat('d F Y');
                Mail::to($user->email)->send(new ApprovedPermissionConfirmation($user, $date, $permission->reason));
                Log::info('PermissionResource: Approval email sent to ' . $user->email);
            } catch (\Exception $e) {
                Log::error('PermissionResource: Failed to send approval email to ' . $user->email . '. Error: ' . $e->getMessage());
            }
        }
    }

    protected static function sendFCMNotificationToUser(int $userId, string $message)
    {
        Log::info('PermissionResource@sendFCMNotificationToUser: Method CALLED for user ID ' . $userId . ' with message: ' . $message);

        $user = User::find($userId);
        if (!$user) {
            Log::error('FCM failed: User not found for ID ' . $userId);
            return;
        }

        $token = $user->fcm_token;

        if (empty($token)) {
            Log::warning('FCM not sent: Token is empty for user ID ' . $userId);
            return;
        }

        Log::info('Attempting to send FCM to user ' . $userId . ' with token ' . $token . ' and message: ' . $message);
        
        try {
            $messaging = app('firebase.messaging');
            $notification = FirebaseNotification::create('Status Pengajuan Izin', $message); // Judul notifikasi lebih spesifik

            $cloudMessage = CloudMessage::withTarget('token', $token)
                ->withNotification($notification);
            
            $messaging->send($cloudMessage);
            Log::info('FCM sent successfully to user ' . $userId);
        } catch (\Kreait\Firebase\Exception\MessagingException $e) {
            Log::error('FCM failed for user ' . $userId . ' with token ' . $token . '. Error: ' . $e->getMessage());
            // Pertimbangkan untuk menghapus token jika tidak valid
            // if ($e->getMessagingError()->getCode() === 'UNREGISTERED' || $e->getMessagingError()->getCode() === 'INVALID_ARGUMENT') {
            //     Log::info('FCM token for user ' . $userId . ' seems invalid (UNREGISTERED/INVALID_ARGUMENT), removing.');
            //     $user->fcm_token = null;
            //     $user->save();
            // }
        } catch (\Exception $e) {
            Log::error('General error sending FCM for user ' . $userId . ' with token ' . $token . '. Error: ' . $e->getMessage());
        }
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\UserRelationManager::make(),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPermissions::route('/'),
            'create' => Pages\CreatePermission::route('/create'),
            'edit' => Pages\EditPermission::route('/{record}/edit'),
            'view' => Pages\ViewPermission::route('/{record}'),
        ];
    }
}
