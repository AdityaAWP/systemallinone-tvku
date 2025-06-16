@php
    $currentYear = now()->year;
    $selectedYear = request('year');
    $statusFilter = request('status');
    
    $isFinanceDirector = auth()->user()->hasRole('direktur_utama');
    $isFinanceAdmin = auth()->user()->hasRole('admin_keuangan');
    
    // Generate years range (e.g., from current year -5 to current year +5)
    $years = range($currentYear - 5, $currentYear + 0);
    
    // Generate the current URL base without query parameters
    $baseUrl = strtok(request()->fullUrl(), '?');
@endphp

<div class="flex items-center gap-4 mb-4">
    <!-- Year Filter Dropdown -->
    <div class="flex items-center space-x-2">
        <x-filament::dropdown placement="bottom-start">
            <x-slot name="trigger">
                <x-filament::button 
                    size="sm" 
                    :color="$selectedYear ? 'primary' : 'gray'"
                    icon-position="after"
                    icon="heroicon-m-chevron-down"
                    class="flex items-center gap-2"
                >
                    {{ $selectedYear ?? 'Semua Tahun' }}
                </x-filament::button>
            </x-slot>

            <x-filament::dropdown.list class="min-w-[calc(100%+0.5rem)] -ml-0.5">
                <!-- All Years Option -->
                <a href="{{ $baseUrl }}{{ $statusFilter ? '?status='.$statusFilter : '' }}" 
                   class="filament-dropdown-list-item filament-dropdown-item flex items-center whitespace-nowrap rounded-md px-4 py-2 text-sm @if(!$selectedYear) bg-primary-500 text-white font-medium @else hover:bg-gray-500/10 focus:bg-gray-500/10 @endif">
                    Semua Tahun
                </a>
                
                <!-- Individual Year Options -->
                @foreach($years as $year)
                    @php
                        // Create the URL for this year option
                        // If already selected, clicking again will remove the year filter
                        $yearUrl = $year == $selectedYear 
                            ? ($statusFilter ? $baseUrl.'?status='.$statusFilter : $baseUrl)
                            : $baseUrl.'?year='.$year.($statusFilter ? '&status='.$statusFilter : '');
                    @endphp
                    <a href="{{ $yearUrl }}"
                       class="filament-dropdown-list-item filament-dropdown-item flex items-center whitespace-nowrap rounded-md px-4 py-2 text-sm @if($year == $selectedYear) bg-primary-500 text-white font-medium @else hover:bg-gray-500/10 focus:bg-gray-500/10 @endif">
                        {{ $year }}
                    </a>
                @endforeach
            </x-filament::dropdown.list>
        </x-filament::dropdown>
    </div>

    <!-- Status Filters (Only for direktur_utama) -->
    @if($isFinanceDirector)
        <div class="flex items-center gap-2">
            @php
                // Create URLs for status filters
                $pendingUrl = $statusFilter === 'pending'
                    ? ($selectedYear ? $baseUrl.'?year='.$selectedYear : $baseUrl)
                    : $baseUrl.'?status=pending'.($selectedYear ? '&year='.$selectedYear : '');
                    
                $respondedUrl = $statusFilter === 'responded'
                    ? ($selectedYear ? $baseUrl.'?year='.$selectedYear : $baseUrl)
                    : $baseUrl.'?status=responded'.($selectedYear ? '&year='.$selectedYear : '');
            @endphp
            
             <x-filament::button
                size="sm"
                :color="$statusFilter === 'pending' ? 'primary' : 'gray'"
                :href="$pendingUrl"
                tag="a"
            >
                Belum Direspon
            </x-filament::button>
            
            <x-filament::button
                size="sm"
                :color="$statusFilter === 'responded' ? 'primary' : 'gray'"
                :href="$respondedUrl"
                tag="a"
            >
                Sudah Direspon
            </x-filament::button>
        
        </div>
    @endif
</div>