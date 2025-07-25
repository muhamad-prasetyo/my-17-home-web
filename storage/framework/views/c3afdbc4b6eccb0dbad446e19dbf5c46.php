<div x-data="{ open: false }" class="relative">
    <!-- Bell Icon with Badge -->
    <button type="button" class="relative focus:outline-none" @click="open = !open">
        <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-bell'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-7 h-7 text-gray-400']); ?>
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
<<<<<<< HEAD
        <!--[if BLOCK]><![endif]--><?php if($unreadCount > 0): ?>
            <span class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-red-600 rounded-full"><?php echo e($unreadCount); ?></span>
        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
=======
        <?php if($unreadCount > 0): ?>
            <span class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-red-600 rounded-full"><?php echo e($unreadCount); ?></span>
        <?php endif; ?>
>>>>>>> 6ad12440d924f1a0aa1d26348cd63a38329565ff
    </button>

    <!-- Popover -->
    <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-full max-w-md bg-white dark:bg-gray-800 rounded-lg shadow-lg z-50 border border-gray-200 dark:border-gray-700" style="width: 28rem; min-width: 20rem; max-width: 90vw; display: none;">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <span class="font-semibold text-gray-900 dark:text-gray-100">Notifikasi</span>
            <button wire:click="markAllAsRead" class="text-xs text-blue-600 hover:underline">Tandai semua sudah dibaca</button>
        </div>
        <ul class="max-h-96 overflow-y-auto divide-y divide-gray-200 dark:divide-gray-700">
<<<<<<< HEAD
            <!--[if BLOCK]><![endif]--><?php $__empty_1 = true; $__currentLoopData = $notifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notification): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
=======
            <?php $__empty_1 = true; $__currentLoopData = $notifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notification): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
>>>>>>> 6ad12440d924f1a0aa1d26348cd63a38329565ff
                <?php
                    $data = $notification->data;
                    $type = $data['type'] ?? '';
                    $url = '';
                    switch ($type) {
                        case 'leave_request':
                            $url = $data['leave_request_id'] ? url('/admin/leave-requests/'.$data['leave_request_id']) : '#';
                            break;
                        case 'transfer_request':
                            $url = $data['transfer_request_id'] ? url('/admin/transfer-requests/'.$data['transfer_request_id']) : '#';
                            break;
                        case 'permission_request':
                            $url = $data['permission_id'] ? url('/admin/laporan-karyawan/'.$data['permission_id']) : '#';
                            break;
                        case 'attendance':
                            $url = $data['attendance_id'] ? url('/admin/attendances/'.$data['attendance_id']) : '#';
                            break;
                        default:
                            $url = '#';
                    }
                ?>
                <li>
                    <a href="<?php echo e($url); ?>" class="flex items-start px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        <div class="flex-shrink-0">
                            <img src="<?php echo e($data['avatar'] ?? asset('images/default_avatar.png')); ?>" class="w-10 h-10 rounded-full object-cover" />
                        </div>
                        <div class="ml-5 flex-1 min-w-0" style="margin-left: 1.5rem;">
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100"><?php echo e($data['title'] ?? 'Notifikasi'); ?></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1"><?php echo e($data['body'] ?? ''); ?></p>
                            <span class="text-xs text-gray-400 dark:text-gray-500"><?php echo e($notification->created_at->diffForHumans()); ?></span>
                        </div>
<<<<<<< HEAD
                        <!--[if BLOCK]><![endif]--><?php if($notification->read_at === null): ?>
                            <span class="ml-2 w-2 h-2 bg-red-500 rounded-full"></span>
                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
=======
                        <?php if($notification->read_at === null): ?>
                            <span class="ml-2 w-2 h-2 bg-red-500 rounded-full"></span>
                        <?php endif; ?>
>>>>>>> 6ad12440d924f1a0aa1d26348cd63a38329565ff
                    </a>
                </li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <li class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">Tidak ada notifikasi</li>
<<<<<<< HEAD
            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
=======
            <?php endif; ?>
>>>>>>> 6ad12440d924f1a0aa1d26348cd63a38329565ff
        </ul>
        <div class="p-2 border-t border-gray-200 dark:border-gray-700 text-center">
            <a href="<?php echo e(url('/admin/notifications')); ?>" class="text-xs text-blue-600 hover:underline">Lihat semua notifikasi</a>
        </div>
    </div>
</div> <?php /**PATH /Users/muhamadprasetyo/Project-bakmi/absensi-bakmi/effiwork17-lastupdate/laravel-absensi-backend-master/resources/views/livewire/custom-notification-bell.blade.php ENDPATH**/ ?>