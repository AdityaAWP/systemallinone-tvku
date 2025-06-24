<x-filament-panels::page>
    <div class="space-y-6">
        <form wire:submit="save">
            {{ $this->form }}
            
            <div class="flex justify-end mt-6">
                <x-filament::button type="submit" color="primary">
                    Save Configuration
                </x-filament::button>
            </div>
        </form>
    </div>
    
    <x-filament-actions::modals />
    
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('download-backup', (event) => {
                const link = document.createElement('a');
                link.href = event[0].url;
                link.style.display = 'none';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });
            
            Livewire.on('download-sql-backup', (event) => {
                const link = document.createElement('a');
                link.href = event[0].url;
                link.style.display = 'none';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });
        });
    </script>
</x-filament-panels::page>