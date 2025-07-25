<?php

namespace App\Exports;

use App\Models\Attendance;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;
use App\Http\Controllers\Api\AttendanceController;

class AttendanceExport implements FromCollection, WithHeadings, WithMapping
{
    protected $userId;
    protected $month;
    protected $year;

    public function __construct($userId, $month, $year)
    {
        $this->userId = $userId;
        $this->month = $month;
        $this->year = $year;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        // Ambil data harian real (termasuk Alfa otomatis) dari logic API
        $controller = new AttendanceController();
        $data = $controller->getDetailedMonthlyAttendance($this->userId, $this->month, $this->year);
        // Ubah ke koleksi Laravel agar bisa diexport
        return collect($data);
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Tanggal',
            'Status',
            'Jam Masuk',
            'Jam Keluar',
            'Total Jam Kerja',
            'Keterangan',
        ];
    }

    /**
     * @param mixed $item
     * @return array
     */
    public function map($item): array
    {
        $isLibur = isset($item['status']) && ($item['status'] === 'libur');
        $isAlfa = isset($item['status']) && ($item['status'] === 'alfa');
        $isLeave = isset($item['status']) && ($item['status'] === 'leave');
        return [
            $item['date'],
            ucfirst($item['status']),
            ($isLibur || $isAlfa) ? 'Libur' : ($isLeave ? 'CUTI' : ($item['time_in'] ?? '-')),
            ($isLibur || $isAlfa) ? 'Libur' : ($isLeave ? 'CUTI' : ($item['time_out'] ?? '-')),
            ($isLibur || $isAlfa) ? 'Libur' : ($isLeave ? 'CUTI' : ($item['total_hours'] ?? '-')),
            $item['description'] ?? '',
        ];
    }

    private function formatStatus($status)
    {
        switch ($status) {
            case 'checked_in':
                return 'Hadir (Check-in)';
            case 'checked_out':
                return 'Hadir (Sudah Check-out)';
            case 'leave':
                return 'Cuti';
            case 'sick':
                return 'Sakit';
            case 'remote':
                return 'Remote';
            case 'holiday':
                return 'Libur';
            default:
                return ucfirst($status);
        }
    }
} 