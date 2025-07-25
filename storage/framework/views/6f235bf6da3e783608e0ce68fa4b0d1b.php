<?php if (isset($component)) { $__componentOriginald489e48d6214ecaf87e4b6a8ce684ad1 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald489e48d6214ecaf87e4b6a8ce684ad1 = $attributes; } ?>
<?php $component = Filament\View\LegacyComponents\Widget::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::widget'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Filament\View\LegacyComponents\Widget::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
    <?php if (isset($component)) { $__componentOriginal9b945b32438afb742355861768089b04 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9b945b32438afb742355861768089b04 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.card','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
        <h2 class="text-lg font-bold tracking-tight">Present Employees</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php $__empty_1 = true; $__currentLoopData = $this->presentEmployees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $employee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="flex items-center space-x-3">
                    <img src="<?php echo e($employee->avatar_url); ?>" alt="<?php echo e($employee->name); ?>" class="w-10 h-10 rounded-full object-cover">
                    <div>
                        <p class="text-sm font-medium text-gray-900"><?php echo e($employee->name); ?></p>
                        <p class="text-xs text-gray-500"><?php echo e($employee->email); ?></p>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <p>No employees present today.</p>
            <?php endif; ?>
        </div>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9b945b32438afb742355861768089b04)): ?>
<?php $attributes = $__attributesOriginal9b945b32438afb742355861768089b04; ?>
<?php unset($__attributesOriginal9b945b32438afb742355861768089b04); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9b945b32438afb742355861768089b04)): ?>
<?php $component = $__componentOriginal9b945b32438afb742355861768089b04; ?>
<?php unset($__componentOriginal9b945b32438afb742355861768089b04); ?>
<?php endif; ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald489e48d6214ecaf87e4b6a8ce684ad1)): ?>
<?php $attributes = $__attributesOriginald489e48d6214ecaf87e4b6a8ce684ad1; ?>
<?php unset($__attributesOriginald489e48d6214ecaf87e4b6a8ce684ad1); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald489e48d6214ecaf87e4b6a8ce684ad1)): ?>
<?php $component = $__componentOriginald489e48d6214ecaf87e4b6a8ce684ad1; ?>
<?php unset($__componentOriginald489e48d6214ecaf87e4b6a8ce684ad1); ?>
<?php endif; ?> <?php /**PATH /Users/muhamadprasetyo/Project-bakmi/absensi-bakmi/effiwork17-lastupdate/laravel-absensi-backend-master/resources/views/filament/widgets/present-employees-widget.blade.php ENDPATH**/ ?>