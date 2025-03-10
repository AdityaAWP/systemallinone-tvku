<x-filament-panels::page>
    <x-filament::section class="w md:w-1/2 ">
        <form wire:submit="save">
            {{ $this->form }}

            <x-filament::button type="submit" class="mt-7 bg-[#1E87C7]">
                Simpan
            </x-filament::button>
        </form>
    </x-filament::section>
</x-filament-panels::page>