<x-filament-panels::page>

    {{-- This renders the form defined in your class --}}
    <form wire:submit="save">
        {{ $this->form }}

        {{-- CORRECT WAY TO RENDER FORM ACTIONS --}}
        <div class="mt-6">
            <x-filament-actions::actions :actions="$this->getFormActions()" />
        </div>
    </form>
    
    {{-- A divider to separate the form from the backups table --}}
    <div class="my-8 border-t border-gray-200 dark:border-gray-700"></div>

    {{-- Database Backups Section --}}
    <div>
        <h2 class="text-xl font-bold tracking-tight">
            Database Backups
        </h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Here are the most recent database backups. You can download them directly.
        </p>

        <div class="mt-4 rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10">
            <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="fi-ta-header-cell px-3 py-3.5 sm:px-6 text-sm font-semibold text-gray-950 dark:text-white text-left">
                            File Name
                        </th>
                        <th class="fi-ta-header-cell px-3 py-3.5 sm:px-6 text-sm font-semibold text-gray-950 dark:text-white text-left">
                            Size
                        </th>
                        <th class="fi-ta-header-cell px-3 py-3.5 sm:px-6 text-sm font-semibold text-gray-950 dark:text-white text-left">
                            Date Created
                        </th>
                        <th class="fi-ta-header-cell px-3 py-3.5 sm:px-6 text-sm font-semibold text-gray-950 dark:text-white text-right">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/5 whitespace-nowrap">
                    @forelse ($this->backups as $backup)
                        <tr>
                            <td class="fi-ta-cell p-3 sm:px-6 text-sm text-gray-950 dark:text-white">
                                {{ $backup['name'] }}
                            </td>
                            <td class="fi-ta-cell p-3 sm:px-6 text-sm text-gray-950 dark:text-white">
                                {{ $backup['size'] }}
                            </td>
                            <td class="fi-ta-cell p-3 sm:px-6 text-sm text-gray-950 dark:text-white">
                                {{ $backup['date'] }}
                            </td>
                            <td class="fi-ta-cell p-3 sm:px-6 text-right">
                                <x-filament::button
                                    wire:click="downloadBackup('{{ $backup['name'] }}')"
                                    icon="heroicon-o-arrow-down-tray"
                                    size="sm"
                                    color="gray"
                                >
                                    Download
                                </x-filament::button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="fi-ta-cell p-3 sm:px-6 text-center text-gray-500 dark:text-gray-400">
                                No backup files found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</x-filament-panels::page>