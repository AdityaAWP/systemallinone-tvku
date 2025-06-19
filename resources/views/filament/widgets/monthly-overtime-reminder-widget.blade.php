<x-filament::widget>
    <x-filament::card>
        @php
            $data = $this->getOvertimeReminderData();
        @endphp {{-- Header --}}
        <div class="flex items-center gap-x-3 mb-4">
            <div class="flex-shrink-0">
                <x-filament::icon icon="heroicon-o-clock" class="h-6 w-6 text-warning" />
            </div>
            <div>
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                    Reminder Lembur & Laporan Harian
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Pantau dan kelola lembur serta laporan harian Anda
                </p>
            </div>
        </div>        {{-- Daily Report Reminder Section --}}
        @if($data['daily_report']['should_show_daily_reminder'])
            <div class="mb-4">
                <x-filament::section collapsible :collapsed="false">
                    <x-slot name="heading">
                        <div class="flex items-center gap-x-2">
                            <x-filament::icon icon="heroicon-o-document-text" class="h-5 w-5 text-danger-500" />
                            <span class="text-white">
                                Saatnya Mengisi Laporan Harian!
                            </span>
                        </div>
                    </x-slot>

                    <div class="space-y-4">
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            {{ $data['daily_report']['daily_reminder_reason'] }}
                        </p>

                        <div class="flex gap-x-3 pt-2">
                            <x-filament::button tag="a" :href="route('filament.admin.resources.daily-reports.create')"
                                color="danger" icon="heroicon-o-plus">
                                Buat Laporan Harian
                            </x-filament::button>
                        </div>
                    </div>
                </x-filament::section>
            </div>
        @elseif($data['daily_report']['has_today_report'])
            {{-- SUDAH MENGISI LAPORAN HARIAN HARI INI --}}
            <div class="mb-4">
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-x-2">
                            <x-filament::icon icon="heroicon-o-check-circle"
                                class="h-6 w-6 text-success-600 dark:text-success-400" />
                            <div>
                                <span class="text-success-600 dark:text-success-400">
                                    Bagus! Anda sudah mengisi laporan harian hari ini
                                </span>
                                <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                                    Laporan harian minggu ini: <strong>{{ $data['daily_report']['this_week_reports_count'] }} hari</strong>
                                    @if($data['daily_report']['missing_days_count'] == 0)
                                        - Semua hari kerja sudah terisi!
                                    @endif
                                </p>
                            </div>
                        </div>
                    </x-slot>
                     {{-- Daily Report Statistics --}}
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-4">Statistik Laporan Harian</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            {{-- This Week Reports --}}
                            <div
                                class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
                                <div class="flex items-center gap-x-4">
                                    <div class="flex-shrink-0">
                                        <div
                                            class="flex h-12 w-12 items-center justify-center rounded-lg bg-success-50 dark:bg-success-400/10">
                                            <x-filament::icon icon="heroicon-o-document-text"
                                                class="h-6 w-6 text-success-600 dark:text-primary-400" />
                                        </div>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white mb-1">
                                            Minggu Ini
                                        </p>
                                        <p class="text-3xl font-semibold text-primary-600 dark:text-primary-400 my-2">
                                            {{ $data['daily_report']['this_week_reports_count'] }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            laporan harian
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {{-- Missing Days --}}
                            @if($data['daily_report']['missing_days_count'] > 0)
                                <div
                                    class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
                                    <div class="flex items-center gap-x-4">
                                        <div class="flex-shrink-0">
                                            <div
                                                class="flex h-12 w-12 items-center justify-center rounded-lg bg-danger-50 dark:bg-danger-400/10">
                                                <x-filament::icon icon="heroicon-o-exclamation-triangle"
                                                    class="h-6 w-6 text-danger-600 dark:text-danger-400" />
                                            </div>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white mb-1">
                                                Hari Terlewat
                                            </p>
                                            <p class="text-3xl font-semibold text-danger-600 dark:text-danger-400 my-2">
                                                {{ $data['daily_report']['missing_days_count'] }}
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                hari belum diisi
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div
                                    class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
                                    <div class="flex items-center gap-x-4">
                                        <div class="flex-shrink-0">
                                            <div
                                                class="flex h-12 w-12 items-center justify-center rounded-lg bg-success-50 dark:bg-success-400/10">
                                                <x-filament::icon icon="heroicon-o-check-circle"
                                                    class="h-6 w-6 text-success-600 dark:text-success-400" />
                                            </div>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white mb-1">
                                                Kelengkapan
                                            </p>
                                            <p class="text-3xl font-semibold text-success-600 dark:text-success-400 my-2">
                                                100%
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                laporan lengkap
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- Recent Daily Report --}}
                            @if($data['daily_report']['recent_reports']->count() > 0)
                                <div
                                    class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
                                    <div class="flex items-center gap-x-4">
                                        <div class="flex-shrink-0">
                                            <div
                                                class="flex h-12 w-12 items-center justify-center rounded-lg bg-info-50 dark:bg-info-400/10">
                                                <x-filament::icon icon="heroicon-o-calendar"
                                                    class="h-6 w-6 text-info-600 dark:text-info-400" />
                                            </div>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white mb-1">
                                                Laporan Terbaru
                                            </p>
                                            @php
                                                $latestReport = $data['daily_report']['recent_reports']->first();
                                            @endphp
                                            <p class="text-3xl font-semibold text-info-600 dark:text-info-400 my-2">
                                                {{ $latestReport->entry_date->format('d M Y') }}
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $latestReport->work_hours_component }}j
                                                {{ $latestReport->work_minutes_component }}m kerja
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div
                                    class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
                                    <div class="flex items-center gap-x-4">
                                        <div class="flex-shrink-0">
                                            <div
                                                class="flex h-12 w-12 items-center justify-center rounded-lg bg-gray-50 dark:bg-gray-400/10">
                                                <x-filament::icon icon="heroicon-o-document-text"
                                                    class="h-6 w-6 text-gray-400 dark:text-gray-500" />
                                            </div>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white mb-1">
                                                Laporan Terbaru
                                            </p>
                                            <p class="text-2xl font-semibold text-gray-400 dark:text-gray-500 my-2">
                                                Kosong
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                Belum ada laporan
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                </x-filament::section>
            </div>
        @endif
        
        {{-- Main Alert Section - TAMPILKAN REMINDER LEMBUR --}}
        @if($data['should_show_reminder'])
            <div class="mb-8">
                <x-filament::section collapsible :collapsed="false">
                    <x-slot name="heading">
                        <div class="flex items-center gap-x-2">
                            <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-5 w-5 text-warning-500" />
                            <span class="text-warning-600 dark:text-warning-400">
                                Saatnya Mengajukan Lembur!
                            </span>
                        </div>
                    </x-slot>

                    <div class="space-y-4">
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            Anda belum mengajukan lembur untuk bulan <strong>{{ $data['current_month_name'] }}</strong>.
                            {{ $data['reminder_reason'] }}
                        </p>

                        @if($data['previous_month_count'] > 0)
                            <div class="flex items-center gap-x-2 text-sm text-gray-500 dark:text-gray-400">
                                <x-filament::icon icon="heroicon-o-information-circle" class="h-4 w-4" />
                                <span>
                                    Bulan lalu: {{ $data['previous_month_count'] }} kali lembur
                                    ({{ $data['previous_month_total'] }} jam)
                                </span>
                            </div>
                        @endif

                        <div class="flex gap-x-3 pt-2">
                            <x-filament::button tag="a" :href="route('filament.admin.resources.overtimes.create')"
                                color="warning" icon="heroicon-o-plus">
                                Ajukan Lembur Sekarang
                            </x-filament::button>
                        </div>
                    </div>
                </x-filament::section>
            </div>

        @elseif($data['has_submitted_this_month'])
            {{-- SUDAH MENGAJUKAN LEMBUR BULAN INI --}}
            <div class="mb-8">
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-x-2">
                            <x-filament::icon icon="heroicon-o-check-circle"
                                class="h-6 w-6 text-success-600 dark:text-success-400" />
                            <div>
                                <span class="text-success-600 dark:text-success-400">
                                    Bagus! Anda sudah mengajukan lembur bulan ini
                                </span>
                                <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                                    Total lembur bulan {{ $data['current_month_name'] }}:
                                    <strong>{{ $data['current_month_count'] }} kali</strong>
                                </p>
                            </div>
                        </div>
                    </x-slot>

                    {{-- Statistics Grid --}}
                    <div class="mb-8">
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-4">Statistik Lembur</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                            {{-- Current Month --}}
                            <div
                                class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
                                <div class="flex items-center gap-x-4">
                                    <div class="flex-shrink-0">
                                        <div
                                            class="flex h-12 w-12 items-center justify-center rounded-lg bg-primary-50 dark:bg-primary-400/10">
                                            <x-filament::icon icon="heroicon-o-calendar-days"
                                                class="h-6 w-6 text-primary-600 dark:text-primary-400" />
                                        </div>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white mb-1">
                                            Bulan Ini
                                        </p>
                                        <p class="text-3xl font-semibold text-primary-600 dark:text-primary-400 my-2">
                                            {{ $data['current_month_total'] }} jam
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $data['current_month_count'] }} kali lembur
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {{-- Previous Month --}}
                            <div
                                class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
                                <div class="flex items-center gap-x-4">
                                    <div class="flex-shrink-0">
                                        <div
                                            class="flex h-12 w-12 items-center justify-center rounded-lg bg-gray-50 dark:bg-gray-400/10">
                                            <x-filament::icon icon="heroicon-o-backward"
                                                class="h-6 w-6 text-danger-600 dark:text-warning-400" />
                                        </div>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white mb-1">
                                            Bulan Lalu
                                        </p>
                                        <p class="text-3xl font-semibold text-danger-600 dark:text-danger-400 my-2">
                                            {{ $data['previous_month_total'] }} jam
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $data['previous_month_count'] }} kali lembur
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {{-- Recent Overtime Entries --}}
                            @if($data['recent_overtimes']->count() > 0)
                                <div
                                    class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
                                    <div class="flex items-center gap-x-4">
                                        <div class="flex-shrink-0">
                                            <div
                                                class="flex h-12 w-12 items-center justify-center rounded-lg bg-warning dark:bg-warning-400/10">
                                                <x-filament::icon icon="heroicon-o-clock"
                                                    class="h-6 w-6 text-warning-600 dark:text-warning-400" />
                                            </div>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white mb-1">
                                                Lembur Terbaru
                                            </p>
                                            @php
                                                $latestOvertime = $data['recent_overtimes']->first();
                                            @endphp
                                            <p class="text-3xl font-semibold text-warning-600 dark:text-warning-400 my-2">
                                                {{ $latestOvertime->overtime_hours }}j {{ $latestOvertime->overtime_minutes }}m
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $latestOvertime->tanggal_overtime->format('d M Y') }} -
                                                {{ Str::limit($latestOvertime->description, 20) }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div
                                    class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
                                    <div class="flex items-center gap-x-4">
                                        <div class="flex-shrink-0">
                                            <div
                                                class="flex h-12 w-12 items-center justify-center rounded-lg bg-gray-50 dark:bg-gray-400/10">
                                                <x-filament::icon icon="heroicon-o-clock"
                                                    class="h-6 w-6 text-gray-400 dark:text-gray-500" />
                                            </div>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white mb-1">
                                                Lembur Terbaru
                                            </p>
                                            <p class="text-2xl font-semibold text-gray-400 dark:text-gray-500 my-2">
                                                0 jam
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                Belum ada data lembur
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                       
                    </div>

                </x-filament::section>
            </div>
        @else
            {{-- BELUM ADA AKTIVITAS LEMBUR --}}
            <div class="mb-8">
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-x-2">
                            <x-filament::icon icon="heroicon-o-information-circle"
                                class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                            <span class="text-blue-600 dark:text-blue-400">
                                Belum Ada Pengajuan Lembur
                            </span>
                        </div>
                    </x-slot>

                    <div class="space-y-4">
                        <p class="text-sm text-gray-300 dark:text-gray-300">
                            Anda belum mengajukan lembur bulan ini. Ajukan lembur jika diperlukan.
                        </p>

                        <div class="flex gap-x-3 pt-2">
                            <x-filament::button tag="a" :href="route('filament.admin.resources.overtimes.create')"
                                color="primary" icon="heroicon-o-plus">
                                Ajukan Lembur
                            </x-filament::button>
                        </div>
                    </div>
                </x-filament::section>
            </div>
        @endif

    </x-filament::card>
</x-filament::widget>