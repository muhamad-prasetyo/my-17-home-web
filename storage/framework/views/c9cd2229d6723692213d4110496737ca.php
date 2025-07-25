<?php
    $record = $getRecord();
?>
<?php if($record->latitude && $record->longitude): ?>
    <?php
        $lat = $record->latitude;
        $lng = $record->longitude;
        $url = "https://maps.google.com/?q={$lat},{$lng}";
        $osm = "https://staticmap.openstreetmap.de/staticmap.php?center={$lat},{$lng}&zoom=15&size=200x120&markers={$lat},{$lng},red-pushpin";
    ?>
    <a href="<?php echo e($url); ?>" target="_blank">
        <img src="<?php echo e($osm); ?>" alt="map" style="border-radius:8px;width:120px;height:80px;object-fit:cover;display:block;margin:auto;" />
    </a>
<?php else: ?>
    -
<?php endif; ?> <?php /**PATH /Users/muhamadprasetyo/Project-bakmi/absensi-bakmi/effiwork17-lastupdate/laravel-absensi-backend-master/resources/views/filament/components/fake-gps-map.blade.php ENDPATH**/ ?>