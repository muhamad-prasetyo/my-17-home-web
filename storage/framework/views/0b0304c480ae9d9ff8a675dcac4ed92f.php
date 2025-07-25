<div style="padding: 1rem;">
    <h3>User: <?php echo e($user->name); ?></h3>
    <p>Email: <?php echo e($user->email); ?></p>
    <p>Device: <?php echo e($record->device_info); ?></p>
    <p>IP: <?php echo e($record->ip_address); ?></p>
    <p>Lokasi: <?php echo e($record->latitude); ?>, <?php echo e($record->longitude); ?></p>
    <p>Waktu Deteksi: <?php echo e($record->detected_at); ?></p>
    <p>Total Pelanggaran Fake GPS: <b><?php echo e($count); ?></b></p>
    <p>Status: <b style="color:<?php echo e($banned ? 'red' : 'green'); ?>"><?php echo e($banned ? 'BANNED' : 'ACTIVE'); ?></b></p>
    <?php if($count >= 3 && !$banned): ?>
        <p style="color:red;font-weight:bold;">User ini sudah 3x fake GPS. Klik tombol Ban User untuk blokir otomatis.</p>
    <?php endif; ?>
</div> <?php /**PATH /Users/muhamadprasetyo/Project-bakmi/absensi-bakmi/effiwork17-lastupdate/laravel-absensi-backend-master/resources/views/filament/components/fake-gps-detail.blade.php ENDPATH**/ ?>