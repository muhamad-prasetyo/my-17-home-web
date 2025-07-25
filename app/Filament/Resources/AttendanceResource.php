<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceResource\Pages;
use App\Filament\Resources\AttendanceResource\RelationManagers;
use App\Models\Attendance;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Auth;
use App\Models\LeaveRequest;
use App\Exports\AttendancesExport;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Collection;
use Filament\Tables\Contracts\HasTable;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static ?string $navigationLabel = 'Absensi';
    
    protected static ?string $navigationGroup = 'Manajemen Absensi';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Pengguna')
                    ->options(User::query()->pluck('name', 'id'))
                    ->searchable()
                    ->required(),
                Forms\Components\DatePicker::make('date')
                    ->label('Tanggal')
                    ->required(),
                Forms\Components\TimePicker::make('time_in')
                    ->label('Jam Masuk')
                    ->required(),
                Forms\Components\TimePicker::make('time_out')
                    ->label('Jam Keluar'),
                Forms\Components\TextInput::make('latlon_in')
                    ->label('Lokasi Masuk'),
                Forms\Components\TextInput::make('latlon_out')
                    ->label('Lokasi Keluar'),
                Forms\Components\Toggle::make('is_late')
                    ->label('Telat Hadir?'),
                Forms\Components\TextInput::make('late_duration')
                    ->label('Durasi Telat (menit)')
                    ->numeric(),
                Forms\Components\Textarea::make('late_reason')
                    ->label('Alasan Telat')
                    ->columnSpanFull(),
                
                // Transfer Data Section
                Forms\Components\Section::make('Data Transfer')
                    ->schema([
                        Forms\Components\Toggle::make('is_transfer_day')
                            ->label('Hari Transfer?')
                            ->disabled(),
                        Forms\Components\Select::make('transfer_status')
                            ->label('Status Transfer')
                            ->options([
                                'pending' => 'Menunggu',
                                'checked_in_at_source' => 'Check-In di Kantor Asal',
                                'checked_out_from_source' => 'Check-Out dari Kantor Asal',
                                'checked_in_at_destination' => 'Check-In di Kantor Tujuan',
                                'completed' => 'Selesai',
                            ])
                            ->disabled(),
                        Forms\Components\TextInput::make('source_office_id')
                            ->label('Kantor Asal')
                            ->disabled(),
                        Forms\Components\TimePicker::make('source_time_in')
                            ->label('Jam Masuk (Kantor Asal)')
                            ->disabled(),
                        Forms\Components\TimePicker::make('source_time_out')
                            ->label('Jam Keluar (Kantor Asal)')
                            ->disabled(),
                        Forms\Components\TextInput::make('destination_office_id')
                            ->label('Kantor Tujuan')
                            ->disabled(),
                        Forms\Components\TimePicker::make('destination_time_in')
                            ->label('Jam Masuk (Kantor Tujuan)')
                            ->disabled(),
                        Forms\Components\TimePicker::make('destination_time_out')
                            ->label('Jam Keluar (Kantor Tujuan)')
                            ->disabled(),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn ($record) => $record && $record->is_transfer_day),
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
            ->headerActions([
                Action::make('export')
                    ->label('Export Excel')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(function (HasTable $livewire) {
                        $records = $livewire->getFilteredTableQuery()->get();
                        return Excel::download(new AttendancesExport($records), 'attendances.xlsx');
                    }),
                Action::make('export_pdf')
                    ->label('Export PDF')
                    ->icon('heroicon-o-document')
                    ->action(function (HasTable $livewire) {
                        $records = $livewire->getFilteredTableQuery()->with(['user', 'sourceOffice', 'destinationOffice'])->get();
                        $pdf = \PDF::loadView('exports.attendances_pdf', ['attendances' => $records])->setPaper('a4', 'landscape');
                        return response()->streamDownload(function () use ($pdf) {
                            echo $pdf->stream();
                        }, 'attendances_' . now()->format('Ymd_His') . '.pdf');
                    }),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),
                // Kolom gabungan jam masuk (biasa & transfer)
                Tables\Columns\TextColumn::make('jam_masuk_final')
                    ->label('Jam Masuk')
                    ->getStateUsing(function ($record) {
                        if ($record->status_attendance === 'leave') {
                            return 'CUTI';
                        }
                        if ($record->is_transfer_day) {
                            return 'Asal: ' . ($record->source_time_in ?? '-') . ' | Tujuan: ' . ($record->destination_time_in ?? '-');
                        }
                        return $record->time_in ?? '-';
                    }),
                // Kolom gabungan jam keluar (biasa & transfer)
                Tables\Columns\TextColumn::make('jam_keluar_final')
                    ->label('Jam Keluar')
                    ->getStateUsing(function ($record) {
                        if ($record->status_attendance === 'leave') {
                            return 'CUTI';
                        }
                        if ($record->is_transfer_day) {
                            return 'Asal: ' . ($record->source_time_out ?? '-') . ' | Tujuan: ' . ($record->destination_time_out ?? '-');
                        }
                        return $record->time_out ?? '-';
                    }),
                // Kolom total jam kerja transfer tetap pakai logic custom
                Tables\Columns\TextColumn::make('id')
                    ->label('Total Jam Kerja')
                    ->formatStateUsing(function ($state, Attendance $record) {
                        $isTransfer = ($record->is_transfer_day === true || $record->is_transfer_day === 1 || $record->is_transfer_day === '1');
                        if ($isTransfer) {
                            // Hanya hitung jika sudah checkout di kantor tujuan
                            if (empty($record->source_time_in) || empty($record->destination_time_out)) {
                                return '-';
                            }
                            try {
                                $timeIn = Carbon::parse($record->source_time_in);
                                $timeOut = Carbon::parse($record->destination_time_out);
                                if ($timeOut->lt($timeIn)) {
                                    return '-';
                                }
                                $duration = $timeOut->diff($timeIn);
                                $hours = $duration->h + ($duration->days * 24);
                                $minutes = $duration->i;
                                return $hours . ' jam ' . $minutes . ' menit';
                            } catch (\Exception $e) {
                                return 'Err';
                            }
                        } else {
                            if (empty($record->time_in) || empty($record->time_out)) {
                                return '-';
                            }
                            try {
                                $timeIn = Carbon::parse($record->time_in);
                                $timeOut = Carbon::parse($record->time_out);
                                if ($timeOut->lt($timeIn)) {
                                    return '-';
                                }
                                $duration = $timeOut->diff($timeIn);
                                $hours = $duration->h + ($duration->days * 24);
                                $minutes = $duration->i;
                                return $hours . ' jam ' . $minutes . ' menit';
                            } catch (\Exception $e) {
                                return 'Err';
                            }
                        }
                    })
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('transfer_status')
                    ->label('Status Transfer')
                    ->formatStateUsing(function ($state, \App\Models\Attendance $record) {
                        if (!$record->is_transfer_day) return 'Tidak';
                        
                        $statusMap = [
                            'pending' => 'Menunggu',
                            'checked_in_at_source' => 'Check-In di Kantor Asal',
                            'checked_out_from_source' => 'Check-Out dari Kantor Asal',
                            'checked_in_at_destination' => 'Check-In di Kantor Tujuan',
                            'completed' => 'Selesai',
                        ];
                        
                        return $statusMap[$state] ?? 'Transfer';
                    })
                    ->badge()
                    ->color(function (\App\Models\Attendance $record) {
                        if (!$record->is_transfer_day) return 'secondary';
                        
                        $colorMap = [
                            'pending' => 'gray',
                            'checked_in_at_source' => 'info',
                            'checked_out_from_source' => 'warning',
                            'checked_in_at_destination' => 'primary',
                            'completed' => 'success',
                        ];
                        
                        return $colorMap[$record->transfer_status] ?? 'warning';
                    }),
                Tables\Columns\TextColumn::make('is_transfer_day')
                    ->label('Hari Transfer')
                    ->formatStateUsing(fn ($state) => $state ? 'Ya' : 'Tidak')
                    ->badge()
                    ->color(fn ($state) => $state ? 'warning' : 'secondary'),
                Tables\Columns\TextColumn::make('sourceOffice.name')
                    ->label('Kantor Asal'),
                Tables\Columns\TextColumn::make('destinationOffice.name')
                    ->label('Kantor Tujuan'),
                Tables\Columns\TextColumn::make('status_attendance')
                    ->label('Status Kehadiran')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'hadir' => 'success',
                        'sakit' => 'warning',
                        'izin' => 'info',
                        'alpha' => 'danger',
                        default => 'secondary',
                    }),
                // Tables\Columns\TextColumn::make('attendanceType')
                //     ->label('Tipe Kehadiran'),
                Tables\Columns\TextColumn::make('is_late')
                    ->label('Status Keterlambatan')
                    ->formatStateUsing(fn ($state) => $state ? 'Terlambat' : 'Tepat Waktu')
                    ->badge()
                    ->color(fn ($state) => $state ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('late_duration')
                    ->label('Durasi Telat'),
             
                // Tables\Columns\TextColumn::make('is_early_checkout')
                //     ->label('Status Checkout')
                //     ->formatStateUsing(fn ($state) => $state ? 'Dini' : 'Normal')
                //     ->badge()
                //     ->color(fn ($state) => $state ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('latlon_in')
                    ->label('Lokasi Masuk')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('latlon_out')
                    ->label('Lokasi Keluar')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->relationship('user', 'name')
                    ->label('Pengguna'),
                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    }),
                Tables\Filters\TernaryFilter::make('is_late')
                    ->label('Status Keterlambatan')
                    ->trueLabel('Telat')
                    ->falseLabel('Tepat Waktu')
                    ->nullable()
                    ->queries(
                        true: fn (Builder $query) => $query->where('is_late', true),
                        false: fn (Builder $query) => $query->where('is_late', false),
                        blank: fn (Builder $query) => $query, 
                    ),
                Tables\Filters\TernaryFilter::make('is_transfer_day')
                    ->label('Hari Transfer')
                    ->trueLabel('Ya')
                    ->falseLabel('Tidak')
                    ->queries(
                        true: fn (Builder $query) => $query->where('is_transfer_day', true),
                        false: fn (Builder $query) => $query->where('is_transfer_day', false),
                        blank: fn (Builder $query) => $query,
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
            RelationManagers\UserRelationManager::make(),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttendances::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
            'view' => Pages\ViewAttendance::route('/{record}'),
            'edit' => Pages\EditAttendance::route('/{record}/edit'),
        ];
    }
}