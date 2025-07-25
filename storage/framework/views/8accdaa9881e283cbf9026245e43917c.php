<!DOCTYPE html>
<html>
<head>
    <title>Laporan Absensi</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333;
        }
        .container {
            width: 100%;
            margin: 0 auto;
        }
        .header, .footer {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0;
            font-size: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .status-onsite { background-color: #e8f5e9; }
        .status-remote { background-color: #e3f2fd; }
        .status-leave, .status-sick { background-color: #fffde7; }
        .status-holiday, .status-libur { background-color: #ffebee; }
        .status-alfa { background-color: #f5f5f5; color: #d32f2f; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Laporan Absensi Bulanan</h1>
            <p>Nama: <?php echo e($user->name); ?></p>
            <p>Periode: <?php echo e(\Carbon\Carbon::create()->month($month)->year($year)->isoFormat('MMMM YYYY')); ?></p>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Hari</th>
                    <th>Status</th>
                    <th>Jam Masuk</th>
                    <th>Jam Pulang</th>
                    <th>Total Jam Kerja</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $attendances; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="status-<?php echo e(strtolower($item['status'])); ?>">
                        <td><?php echo e(\Carbon\Carbon::parse($item['date'])->isoFormat('DD MMMM YYYY')); ?></td>
                        <td><?php echo e(\Carbon\Carbon::parse($item['date'])->isoFormat('dddd')); ?></td>
                        <td><?php echo e(ucfirst($item['status'])); ?></td>
                        <td><?php echo e($item['time_in'] ?? '-'); ?></td>
                        <td><?php echo e($item['time_out'] ?? '-'); ?></td>
                        <td>
                            <?php if(!empty($item['time_in']) && !empty($item['time_out'])): ?>
                                <?php echo e(\Carbon\Carbon::parse($item['time_in'])->diff(\Carbon\Carbon::parse($item['time_out']))->format('%H jam %i menit')); ?>

                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="6" style="text-align: center;">Tidak ada data absensi untuk periode ini.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="footer">
            <p>Laporan ini dibuat secara otomatis pada <?php echo e(now()->isoFormat('DD MMMM YYYY HH:mm')); ?></p>
        </div>
    </div>
</body>
</html> 
<?php /**PATH /Users/muhamadprasetyo/Project-bakmi/absensi-bakmi/effiwork17-lastupdate/laravel-absensi-backend-master/resources/views/reports/attendance_pdf.blade.php ENDPATH**/ ?>