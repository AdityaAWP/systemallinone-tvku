@php
    $currentYear = now()->year;
    $selectedYear = request('year');
    $statusFilter = request('status');
    
    $isFinanceDirector = auth()->user()->hasRole('direktur_utama');
    $isFinanceAdmin = auth()->user()->hasRole('admin_keuangan');
    $isFinanceStaff = auth()->user()->hasRole('staff_keuangan');
    $isFinanceManager = auth()->user()->hasRole('manager_keuangan');
    
    // Check if user has any finance-related role
    $hasFinanceRole = $isFinanceDirector || $isFinanceAdmin || $isFinanceStaff || $isFinanceManager;
    
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

    <!-- Status Filters (Role-specific) -->
    @if($hasFinanceRole)
        <div class="flex items-center gap-2">
            @if($isFinanceDirector)
                {{-- Filters for Direktur Utama --}}
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

            @elseif($isFinanceManager)
                {{-- Filters for Manager Keuangan --}}
                @php
                    $needSubmissionUrl = $statusFilter === 'need_submission'
                        ? ($selectedYear ? $baseUrl.'?year='.$selectedYear : $baseUrl)
                        : $baseUrl.'?status=need_submission'.($selectedYear ? '&year='.$selectedYear : '');
                        
                    $submittedUrl = $statusFilter === 'submitted'
                        ? ($selectedYear ? $baseUrl.'?year='.$selectedYear : $baseUrl)
                        : $baseUrl.'?status=submitted'.($selectedYear ? '&year='.$selectedYear : '');
                @endphp
                
                <x-filament::button
                    size="sm"
                    :color="$statusFilter === 'need_submission' ? 'primary' : 'gray'"
                    :href="$needSubmissionUrl"
                    tag="a"
                >
                    Perlu Submit
                </x-filament::button>
                
                <x-filament::button
                    size="sm"
                    :color="$statusFilter === 'submitted' ? 'primary' : 'gray'"
                    :href="$submittedUrl"
                    tag="a"
                >
                    Sudah Submit
                </x-filament::button>

            @elseif($isFinanceStaff)
                {{-- Filters for Staff Keuangan --}}
                @php
                    $myPendingUrl = $statusFilter === 'my_pending'
                        ? ($selectedYear ? $baseUrl.'?year='.$selectedYear : $baseUrl)
                        : $baseUrl.'?status=my_pending'.($selectedYear ? '&year='.$selectedYear : '');
                        
                    $myApprovedUrl = $statusFilter === 'my_approved'
                        ? ($selectedYear ? $baseUrl.'?year='.$selectedYear : $baseUrl)
                        : $baseUrl.'?status=my_approved'.($selectedYear ? '&year='.$selectedYear : '');
                        
                    $myRejectedUrl = $statusFilter === 'my_rejected'
                        ? ($selectedYear ? $baseUrl.'?year='.$selectedYear : $baseUrl)
                        : $baseUrl.'?status=my_rejected'.($selectedYear ? '&year='.$selectedYear : '');
                @endphp
                
                <x-filament::button
                    size="sm"
                    :color="$statusFilter === 'my_pending' ? 'primary' : 'gray'"
                    :href="$myPendingUrl"
                    tag="a"
                >
                    Menunggu Persetujuan
                </x-filament::button>
                
                <x-filament::button
                    size="sm"
                    :color="$statusFilter === 'my_approved' ? 'primary' : 'gray'"
                    :href="$myApprovedUrl"
                    tag="a"
                >
                    Disetujui
                </x-filament::button>
                
                <x-filament::button
                    size="sm"
                    :color="$statusFilter === 'my_rejected' ? 'primary' : 'gray'"
                    :href="$myRejectedUrl"
                    tag="a"
                >
                    Ditolak
                </x-filament::button>

            @elseif($isFinanceAdmin)
                {{-- Filters for Admin Keuangan - Show all status filters --}}
                @php
                    $allPendingUrl = $statusFilter === 'all_pending'
                        ? ($selectedYear ? $baseUrl.'?year='.$selectedYear : $baseUrl)
                        : $baseUrl.'?status=all_pending'.($selectedYear ? '&year='.$selectedYear : '');
                        
                    $allApprovedUrl = $statusFilter === 'all_approved'
                        ? ($selectedYear ? $baseUrl.'?year='.$selectedYear : $baseUrl)
                        : $baseUrl.'?status=all_approved'.($selectedYear ? '&year='.$selectedYear : '');
                        
                    $allRejectedUrl = $statusFilter === 'all_rejected'
                        ? ($selectedYear ? $baseUrl.'?year='.$selectedYear : $baseUrl)
                        : $baseUrl.'?status=all_rejected'.($selectedYear ? '&year='.$selectedYear : '');
                @endphp
                
                <x-filament::button
                    size="sm"
                    :color="$statusFilter === 'all_pending' ? 'primary' : 'gray'"
                    :href="$allPendingUrl"
                    tag="a"
                >
                    Semua Pending
                </x-filament::button>
                
                <x-filament::button
                    size="sm"
                    :color="$statusFilter === 'all_approved' ? 'primary' : 'gray'"
                    :href="$allApprovedUrl"
                    tag="a"
                >
                    Semua Disetujui
                </x-filament::button>
                
                <x-filament::button
                    size="sm"
                    :color="$statusFilter === 'all_rejected' ? 'primary' : 'gray'"
                    :href="$allRejectedUrl"
                    tag="a"
                >
                    Semua Ditolak
                </x-filament::button>
            @endif
        </div>
    @endif
</div>