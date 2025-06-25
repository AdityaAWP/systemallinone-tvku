<x-filament::widget>
    <x-filament::card>
        @php
            $data = $this->getJournalReminderData();
        @endphp

        @if($data)
            {{-- Header --}}
            <div class="flex items-center gap-x-3 mb-4">
                <div class="flex-shrink-0">
                    <x-filament::icon icon="heroicon-o-document-text" class="h-6 w-6 text-primary-600" />
                </div>
                <div>
                    <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                        Reminder Journal Harian
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Pantau dan lengkapi journal harian Anda
                    </p>
                </div>
            </div>

            {{-- Main Reminder Section --}}
            @if($data['should_show_reminder'])
                <div class="mb-6">
                    <x-filament::section collapsible :collapsed="false">
                        <x-slot name="heading">
                            <div class="flex items-center gap-x-2">
                                @if(!$data['today_journal'])
                                    <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-5 w-5 text-warning-500" />
                                    <span class="text-warning-600 dark:text-warning-400">
                                        Jangan Lupa Isi Journal Hari Ini!
                                    </span>
                                @endif
                            </div>
                        </x-slot>

                        <div class="space-y-4">
                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                {{ $data['reminder_message'] }}
                            </p>

                            @if(!$data['today_journal'])
                                <div class="flex items-center gap-x-2 text-sm text-gray-500 dark:text-gray-400">
                                    <x-filament::icon icon="heroicon-o-information-circle" class="h-4 w-4" />
                                    <span>
                                        Isi journal sebelum akhir hari kerja untuk memastikan laporan harian Anda tercatat dengan
                                        baik.
                                    </span>
                                </div>

                                <div class="flex gap-x-3 pt-2">
                                    <x-filament::button tag="a" :href="route('filament.intern.resources.journals.create')"
                                        color="primary" icon="heroicon-o-plus">
                                        Isi Journal Sekarang
                                    </x-filament::button>
                                </div>
                            @endif
                        </div>
                    </x-filament::section>
                </div>
            @endif

            {{-- Journal sudah diisi hari ini --}}
            @if($data['today_journal'])
                <div class="mb-6">
                    <x-filament::section>
                        <x-slot name="heading">
                            <div class="flex items-center gap-x-2">
                                <x-filament::icon icon="heroicon-o-check-circle" class="h-5 w-5 text-success-500" />
                                <span class="text-success-600 dark:text-success-400">
                                    Journal Hari Ini Sudah Terisi
                                </span>
                            </div>
                        </x-slot>

                        {{-- Statistics Section --}}
                        <div class="mb-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                {{-- Today Status --}}
                                <div
                                    class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
                                    <div class="flex items-center gap-x-3">
                                        <div class="flex-shrink-0">
                                            <div
                                                class="flex h-10 w-10 items-center justify-center rounded-lg 
                                            {{ $data['today_journal'] ? 'bg-success-50 dark:bg-success-400/10' : 'bg-warning-50 dark:bg-warning-400/10' }}">
                                                <x-filament::icon
                                                    icon="{{ $data['today_journal'] ? 'heroicon-o-check-circle' : 'heroicon-o-clock' }}"
                                                    class="h-6 w-6 {{ $data['today_journal'] ? 'text-success-600 dark:text-success-400' : 'text-warning-600 dark:text-warning-400' }}" />
                                            </div>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm text-gray-900 dark:text-white mb-1">
                                                Hari Ini
                                            </p>
                                            <p
                                                class="text-lg font-semibold {{ $data['today_journal'] ? 'text-success-600 dark:text-success-400' : 'text-warning-600 dark:text-warning-400' }}">
                                                {{ $data['today_journal'] ? '✓ Sudah Diisi' : '⏰ Belum Diisi' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Weekly Progress --}}
                                <div
                                    class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
                                    <div class="flex items-center gap-x-3">
                                        <div class="flex-shrink-0">
                                            <div
                                                class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-400/10">
                                                <x-filament::icon icon="heroicon-o-calendar-days"
                                                    class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                                            </div>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm text-gray-900 dark:text-white mb-1">
                                                Minggu Ini
                                            </p>
                                            <p class="text-lg font-semibold text-blue-600 dark:text-blue-400">
                                                {{ (int) $data['completed_this_week'] }}/{{ (int) $data['total_weekdays_week'] }} Hari
                                            </p>
                                            <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                                                <div class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                                                    style="width: {{ ($data['total_weekdays_week'] ?? 0) > 0 ? number_format(($data['completed_this_week'] / $data['total_weekdays_week']) * 100, 2) : 0 }}%">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </x-filament::section>
                </div>
            @endif

            {{-- Recent Journal Entries --}}
            @if($data['recent_journals']->count() > 0)
                <div class="my-4">
                    <x-filament::section collapsible :collapsed="true">
                        <x-slot name="heading">
                            <div class="flex items-center gap-x-2">
                                <x-filament::icon icon="heroicon-o-list-bullet" class="h-5 w-5 text-gray-500" />
                                <span>Journal Terbaru ({{ $data['recent_journals']->count() }} Terakhir)</span>
                            </div>
                        </x-slot>

                        <div class="space-y-3">
                            @foreach($data['recent_journals'] as $journal)
                                <div class="flex items-center gap-x-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                    <div class="flex-shrink-0">
                                        @php
                                            $statusColor = match ($journal->status) {
                                                'Hadir' => 'text-success-600',
                                                'Izin' => 'text-warning-600',
                                                'Sakit' => 'text-danger-600',
                                                default => 'text-gray-600'
                                            };
                                        @endphp
                                        <div class="w-2 h-2 rounded-full {{ str_replace('text-', 'bg-', $statusColor) }}"></div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $journal->entry_date->translatedFormat('l, d F Y') }}
                                            </p>
                                            <span class="text-xs px-2 py-1 rounded-full {{ $statusColor }} bg-opacity-10">
                                                {{ $journal->status }}
                                            </span>
                                        </div>
                                        @if($journal->start_time && $journal->end_time)
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $journal->start_time->format('H:i') }} - {{ $journal->end_time->format('H:i') }}
                                            </p>
                                        @endif
                                        @if($journal->activity)
                                            <p class="text-xs text-gray-600 dark:text-gray-300 mt-1">
                                                {{ Str::limit(strip_tags($journal->activity), 80) }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="flex gap-x-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                            <x-filament::button tag="a" :href="route('filament.intern.resources.journals.index')" color="gray"
                                icon="heroicon-o-eye" size="sm">
                                Lihat Semua Journal
                            </x-filament::button>
                        </div>
                    </x-filament::section>
                </div>
            @endif

            {{-- Tips Section --}}
            <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/50 rounded-lg">
                <div class="flex items-start gap-x-2">
                    <x-filament::icon icon="heroicon-o-light-bulb"
                        class="h-4 w-4 text-blue-600 dark:text-blue-400 mt-0.5" />
                    <div class="text-xs text-blue-700 dark:text-blue-300">
                        <strong>Tips:</strong> Isi journal setiap hari untuk mencatat aktivitas magang Anda.
                        Jurnal yang konsisten akan membantu evaluasi performa dan memberikan catatan yang baik untuk
                        laporan akhir magang.
                    </div>
                </div>
            </div>
        @endif
    </x-filament::card>
</x-filament::widget>