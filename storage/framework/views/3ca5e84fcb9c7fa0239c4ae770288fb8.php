<?php $__currentLoopData = $getActions(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $action): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <?php if($action->isVisible()): ?>
        <?php echo e($action); ?>

    <?php endif; ?>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php /**PATH /Users/muhamadprasetyo/Project-bakmi/absensi-bakmi/effiwork17-lastupdate/laravel-absensi-backend-master/vendor/filament/forms/resources/views/components/actions/action-container.blade.php ENDPATH**/ ?>