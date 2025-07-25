<!DOCTYPE html>
<html>
<head>
    <title>Rekap Absensi <?php echo e($user->name); ?></title>
    <style>
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 4px; font-size: 12px; }
        th { background: #eee; }
    </style>
</head>
<body>
    <h2>Rekap Absensi: <?php echo e($user->name); ?></h2>
    <?php if($month && $year): ?>
        <p>Bulan: <?php echo e($month); ?>/<?php echo e($year); ?></p>
    <?php endif; ?>
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
            <?php $__currentLoopData = $attendances; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                <td><?php echo e(isset($a['date']) ? \Carbon\Carbon::parse($a['date'])->isoFormat('D MMMM YYYY') : '-'); ?></td>
                <td>
                    <?php if(isset($a['status']) && ($a['status'] === 'alfa' || $a['status'] === 'libur')): ?>
                        Libur
                    <?php else: ?>
                        <?php echo e($a['time_in'] ?? '-'); ?>

                    <?php endif; ?>
                </td>
                <td>
                    <?php if(isset($a['status']) && ($a['status'] === 'alfa' || $a['status'] === 'libur')): ?>
                        Libur
                    <?php else: ?>
                        <?php echo e($a['time_out'] ?? '-'); ?>

                    <?php endif; ?>
                </td>
                <td><?php echo e(isset($a['status']) ? ucfirst($a['status']) : '-'); ?></td>
                <td><?php echo e($a['attendance_type'] ?? '-'); ?></td>
                <td>
                    <?php if(isset($a['is_late'])): ?>
                        <?php echo e($a['is_late'] ? 'Ya' : 'Tidak'); ?>

                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
                <td>
                    <?php if(isset($a['is_transfer_day']) && $a['is_transfer_day']): ?>
                        <?php echo e($a['transfer_status'] ?? '-'); ?>

                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
                <td><?php echo e((isset($a['is_transfer_day']) && $a['is_transfer_day']) ? ($a['source_time_in'] ?? '-') : '-'); ?></td>
                <td><?php echo e((isset($a['is_transfer_day']) && $a['is_transfer_day']) ? ($a['source_time_out'] ?? '-') : '-'); ?></td>
                <td><?php echo e((isset($a['is_transfer_day']) && $a['is_transfer_day']) ? ($a['destination_time_in'] ?? '-') : '-'); ?></td>
                <td><?php echo e((isset($a['is_transfer_day']) && $a['is_transfer_day']) ? ($a['destination_time_out'] ?? '-') : '-'); ?></td>
                <td>
                    <?php if(isset($a['status']) && ($a['status'] === 'alfa' || $a['status'] === 'libur')): ?>
                        Libur
                    <?php else: ?>
                        <?php echo e($a['total_hours'] ?? '-'); ?>

                    <?php endif; ?>
                </td>
                <td><?php echo e($a['description'] ?? ''); ?></td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>
</body>
</html> <?php /**PATH /Users/muhamadprasetyo/Project-bakmi/absensi-bakmi/effiwork17-lastupdate/laravel-absensi-backend-master/resources/views/exports/attendance-data-pdf.blade.php ENDPATH**/ ?>