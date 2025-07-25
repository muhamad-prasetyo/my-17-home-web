<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        .header { text-align: center; font-size: 16px; font-weight: bold; margin-bottom: 10px; }
        .logo { float: left; width: 40px; height: auto; margin-bottom: 10px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 30px; table-layout: fixed; }
        .table th, .table td { border: 1px solid #333; padding: 2px 2px; word-break: break-word; }
        .table th { background: #eee; }
        .footer { margin-top: 20px; font-size: 9px; font-style: italic; }
    </style>
</head>
<body>
    <div>
        <img src="<?php echo e(public_path('LOGO-PDF-1.png')); ?>" class="logo">
        <div class="header">PT.Sugi Boga Nusantara</div>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>Nama</th>
                <th>Tanggal</th>
                <th>Jam Masuk</th>
                <th>Jam Keluar</th>
                <th>Total Jam Kerja</th>
                <th>Status Transfer</th>
                <th>Hari Transfer</th>
                <th>Kantor Asal</th>
                <th>Kantor Tujuan</th>
                <th>Status Kehadiran</th>
                <th>Status Keterlambatan</th>
                <th>Durasi Telat</th>
                <th>Lokasi Masuk</th>
                <th>Lokasi Keluar</th>
                <th>Dibuat</th>
            </tr>
        </thead>
        <tbody>
            <?php $__currentLoopData = $attendances; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $attendance): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                <td><?php echo e($attendance->user->name ?? ''); ?></td>
                <td><?php echo e($attendance->date); ?></td>
                <td>
                    <?php if(isset($attendance->status_attendance) && $attendance->status_attendance === 'leave'): ?>
                        CUTI
                    <?php elseif($attendance->is_transfer_day): ?>
                        Asal: <?php echo e($attendance->source_time_in ?? '-'); ?> | Tujuan: <?php echo e($attendance->destination_time_in ?? '-'); ?>

                    <?php else: ?>
                        <?php echo e($attendance->time_in ?? '-'); ?>

                    <?php endif; ?>
                </td>
                <td>
                    <?php if(isset($attendance->status_attendance) && $attendance->status_attendance === 'leave'): ?>
                        CUTI
                    <?php elseif($attendance->is_transfer_day): ?>
                        Asal: <?php echo e($attendance->source_time_out ?? '-'); ?> | Tujuan: <?php echo e($attendance->destination_time_out ?? '-'); ?>

                    <?php else: ?>
                        <?php echo e($attendance->time_out ?? '-'); ?>

                    <?php endif; ?>
                </td>
                <td>
                    <?php if(isset($attendance->status_attendance) && $attendance->status_attendance === 'leave'): ?>
                        CUTI
                    <?php else: ?>
                        <?php
                            $isTransfer = $attendance->is_transfer_day;
                            if ($isTransfer && $attendance->source_time_in && $attendance->destination_time_out) {
                                $in = \Carbon\Carbon::parse($attendance->source_time_in);
                                $out = \Carbon\Carbon::parse($attendance->destination_time_out);
                                $diff = $out->diff($in);
                                echo $diff->h + ($diff->days * 24) . ' jam ' . $diff->i . ' menit';
                            } elseif (!$isTransfer && $attendance->time_in && $attendance->time_out) {
                                $in = \Carbon\Carbon::parse($attendance->time_in);
                                $out = \Carbon\Carbon::parse($attendance->time_out);
                                $diff = $out->diff($in);
                                echo $diff->h + ($diff->days * 24) . ' jam ' . $diff->i . ' menit';
                            } else {
                                echo '-';
                            }
                        ?>
                    <?php endif; ?>
                </td>
                <td><?php echo e($attendance->is_transfer_day ? ($attendance->transfer_status ?? 'Transfer') : 'Tidak'); ?></td>
                <td><?php echo e($attendance->is_transfer_day ? 'Ya' : 'Tidak'); ?></td>
                <td><?php echo e($attendance->sourceOffice->name ?? ''); ?></td>
                <td><?php echo e($attendance->destinationOffice->name ?? ''); ?></td>
                <td><?php echo e($attendance->status_attendance); ?></td>
                <td><?php echo e($attendance->is_late ? 'Terlambat' : 'Tepat Waktu'); ?></td>
                <td><?php echo e($attendance->late_duration); ?></td>
                <td><?php echo e($attendance->latlon_in); ?></td>
                <td><?php echo e($attendance->latlon_out); ?></td>
                <td><?php echo e($attendance->created_at); ?></td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>
    <div class="footer">
        Data diunduh dari sistem absensi PT.Sugi Boga Nusantara pada <?php echo e(now()->format('d-m-Y H:i:s')); ?>

    </div>
</body>
</html> <?php /**PATH /Users/muhamadprasetyo/Project-bakmi/absensi-bakmi/effiwork17-lastupdate/laravel-absensi-backend-master/resources/views/exports/attendances_pdf.blade.php ENDPATH**/ ?>