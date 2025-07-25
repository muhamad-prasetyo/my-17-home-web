<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Filter Section -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Filter Notifikasi</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Jenis Notifikasi</label>
                    <select wire:model.live="filterType" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                        <option value="">Semua Jenis</option>
                        <option value="leave_request">Cuti</option>
                        <option value="transfer_request">Transfer</option>
                        <option value="permission_request">Izin/Report</option>
                        <option value="attendance">Absensi</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
                    <select wire:model.live="filterStatus" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                        <option value="">Semua Status</option>
                        <option value="unread">Belum Dibaca</option>
                        <option value="read">Sudah Dibaca</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tanggal</label>
                    <input type="date" wire:model.live="filterDate" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Cari</label>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari notifikasi..." class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                </div>
            </div>
        </div>

        <!-- Stats Section -->
        <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                            <x-heroicon-o-bell class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                        </div>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Total</p>
                        <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['total'] ?? 0 }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 rounded-full bg-red-100 dark:bg-red-900 flex items-center justify-center">
                            <x-heroicon-o-envelope class="w-4 h-4 text-red-600 dark:text-red-400" />
                        </div>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Belum Dibaca</p>
                        <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $stats['unread'] ?? 0 }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 rounded-full bg-orange-100 dark:bg-orange-900 flex items-center justify-center">
                            <x-heroicon-o-calendar class="w-4 h-4 text-orange-600 dark:text-orange-400" />
                        </div>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Cuti</p>
                        <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ $stats['by_type']['leave_request'] ?? 0 }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                            <x-heroicon-o-arrow-path class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                        </div>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Transfer</p>
                        <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['by_type']['transfer_request'] ?? 0 }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 rounded-full bg-red-100 dark:bg-red-900 flex items-center justify-center">
                            <x-heroicon-o-document-text class="w-4 h-4 text-red-600 dark:text-red-400" />
                        </div>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Izin/Report</p>
                        <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $stats['by_type']['permission_request'] ?? 0 }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 rounded-full bg-green-100 dark:bg-green-900 flex items-center justify-center">
                            <x-heroicon-o-clock class="w-4 h-4 text-green-600 dark:text-green-400" />
                        </div>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Absensi</p>
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['by_type']['attendance'] ?? 0 }}</p>
                    </div>
                </div>
            </div>
        </div>

        @if($notifications && $notifications->count() > 0)
            <div class="grid gap-4">
                @foreach($notifications as $notification)
                    @php
                        $url = $this->getNotificationUrl($notification);
                        $icon = $this->getNotificationIcon($notification);
                        $color = $this->getNotificationColor($notification);
                        $data = $notification->data;
                    @endphp
                    
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 hover:shadow-md transition-shadow duration-200">
                        <div class="flex items-start space-x-3">
                            <!-- Icon -->
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 rounded-full bg-{{ $color }}-100 dark:bg-{{ $color }}-900 flex items-center justify-center">
                                    <x-heroicon-o-{{ str_replace('heroicon-o-', '', $icon) }} class="w-5 h-5 text-{{ $color }}-600 dark:text-{{ $color }}-400" />
                                </div>
                            </div>
                            
                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ $data['title'] ?? 'Notifikasi' }}
                                    </h3>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $notification->created_at->diffForHumans() }}
                                    </span>
                                </div>
                                
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                    {{ $data['body'] ?? 'Tidak ada pesan' }}
                                </p>
                                
                                @if($url)
                                    <div class="mt-3">
                                        <a href="{{ $url }}" 
                                           class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-{{ $color }}-700 bg-{{ $color }}-100 dark:text-{{ $color }}-300 dark:bg-{{ $color }}-900 rounded-md hover:bg-{{ $color }}-200 dark:hover:bg-{{ $color }}-800 transition-colors duration-200">
                                            Lihat Detail
                                            <x-heroicon-o-arrow-right class="ml-1 w-3 h-3" />
                                        </a>
                                    </div>
                                @endif
                            </div>
                            
                            <!-- Status indicator -->
                            @if($notification->read_at === null)
                                <div class="flex-shrink-0">
                                    <div class="w-2 h-2 bg-{{ $color }}-500 rounded-full"></div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12">
                <x-heroicon-o-bell class="mx-auto h-12 w-12 text-gray-400" />
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">Tidak ada notifikasi</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Belum ada notifikasi yang masuk.
                </p>
            </div>
        @endif
    </div>
</x-filament-panels::page> 