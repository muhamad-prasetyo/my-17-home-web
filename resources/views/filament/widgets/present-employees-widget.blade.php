<x-filament::widget>
    <x-filament::card>
        <h2 class="text-lg font-bold tracking-tight">Present Employees</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($this->presentEmployees as $employee)
                <div class="flex items-center space-x-3">
                    <img src="{{ $employee->avatar_url }}" alt="{{ $employee->name }}" class="w-10 h-10 rounded-full object-cover">
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ $employee->name }}</p>
                        <p class="text-xs text-gray-500">{{ $employee->email }}</p>
                    </div>
                </div>
            @empty
                <p>No employees present today.</p>
            @endforelse
        </div>
    </x-filament::card>
</x-filament::widget> 