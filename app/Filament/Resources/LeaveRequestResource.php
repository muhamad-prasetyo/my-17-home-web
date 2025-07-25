<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaveRequestResource\Pages;
use App\Filament\Resources\LeaveRequestResource\RelationManagers;
use App\Models\LeaveRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Auth;
use App\Models\Attendance;
use Carbon\Carbon;
use Filament\Notifications\Notification;

class LeaveRequestResource extends Resource
{
    protected static ?string $model = LeaveRequest::class;

    // protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Cuti & Izin ';
    protected static ?string $navigationGroup = 'Manajemen Cuti & Izin';
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
                Forms\Components\DatePicker::make('start_date')
                    ->required(),
                Forms\Components\DatePicker::make('end_date')
                    ->required(),
                Forms\Components\Textarea::make('reason')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'cancelled' => 'Cancelled',
                    ])
                    ->required()
                    ->default('pending'),
                Forms\Components\Select::make('approved_by_user_id')
                    ->relationship('approver', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),
                Forms\Components\DateTimePicker::make('approval_date'),
                Forms\Components\Textarea::make('rejection_reason')
                    ->columnSpanFull(),
                Forms\Components\FileUpload::make('attachment_path')
                    ->label('Attachment')
                    ->directory('leave-attachments')
                    ->disk('public')
                    ->maxSize(2048)
                    ->nullable(),
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
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('leaveType.name')
                    ->label('Leave Type')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
                    Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'cancelled' => 'secondary',
                    ])
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'pending' => 'Pending',
                            'approved' => 'Approved',
                            'rejected' => 'Rejected',
                            'cancelled' => 'Cancelled',
                            default => ucfirst($state),
                        };
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('approver.name')
                    ->label('Approver')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('approval_date')
                    ->dateTime()
                    ->sortable(),
                    Tables\Columns\ImageColumn::make('attachment')
                    ->label('Attachment')
                    ->disk('public'),
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
                    ->modalHeading('Detail Pengajuan Cuti')
                    ->modalDescription('Informasi lengkap pengajuan cuti karyawan')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (LeaveRequest $record) {
                        // Cek apakah sudah disetujui atau ditolak sebelumnya
                        if ($record->status === 'approved') {
                            Notification::make()
                                ->title('Pengajuan sudah disetujui sebelumnya')
                                ->warning()
                                ->send();
                            return;
                        }
                         if ($record->status === 'rejected') {
                            Notification::make()
                                ->title('Pengajuan sudah ditolak sebelumnya')
                                ->warning()
                                ->send();
                            return;
                        }

                        // Update status pengajuan cuti/izin
                        $record->status = 'approved';
                        $record->approved_by_user_id = Auth::id();
                        $record->approval_date = Carbon::now();
                        $record->save();

                        // Buat catatan di tabel attendances untuk setiap hari dalam rentang cuti/izin
                        $startDate = Carbon::parse($record->start_date);
                        $endDate = Carbon::parse($record->end_date);

                        for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
                            // Cek apakah sudah ada catatan absensi untuk tanggal dan user ini
                            $existingAttendance = Attendance::where('user_id', $record->user_id)
                                ->whereDate('date', $date->toDateString())
                                ->first();

                            if (!$existingAttendance) {
                                // Buat catatan baru jika belum ada
                                Attendance::create([
                                    'user_id' => $record->user_id,
                                    'date' => $date->toDateString(),
                                    'attendance_type' => 'leave',
                                    'status_attendance' => 'leave',
                                ]);
                            } else {
                                // Update catatan yang sudah ada jika perlu (misal: jika sebelumnya absent)
                                // Untuk saat ini, kita biarkan saja jika sudah ada catatan (misal: present)
                                // Anda bisa tambahkan logika update di sini jika dibutuhkan
                            }
                        }

                        // Tampilkan notifikasi sukses
                        Notification::make()
                            ->title('Pengajuan cuti/izin berhasil disetujui dan dicatat di absensi')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (LeaveRequest $record) => $record->status === 'pending'), // Hanya tampil jika status masih pending

                Tables\Actions\Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (LeaveRequest $record) {
                         // Cek apakah sudah disetujui atau ditolak sebelumnya
                        if ($record->status === 'approved') {
                            Notification::make()
                                ->title('Pengajuan sudah disetujui sebelumnya')
                                ->warning()
                                ->send();
                            return;
                        }
                         if ($record->status === 'rejected') {
                            Notification::make()
                                ->title('Pengajuan sudah ditolak sebelumnya')
                                ->warning()
                                ->send();
                            return;
                        }
                        // Update status
                        $record->status = 'rejected';
                        // Anda bisa tambahkan kolom rejection_reason di sini jika perlu
                        $record->approved_by_user_id = Auth::id(); // Optional: catat siapa yang menolak
                        $record->approval_date = Carbon::now(); // Optional: catat tanggal penolakan
                        $record->save();

                        // Anda mungkin ingin menghapus catatan di tabel attendances jika sebelumnya sudah dibuat
                        // Namun, karena aksi approve hanya muncul jika status pending, ini tidak perlu.

                        // Tampilkan notifikasi sukses
                        Notification::make()
                            ->title('Pengajuan cuti/izin berhasil ditolak')
                            ->success()
                            ->send();
                    })
                     ->visible(fn (LeaveRequest $record) => $record->status === 'pending'), // Hanya tampil jika status masih pending

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
            'index' => Pages\ListLeaveRequests::route('/'),
            'create' => Pages\CreateLeaveRequest::route('/create'),
            'view' => Pages\ViewLeaveRequest::route('/{record}'),
            'edit' => Pages\EditLeaveRequest::route('/{record}/edit'),
        ];
    }
}
