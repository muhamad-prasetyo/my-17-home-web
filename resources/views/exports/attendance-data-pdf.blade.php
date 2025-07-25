<!DOCTYPE html>
<html>
<head>
    <title>Rekap Absensi {{ $user->name }}</title>
    <style>
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 4px; font-size: 12px; }
        th { background: #eee; }
    </style>
</head>
<body>
    <h2>Rekap Absensi: {{ $user->name }}</h2>
    @if($month && $year)
        <p>Bulan: {{ $month }}/{{ $year }}</p>
    @endif
    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Jam Masuk</th>
                <th>Jam Keluar</th>
                <th>Status</th>
                <th>Tipe</th>
                <th>Terlambat</th>
                <th>Status Transfer</th>
                <th>Jam Masuk Asal</th>
                <th>Jam Keluar Asal</th>
                <th>Jam Masuk Tujuan</th>
                <th>Jam Keluar Tujuan</th>
                <th>Total Jam Kerja</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($attendances as $a)
            <tr>
                <td>{{ isset($a['date']) ? \Carbon\Carbon::parse($a['date'])->isoFormat('D MMMM YYYY') : '-' }}</td>
                <td>
                    @if(isset($a['status']) && ($a['status'] === 'alfa' || $a['status'] === 'libur'))
                        Libur
                    @else
                        {{ $a['time_in'] ?? '-' }}
                    @endif
                </td>
                <td>
                    @if(isset($a['status']) && ($a['status'] === 'alfa' || $a['status'] === 'libur'))
                        Libur
                    @else
                        {{ $a['time_out'] ?? '-' }}
                    @endif
                </td>
                <td>{{ isset($a['status']) ? ucfirst($a['status']) : '-' }}</td>
                <td>{{ $a['attendance_type'] ?? '-' }}</td>
                <td>
                    @if(isset($a['is_late']))
                        {{ $a['is_late'] ? 'Ya' : 'Tidak' }}
                    @else
                        -
                    @endif
                </td>
                <td>
                    @if(isset($a['is_transfer_day']) && $a['is_transfer_day'])
                        {{ $a['transfer_status'] ?? '-' }}
                    @else
                        -
                    @endif
                </td>
                <td>{{ (isset($a['is_transfer_day']) && $a['is_transfer_day']) ? ($a['source_time_in'] ?? '-') : '-' }}</td>
                <td>{{ (isset($a['is_transfer_day']) && $a['is_transfer_day']) ? ($a['source_time_out'] ?? '-') : '-' }}</td>
                <td>{{ (isset($a['is_transfer_day']) && $a['is_transfer_day']) ? ($a['destination_time_in'] ?? '-') : '-' }}</td>
                <td>{{ (isset($a['is_transfer_day']) && $a['is_transfer_day']) ? ($a['destination_time_out'] ?? '-') : '-' }}</td>
                <td>
                    @if(isset($a['status']) && ($a['status'] === 'alfa' || $a['status'] === 'libur'))
                        Libur
                    @else
                        {{ $a['total_hours'] ?? '-' }}
                    @endif
                </td>
                <td>{{ $a['description'] ?? '' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html> 