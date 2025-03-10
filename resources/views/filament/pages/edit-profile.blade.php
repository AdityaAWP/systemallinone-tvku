<x-filament::page>
    <form wire:submit="save" class="m-4">
        {{ $this->form }}
        
        <x-filament::button type="submit" class="m-4">
            Save
        </x-filament::button>
    </form>
</x-filament::page>