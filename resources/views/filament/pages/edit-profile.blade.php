<x-filament-panels::page>
    <h1 class="bg-blue-600 w-1/12 text-center text-white rounded-lg py-2 px-2">Edit Profile</h1>
    <x-filament-panels::form wire:submit="save">
        {{ $this->form }}

        <div class="flex justify-end mt-6">
            <x-filament::button type="submit">
                Simpan Perubahan
            </x-filament::button>
        </div>
    </x-filament-panels::form>
</x-filament-panels::page>