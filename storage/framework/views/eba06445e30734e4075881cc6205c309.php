<?php if (isset($component)) { $__componentOriginal511d4862ff04963c3c16115c05a86a9d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal511d4862ff04963c3c16115c05a86a9d = $attributes; } ?>
<?php $component = Illuminate\View\DynamicComponent::resolve(['component' => $getFieldWrapperView()] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dynamic-component'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\DynamicComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['field' => $field,'id' => $getId(),'label' => $getLabel(),'label-sr-only' => $isLabelHidden(),'helper-text' => $getHelperText(),'hint' => $getHint(),'required' => $isRequired(),'state-path' => $getStatePath()]); ?>
    <div
        x-data="async () => {
            <?php if($hasCss()): ?>
            if(!document.getElementById('map-picker-css')){
                const link  = document.createElement('link');
                link.id   = 'map-picker-css';
                link.rel  = 'stylesheet';
                link.type = 'text/css';
                link.href = '<?php echo e($cssUrl()); ?>';
                link.media = 'all';
                document.head.appendChild(link);
            }
            <?php endif; ?>
        <?php if($hasJs()): ?>
            if(!document.getElementById('map-picker-js')){
                const script = document.createElement('script');
                script.id   = 'map-picker-js';
                script.src = '<?php echo e($jsUrl()); ?>';
                document.head.appendChild(script);
            }
            <?php endif; ?>
            do {
                await (new Promise(resolve => setTimeout(resolve, 100)));
            } while (window.mapPicker === undefined);
            const m = mapPicker($wire, <?php echo e($getMapConfig()); ?>);
            m.attach($refs.map);
        }"
        wire:ignore>
        <div
            x-ref="map"
            class="w-full" style="min-height: 30vh; z-index: 1 !important;">
        </div>
    </div>
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal511d4862ff04963c3c16115c05a86a9d)): ?>
<?php $attributes = $__attributesOriginal511d4862ff04963c3c16115c05a86a9d; ?>
<?php unset($__attributesOriginal511d4862ff04963c3c16115c05a86a9d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal511d4862ff04963c3c16115c05a86a9d)): ?>
<?php $component = $__componentOriginal511d4862ff04963c3c16115c05a86a9d; ?>
<?php unset($__componentOriginal511d4862ff04963c3c16115c05a86a9d); ?>
<?php endif; ?>
<?php /**PATH /Users/muhamadprasetyo/Project-bakmi/absensi-bakmi/effiwork17-lastupdate/laravel-absensi-backend-master/vendor/humaidem/filament-map-picker/resources/views/fields/osm-map-picker.blade.php ENDPATH**/ ?>