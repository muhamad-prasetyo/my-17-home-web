<button
    <?php echo e($attributes
            ->merge([
                'type' => 'button',
            ], escape: false)
            ->class(['fi-fo-rich-editor-toolbar-btn flex h-8 min-w-[theme(spacing.8)] cursor-pointer items-center justify-center rounded-lg text-sm font-semibold text-gray-700 transition duration-75 hover:bg-gray-50 focus-visible:bg-gray-50 dark:text-gray-200 dark:hover:bg-white/5 dark:focus-visible:bg-white/5 [&.trix-active]:bg-gray-50 [&.trix-active]:text-primary-600 dark:[&.trix-active]:bg-white/5 dark:[&.trix-active]:text-primary-400'])); ?>

>
    <?php echo e($slot); ?>

</button>
<?php /**PATH /Users/muhamadprasetyo/Project-bakmi/absensi-bakmi/effiwork17-lastupdate/laravel-absensi-backend-master/vendor/filament/forms/src/../resources/views/components/rich-editor/toolbar/button.blade.php ENDPATH**/ ?>