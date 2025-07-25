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
        <x-heroicon-o-bell class="w-7 h-7" />
        @if ($unreadNotificationsCount > 0)
            <span class="absolute -top-1 -right-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-primary-600 rounded-full shadow z-10">{{ $unreadNotificationsCount }}</span>
        @endif
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
            @forelse ($unreadNotifications as $notification)
                <div class="flex items-center gap-3 px-6 py-4 border-b last:border-b-0 cursor-pointer {{ !$notification->read_at ? 'bg-blue-50 hover:bg-blue-100' : 'hover:bg-gray-50' }} transition">
                    <div class="flex-shrink-0">
                        @if(isset($notification->data['avatar']))
                            <img src="{{ $notification->data['avatar'] }}" class="w-10 h-10 rounded-full object-cover" alt="avatar">
                        @else
                            <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center">
                                <x-heroicon-o-bell class="w-6 h-6 text-blue-500" />
                            </div>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold text-gray-900 text-lg leading-tight">
                            {{ $notification->data['title'] ?? 'Notifikasi' }}
                        </div>
                        <div class="text-gray-700 text-base mt-0.5 leading-snug">
                            {{ $notification->data['body'] ?? 'Tidak ada detail.' }}
                        </div>
                        <div class="text-xs text-gray-400 mt-1">
                            {{ $notification->created_at->diffForHumans() }}
                        </div>
                    </div>
                </div>
            @empty
                <p class="px-6 py-4 text-base text-gray-700 text-center">Tidak ada notifikasi baru.</p>
            @endforelse
            @if ($unreadNotificationsCount > 0)
                <div class="border-t border-gray-100 mt-2 pt-2 px-6">
                    <button type="button" wire:click="markAllAsRead" class="w-full text-center text-base text-primary-600 hover:underline font-semibold">
                        Tandai semua sudah dibaca
                    </button>
                </div>
            @endif
        </div>
    </div>
</div> 