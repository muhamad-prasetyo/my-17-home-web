<div
    x-data="{ open: false }"
    @click.away="open = false"
    class="relative order-first"
>
    <button
        type="button"
        @click="open = ! open; $wire.getUnreadNotifications()"
        class="relative flex items-center justify-center w-12 h-12 text-gray-500 rounded-full hover:bg-primary-100 focus:outline-none focus:bg-primary-100 transition"
    >
        <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-bell'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-7 h-7']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $attributes = $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $component = $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
        <?php if($unreadNotificationsCount > 0): ?>
            <span class="absolute -top-1 -right-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-primary-600 rounded-full shadow z-10"><?php echo e($unreadNotificationsCount); ?></span>
        <?php endif; ?>
    </button>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute right-0 z-50 mt-2 origin-top-right rounded-lg bg-white shadow-2xl ring-1 ring-black ring-opacity-5 focus:outline-none"
        style="width: 600px; min-width: 400px; max-width: 90vw;"
        role="menu"
        aria-orientation="vertical"
        aria-labelledby="menu-button"
        tabindex="-1"
    >
        <div class="py-2" role="none">
            <?php $__empty_1 = true; $__currentLoopData = $unreadNotifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notification): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="flex items-center gap-3 px-6 py-4 border-b last:border-b-0 cursor-pointer <?php echo e(!$notification->read_at ? 'bg-blue-50 hover:bg-blue-100' : 'hover:bg-gray-50'); ?> transition">
                    <div class="flex-shrink-0">
                        <?php if(isset($notification->data['avatar'])): ?>
                            <img src="<?php echo e($notification->data['avatar']); ?>" class="w-10 h-10 rounded-full object-cover" alt="avatar">
                        <?php else: ?>
                            <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center">
                                <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-bell'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-6 h-6 text-blue-500']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $attributes = $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $component = $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold text-gray-900 text-lg leading-tight">
                            <?php echo e($notification->data['title'] ?? 'Notifikasi'); ?>

                        </div>
                        <div class="text-gray-700 text-base mt-0.5 leading-snug">
                            <?php echo e($notification->data['body'] ?? 'Tidak ada detail.'); ?>

                        </div>
                        <div class="text-xs text-gray-400 mt-1">
                            <?php echo e($notification->created_at->diffForHumans()); ?>

                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <p class="px-6 py-4 text-base text-gray-700 text-center">Tidak ada notifikasi baru.</p>
            <?php endif; ?>
            <?php if($unreadNotificationsCount > 0): ?>
                <div class="border-t border-gray-100 mt-2 pt-2 px-6">
                    <button type="button" wire:click="markAllAsRead" class="w-full text-center text-base text-primary-600 hover:underline font-semibold">
                        Tandai semua sudah dibaca
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div> <?php /**PATH /Users/muhamadprasetyo/Project-bakmi/absensi-bakmi/effiwork17-lastupdate/laravel-absensi-backend-master/resources/views/livewire/notification-bell.blade.php ENDPATH**/ ?>