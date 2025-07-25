<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class AttendancesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithEvents, WithTitle
{
    protected $attendances;

    public function __construct(Collection $attendances)
    {
        $this->attendances = $attendances;
    }

    public function collection()
    {
        return $this->attendances;
    }

    public function headings(): array
    {
        // Header field akan muncul di baris 4
        return [
            'Nama',
            'Tanggal',
            'Jam Masuk',
            'Jam Keluar',
            'Total Jam Kerja',
            'Status Transfer',
            'Hari Transfer',
            'Kantor Asal',
            'Kantor Tujuan',
            'Status Kehadiran',
            'Status Keterlambatan',
            'Durasi Telat',
            'Lokasi Masuk',
            'Lokasi Keluar',
            'Dibuat',
        ];
    }

    public function map($attendance): array
    {
        // Logic Jam Masuk & Keluar (gabungan)
        if ($attendance->status_attendance === 'leave') {
            $jamMasuk = 'CUTI';
            $jamKeluar = 'CUTI';
        } else if ($attendance->is_transfer_day) {
            $jamMasuk = 'Asal: ' . ($attendance->source_time_in ?? '-') . ' | Tujuan: ' . ($attendance->destination_time_in ?? '-');
            $jamKeluar = 'Asal: ' . ($attendance->source_time_out ?? '-') . ' | Tujuan: ' . ($attendance->destination_time_out ?? '-');
        } else {
            $jamMasuk = $attendance->time_in ?? '-';
            $jamKeluar = $attendance->time_out ?? '-';
        }

        // Logic Total Jam Kerja (sama seperti di AttendanceResource)
        $totalJamKerja = '-';
        $isTransfer = ($attendance->is_transfer_day === true || $attendance->is_transfer_day === 1 || $attendance->is_transfer_day === '1');
        if ($isTransfer) {
            if (!empty($attendance->source_time_in) && !empty($attendance->destination_time_out)) {
                try {
                    $timeIn = \Carbon\Carbon::parse($attendance->source_time_in);
                    $timeOut = \Carbon\Carbon::parse($attendance->destination_time_out);
                    if (!$timeOut->lt($timeIn)) {
                        $duration = $timeOut->diff($timeIn);
                        $hours = $duration->h + ($duration->days * 24);
                        $minutes = $duration->i;
                        $totalJamKerja = $hours . ' jam ' . $minutes . ' menit';
                    }
                } catch (\Exception $e) {
                    $totalJamKerja = 'Err';
                }
            }
        } else {
            if (!empty($attendance->time_in) && !empty($attendance->time_out)) {
                try {
                    $timeIn = \Carbon\Carbon::parse($attendance->time_in);
                    $timeOut = \Carbon\Carbon::parse($attendance->time_out);
                    if (!$timeOut->lt($timeIn)) {
                        $duration = $timeOut->diff($timeIn);
                        $hours = $duration->h + ($duration->days * 24);
                        $minutes = $duration->i;
                        $totalJamKerja = $hours . ' jam ' . $minutes . ' menit';
                    }
                } catch (\Exception $e) {
                    $totalJamKerja = 'Err';
                }
            }
        }

        // Mapping Status Transfer
        $statusMap = [
            'pending' => 'Menunggu',
            'checked_in_at_source' => 'Check-In di Kantor Asal',
            'checked_out_from_source' => 'Check-Out dari Kantor Asal',
            'checked_in_at_destination' => 'Check-In di Kantor Tujuan',
            'completed' => 'Selesai',
        ];
        $statusTransfer = $attendance->is_transfer_day ? ($statusMap[$attendance->transfer_status] ?? 'Transfer') : 'Tidak';
        $hariTransfer = $attendance->is_transfer_day ? 'Ya' : 'Tidak';

        // Kantor Asal & Tujuan
        $kantorAsal = $attendance->sourceOffice->name ?? '';
        $kantorTujuan = $attendance->destinationOffice->name ?? '';

        return [
            $attendance->user->name ?? '',
            $attendance->date,
            $jamMasuk,
            $jamKeluar,
            $totalJamKerja,
            $statusTransfer,
            $hariTransfer,
            $kantorAsal,
            $kantorTujuan,
            $attendance->status_attendance,
            $attendance->is_late ? 'Terlambat' : 'Tepat Waktu',
            $attendance->late_duration,
            $attendance->latlon_in,
            $attendance->latlon_out,
            $attendance->created_at,
        ];
    }

    public function title(): string
    {
        // Nama sheet dinamis, misal: Absensi_2025_06
        $first = $this->attendances->first();
        $month = $first ? date('Y_m', strtotime($first->date)) : date('Y_m');
        return 'Absensi_' . $month;
    }

    public function styles(Worksheet $sheet)
    {
        // Header bold & background
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E0E0E0'],
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastCol = $sheet->getHighestColumn();
                $colCount = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($lastCol);

                // --- LOGO KIRI ---
                $drawingLeft = new Drawing();
                $drawingLeft->setName('Logo Kiri');
                $drawingLeft->setPath(public_path('LOGO-PDF-1.png'));
                $drawingLeft->setHeight(60);
                $drawingLeft->setCoordinates('A1');
                $drawingLeft->setWorksheet($sheet);

                // --- VISUAL: LEBARKAN KOLOM A & TINGGI BARIS 1 ---
                $sheet->getColumnDimension('A')->setWidth(20);
                $sheet->getRowDimension(1)->setRowHeight(60);
                $sheet->getRowDimension(2)->setRowHeight(20); // spasi vertikal

                // --- NAMA PERUSAHAAN DI TENGAH (MERGE B1 SAMPAI KOLOM KE-2 DARI KANAN) ---
                $mergeStart = 'B';
                $mergeEnd = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colCount-1); // kolom ke-2 dari kanan
                $sheet->setCellValue($mergeStart.'1', 'PT.Sugi Boga Nusantara');
                $sheet->mergeCells($mergeStart.'1:'.$mergeEnd.'1');
                $sheet->getStyle($mergeStart.'1:'.$mergeEnd.'1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle($mergeStart.'1:'.$mergeEnd.'1')->getFont()->setBold(true)->setSize(18);

                // Baris 2 kosong (tinggi diperbesar)
                // Header kolom tetap di baris 3, data mulai baris 4

                // Auto-size kolom selain A
                foreach (range('B', $lastCol) as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
                // Border semua cell (mulai baris 3)
                $highestRow = $sheet->getHighestRow();
                $sheet->getStyle('A3:' . $lastCol . $highestRow)
                    ->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                // Header bold & background (baris 3)
                $sheet->getStyle('A3:'.$lastCol.'3')->getFont()->setBold(true);
                $sheet->getStyle('A3:'.$lastCol.'3')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('E0E0E0');
                // Format tanggal & jam (kolom B, Q)
                $sheet->getStyle('B4:B'.$highestRow)
                    ->getNumberFormat()->setFormatCode('DD-MM-YYYY');
                $sheet->getStyle('Q4:Q'.$highestRow)
                    ->getNumberFormat()->setFormatCode('DD-MM-YYYY HH:MM:SS');
                // --- FOOTER ---
                $footerRow = $highestRow + 2;
                $sheet->setCellValue('A'.$footerRow, 'Data diunduh dari sistem absensi PT.Sugi Boga Nusantara pada '.date('d-m-Y H:i:s'));
                $sheet->mergeCells('A'.$footerRow.':'.$lastCol.$footerRow);
                $sheet->getStyle('A'.$footerRow.':'.$lastCol.$footerRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle('A'.$footerRow.':'.$lastCol.$footerRow)->getFont()->setItalic(true)->setSize(10);
                // --- WARNA STATUS ---
                // Status Kehadiran (kolom J)
                for ($row = 4; $row <= $highestRow; $row++) {
                    $status = strtolower($sheet->getCell('J'.$row)->getValue());
                    $color = null;
                    if ($status === 'hadir' || $status === 'present' || $status === 'checked_out') $color = 'C6EFCE'; // hijau
                    elseif ($status === 'sakit') $color = 'FFF3CD'; // kuning
                    elseif ($status === 'izin') $color = 'D1E7FF'; // biru
                    elseif ($status === 'alpha') $color = 'F8D7DA'; // merah
                    if ($color) {
                        $sheet->getStyle('J'.$row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB($color);
                    }
                    // Status Keterlambatan (kolom K)
                    $late = strtolower($sheet->getCell('K'.$row)->getValue());
                    if ($late === 'terlambat') {
                        $sheet->getStyle('K'.$row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('F8D7DA'); // merah muda
                    } elseif ($late === 'tepat waktu') {
                        $sheet->getStyle('K'.$row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('C6EFCE'); // hijau muda
                    }
                }
            }
        ];
    }
}