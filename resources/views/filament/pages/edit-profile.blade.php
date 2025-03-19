<x-filament-panels::page>
    <x-filament::button class="w-10">
        Edit
    </x-filament::button>
    <x-filament-panels::form wire:submit="save">
        {{ $this->form }}

        <div class="flex justify-end mt-6">
            <x-filament::button type="submit">
                Simpan Perubahan
            </x-filament::button>
        </div>
    </x-filament-panels::form>
</x-filament-panels::page>