<div>
    <div class="p-4 mb-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Legenda</h3>
        <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-gray-700 dark:text-gray-200">
            <div class="flex items-center gap-2">
                <span class="w-4 h-4 rounded-full" style="background-color: #22c55e;"></span>
                <span>Onsite</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-4 h-4 rounded-full" style="background-color: #3b82f6;"></span>
                <span>Remote</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-4 h-4 rounded-full" style="background-color: #facc15;"></span>
                <span>Leave</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-4 h-4 rounded-full" style="background-color: #ef4444;"></span>
                <span>Libur</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-4 h-4 rounded-full" style="background-color: #000000;"></span>
                <span>Alfa</span>
            </div>
        </div>
    </div>


    <!-- DEBUG: Tampilkan nilai userId -->
    <?php
        use Carbon\Carbon;
        use App\Filament\Widgets\AttendanceCalendarWidget;
        $userId = request('user_id');
        $month = request('month', Carbon::now()->month);
        $year = request('year', Carbon::now()->year);
        $btnClass = 'btn-export-data px-4 py-2 font-semibold rounded border transition';
        $btnClassBlue = 'btn-export-calendar px-4 py-2 font-semibold rounded border transition';
        if (empty($userId)) {
            $btnClass = 'btn-disabled px-4 py-2 font-semibold rounded border transition';
            $btnClassBlue = 'btn-disabled px-4 py-2 font-semibold rounded border transition';
        }
        // Judul bulan-tahun untuk export
        $bulanTahun = Carbon::create($year, $month, 1)->locale('id')->isoFormat('MMMM YYYY');
        $rekap = $userId ? AttendanceCalendarWidget::getMonthlyRecap($userId, $month, $year) : [
            'hadir' => 0,
            'remote' => 0,
            'leave' => 0,
            'libur' => 0,
            'alfa' => 0,
            'total' => 0,
        ];
    ?>
    <?php if($userId): ?>
    <div class="mb-4 p-3 rounded bg-gray-100 dark:bg-gray-800 flex flex-wrap gap-6 items-center">
        <span class="font-semibold">Rekap Bulan <?php echo e($bulanTahun); ?>:</span>
        <span>Hadir: <span class="font-bold text-green-600"><?php echo e($rekap['hadir']); ?></span></span>
        <span>Remote: <span class="font-bold text-blue-600"><?php echo e($rekap['remote']); ?></span></span>
        <span>Leave: <span class="font-bold text-yellow-600"><?php echo e($rekap['leave']); ?></span></span>
        <span>Libur: <span class="font-bold text-red-600"><?php echo e($rekap['libur']); ?></span></span>
        <span>Alfa: <span class="font-bold text-red-600"><?php echo e($rekap['alfa']); ?></span></span>
        <span>Total: <span class="font-bold text-blue-600"><?php echo e($rekap['total']); ?></span></span>
    </div>
    <?php endif; ?>
    <style>
    /* Tombol aktif Export Data (mode light) */
    .btn-export-data {
        background-color: #dc2626 !important; /* merah */
        color: #fff !important;
        border: 2px solid #991b1b !important;
        font-weight: bold;
    }
    /* Tombol aktif Export Kalender (mode light) */
    .btn-export-calendar {
        background-color: #2563eb !important; /* biru */
        color: #fff !important;
        border: 2px solid #1e40af !important;
        font-weight: bold;
    }
    .btn-disabled {
        background-color: #d1d5db !important;
        color: #222 !important;
        border-color: #bdbdbd !important;
        opacity: 1 !important;
        cursor: not-allowed !important;
        font-weight: bold !important;
        text-shadow: none !important;
    }
    .dark .btn-disabled {
        background-color: #374151 !important;
        color: #f87171 !important;
        border-color: #4b5563 !important;
        font-weight: bold !important;
        text-shadow: none;
    }
    /* Tombol aktif di dark mode tetap pakai style tailwind bawaan (bg-red-600, bg-blue-600) */
    /* Select2 search input di dark mode */
    .dark .select2-container--default .select2-search--dropdown .select2-search__field,
    body.dark .select2-container--default .select2-search--dropdown .select2-search__field {
        background-color: #1f2937 !important;
        color: #fff !important;
        border-color: #374151 !important;
    }
    </style>
    <div class="mb-4 flex flex-wrap items-end gap-4">
        <div style="max-width: 320px;">
            <form method="GET">
                <label for="user_id" class="block mb-1 font-medium">Cari Karyawan:</label>
                <select id="user_id" name="user_id" class="form-select w-full max-w-xs" style="width: 100%;" onchange="this.form.submit()">
                    <option value="">-- Pilih Karyawan --</option>
                    <?php $__currentLoopData = \App\Models\User::orderBy('name')->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($user->id); ?>" <?php echo e(request('user_id') == $user->id ? 'selected' : ''); ?>>
                            <?php echo e($user->name); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </form>
        </div>
        <div class="flex gap-2">
            <form action="<?php echo e(route('admin.export-attendance-data')); ?>" method="GET" target="_blank">
                <input type="hidden" name="user_id" value="<?php echo e($userId); ?>">
                <input type="hidden" name="month" value="<?php echo e($month); ?>">
                <input type="hidden" name="year" value="<?php echo e($year); ?>">
                <button type="submit" class="<?php echo e($btnClass); ?>">
                    Export PDF (Data)
                </button>
            </form>
            <button id="export-calendar-image" class="<?php echo e($btnClassBlue); ?>">
                Export PDF (Gambar Kalender)
            </button>
            <form id="calendar-image-form" action="<?php echo e(route('admin.export-attendance-calendar-image')); ?>" method="POST" target="_blank" style="display:none;">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="calendar_image" id="calendar_image_input">
                <input type="hidden" name="user_id" value="<?php echo e($userId); ?>">
            </form>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script>
    document.getElementById('export-calendar-image').onclick = function() {
        // Scroll ke atas agar area rekap pasti terlihat
        window.scrollTo({ top: 0, behavior: 'instant' });
        var area = document.getElementById('calendar-export-area');
        area.classList.add('force-light');
        setTimeout(function() {
            html2canvas(area, {
                width: 1100,
                windowWidth: 1200,
                backgroundColor: '#fff'
            }).then(function(canvas) {
                area.classList.remove('force-light');
                var imgData = canvas.toDataURL('image/png');
                document.getElementById('calendar_image_input').value = imgData;
                document.getElementById('calendar-image-form').submit();
            });
        }, 100);
    };
    </script>

    <!-- Wrapper untuk export gambar kalender dan rekap (hanya satu rekap di sini) -->
    <div id="calendar-export-area">
        <?php if($userId): ?>
        <div class="mb-4 p-4 rounded rekap-export flex flex-wrap gap-6 items-center" style="font-size:1.1em; margin-top:32px;">
            <span class="font-semibold">Rekap Bulan <?php echo e($bulanTahun); ?>:</span>
            <span>Hadir: <span style="color:#16a34a;font-weight:bold"><?php echo e($rekap['hadir']); ?></span></span>
            <span>Remote: <span style="color:#2563eb;font-weight:bold"><?php echo e($rekap['remote']); ?></span></span>
            <span>Leave: <span style="color:#facc15;font-weight:bold"><?php echo e($rekap['leave']); ?></span></span>
            <span>Libur: <span style="color:#ef4444;font-weight:bold"><?php echo e($rekap['libur']); ?></span></span>
            <span>Alfa: <span style="color:#000;font-weight:bold"><?php echo e($rekap['alfa']); ?></span></span>
            <span>Total: <span style="color:#111827;font-weight:bold"><?php echo e($rekap['total']); ?></span></span>
        </div>
        <?php endif; ?>
        <h3 style="text-align:center; font-size:1.5em; margin-bottom:8px;">
            <?php echo e($bulanTahun); ?>

        </h3>
        <?php if (isset($component)) { $__componentOriginal166a02a7c5ef5a9331faf66fa665c256 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal166a02a7c5ef5a9331faf66fa665c256 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament-panels::components.page.index','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament-panels::page'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
             
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal166a02a7c5ef5a9331faf66fa665c256)): ?>
<?php $attributes = $__attributesOriginal166a02a7c5ef5a9331faf66fa665c256; ?>
<?php unset($__attributesOriginal166a02a7c5ef5a9331faf66fa665c256); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal166a02a7c5ef5a9331faf66fa665c256)): ?>
<?php $component = $__componentOriginal166a02a7c5ef5a9331faf66fa665c256; ?>
<?php unset($__componentOriginal166a02a7c5ef5a9331faf66fa665c256); ?>
<?php endif; ?>
    </div>
    <style>
    .rekap-export {
        background: #f3f4f6 !important; /* abu-abu muda di light mode */
    }
    .dark .rekap-export {
        background: #1f2937 !important; /* abu-abu gelap di dark mode */
    }
    </style>

    <!-- Select2 CDN & Inisialisasi -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
      $(document).ready(function() {
        $('#user_id').select2({
          width: '100%',
          placeholder: '-- Pilih Karyawan --',
          allowClear: true
        });
        
        // Force calendar refresh when user changes
        $('#user_id').on('change', function() {
          console.log('User changed to:', $(this).val());
          
          // Add cache-busting parameter
          var currentUrl = new URL(window.location);
          currentUrl.searchParams.set('user_id', $(this).val());
          currentUrl.searchParams.set('_t', Date.now()); // Cache buster
          
          // Reload page with new parameters
          window.location.href = currentUrl.toString();
        });
      });
    </script>
    <style>
    /* Default (light mode) */
    .select2-container--default .select2-results__option {
        color: #111827;
        background-color: #fff;
    }
    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: #2563eb;
        color: #fff;
    }
    .select2-container--default .select2-selection--single {
        background-color: #fff;
        color: #111827;
        border-color: #d1d5db;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #111827;
    }
    .select2-dropdown {
        background-color: #fff !important;
        color: #111827 !important;
    }

    /* Dark mode (Tailwind/Filament: .dark class di <html> atau <body>) */
    .dark .select2-container--default .select2-results__option,
    body.dark .select2-container--default .select2-results__option {
        color: #fff;
        background-color: #1f2937;
    }
    .dark .select2-container--default .select2-results__option--highlighted[aria-selected],
    body.dark .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: #2563eb;
        color: #fff;
    }
    .dark .select2-container--default .select2-selection--single,
    body.dark .select2-container--default .select2-selection--single {
        background-color: #1f2937;
        color: #fff;
        border-color: #374151;
    }
    .dark .select2-container--default .select2-selection--single .select2-selection__rendered,
    body.dark .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #fff;
    }
    .dark .select2-dropdown,
    body.dark .select2-dropdown {
        background-color: #1f2937 !important;
        color: #fff !important;
    }
    </style>
    <script>
    // Blokir klik jika tombol punya class btn-disabled
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('button.btn-disabled').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            });
        });
    });
    </script>
</div>
<?php /**PATH /Users/muhamadprasetyo/Project-bakmi/absensi-bakmi/effiwork17-lastupdate/laravel-absensi-backend-master/resources/views/filament/pages/kalender-absensi.blade.php ENDPATH**/ ?>