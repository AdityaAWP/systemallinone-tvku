<x-filament::widget>
    <x-filament::card>
        @php
            $data = $this->getViewData();
            $intern = $data['intern'];
        @endphp

        @if($intern)
            {{-- Profile Header --}}
            <div class="text-center mb-6">
                <div class="flex justify-center mb-4">
                    <img src="{{ asset('images/user.png') }}" alt="{{ $intern->name }}" 
                        class="w-20 h-20 rounded-full bg-white border-2 border-black object-cover">
                    </div>
                
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-1">{{ $intern->name }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">{{ $intern->email }}</p>
                
                {{-- Status Badge --}}
                <div class="flex justify-center mb-4">
                    <span class="inline-flex items-center gap-x-1.5 px-3 py-1.5 rounded-full text-sm font-medium
                        {{ $data['status']['color'] === 'success' ? 'bg-success-100 text-success-700 dark:bg-success-800 dark:text-success-100' : '' }}
                        {{ $data['status']['color'] === 'warning' ? 'bg-warning-100 text-warning-700 dark:bg-warning-800 dark:text-warning-100' : '' }}
                        {{ $data['status']['color'] === 'danger' ? 'bg-danger-100 text-danger-700 dark:bg-danger-800 dark:text-danger-100' : '' }}
                        {{ $data['status']['color'] === 'gray' ? 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-100' : '' }}">
                        <x-filament::icon :icon="$data['status']['icon']" class="h-4 w-4" />
                        {{ $data['status']['label'] }}
                    </span>
                </div>
            </div>

            {{-- Quick Info --}}
            <div class="space-y-4">
                <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Divisi</dt>
                    <dd class="text-sm text-gray-900 dark:text-white mt-1">{{ $intern->internDivision->name ?? 'Belum ditentukan' }}</dd>
                </div>
                
                @if($intern->school)
                <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Sekolah</dt>
                    <dd class="text-sm text-gray-900 dark:text-white mt-1">{{ $intern->school->name }}</dd>
                </div>
                @endif
                
                <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Periode Magang</dt>
                    <dd class="text-sm text-gray-900 dark:text-white mt-1">
                        {{ $data['start_date']->translatedFormat('d M Y') }} - {{ $data['end_date']->translatedFormat('d M Y') }}
                    </dd>
                </div>
                
                <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Durasi</dt>
                    <dd class="text-sm text-gray-900 dark:text-white mt-1">{{ $data['total_duration_days'] }} hari</dd>
                </div>
                
                <div>
                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Progress</dt>
                    <dd class="mt-2">
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-primary-600 h-2 rounded-full transition-all duration-300" 
                                 style="width: {{ $data['progress_percentage'] }}%"></div>
                        </div>
                        <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-1">
                            <span>{{ $data['progress_percentage'] }}%</span>
                            <span>{{ (int) $data['days_remaining'] }} hari tersisa</span>
                        </div>
                    </dd>
                </div>
            </div>
           
        @else
            <div class="text-center py-8">
                <x-filament::icon icon="heroicon-o-user-circle" class="h-12 w-12 text-gray-400 mx-auto mb-3" />
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Informasi profil tidak tersedia
                </p>
            </div>
        @endif
    </x-filament::card>
</x-filament::widget>