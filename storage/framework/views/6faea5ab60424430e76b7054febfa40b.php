<?php
    use Filament\Support\Facades\FilamentView;
?>

<<<<<<< HEAD
<!--[if BLOCK]><![endif]--><?php if($this->hasUnsavedDataChangesAlert()): ?>
    <!--[if BLOCK]><![endif]--><?php if(FilamentView::hasSpaMode()): ?>
=======
<?php if($this->hasUnsavedDataChangesAlert()): ?>
    <?php if(FilamentView::hasSpaMode()): ?>
>>>>>>> 6ad12440d924f1a0aa1d26348cd63a38329565ff
            <?php
        $__scriptKey = '1759064701-0';
        ob_start();
    ?>
            <script>
                shouldPreventNavigation = () => {
                    if ($wire?.__instance?.effects?.redirect) {
                        return
                    }

                    return (
                        window.jsMd5(
                            JSON.stringify($wire.data).replace(/\\/g, ''),
                        ) !== $wire.savedDataHash
                    )
                }

                const showUnsavedChangesAlert = () => {
                    return confirm(<?php echo \Illuminate\Support\Js::from(__('filament-panels::unsaved-changes-alert.body'))->toHtml() ?>)
                }

                document.addEventListener('livewire:navigate', (event) => {
                    if (typeof window.Livewire.find('<?php echo e($_instance->getId()); ?>') !== 'undefined') {
                        if (!shouldPreventNavigation()) {
                            return
                        }

                        if (showUnsavedChangesAlert()) {
                            return
                        }

                        event.preventDefault()
                    }
                })

                window.addEventListener('beforeunload', (event) => {
                    if (!shouldPreventNavigation()) {
                        return
                    }

                    event.preventDefault()
                    event.returnValue = true
                })
            </script>
            <?php
        $__output = ob_get_clean();

        \Livewire\store($this)->push('scripts', $__output, $__scriptKey)
    ?>
    <?php else: ?>
            <?php
        $__scriptKey = '1759064701-1';
        ob_start();
    ?>
            <script>
                window.addEventListener('beforeunload', (event) => {
                    if (
                        window.jsMd5(
                            JSON.stringify($wire.data).replace(/\\/g, ''),
                        ) === $wire.savedDataHash ||
                        $wire?.__instance?.effects?.redirect
                    ) {
                        return
                    }

                    event.preventDefault()
                    event.returnValue = true
                })
            </script>
            <?php
        $__output = ob_get_clean();

        \Livewire\store($this)->push('scripts', $__output, $__scriptKey)
    ?>
<<<<<<< HEAD
    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
<?php endif; ?><!--[if ENDBLOCK]><![endif]-->
=======
    <?php endif; ?>
<?php endif; ?>
>>>>>>> 6ad12440d924f1a0aa1d26348cd63a38329565ff
<?php /**PATH /Users/muhamadprasetyo/Project-bakmi/absensi-bakmi/effiwork17-lastupdate/laravel-absensi-backend-master/resources/views/vendor/filament-panels/components/page/unsaved-data-changes-alert.blade.php ENDPATH**/ ?>