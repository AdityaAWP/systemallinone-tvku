<x-filament-panels::page>
    <x-filament-panels::form wire:submit="create">
        {{ $this->form }}

        <x-filament-panels::form.actions
            :actions="$this->getCachedFormActions()"
            :full-width="$this->hasFullWidthFormActions()"
        />
    </x-filament-panels::form>

    @if(Auth::guard('intern')->check())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Custom script loaded for intern geolocation');
            
            // Wait for Livewire to initialize
            document.addEventListener('livewire:load', function () {
                setTimeout(function() {
                    captureLocationForIntern();
                }, 2000);
            });
            
            // Also try after a delay
            setTimeout(function() {
                captureLocationForIntern();
            }, 3000);
            
            function captureLocationForIntern() {
                console.log('Attempting to capture location...');
                
                // Try multiple selectors to find the hidden inputs
                const selectors = [
                    'input[type="hidden"][name*="latitude"]',
                    'input[type="hidden"][wire\\:model*="latitude"]',
                    'input[name="latitude"]',
                    '[wire\\:model="data.latitude"]',
                    '[name="data.latitude"]'
                ];
                
                let latInput = null;
                let lngInput = null;
                let addressInput = null;
                
                for (let selector of selectors) {
                    latInput = document.querySelector(selector);
                    if (latInput) {
                        console.log('Found latitude input with selector:', selector);
                        break;
                    }
                }
                
                for (let selector of selectors) {
                    const modifiedSelector = selector.replace('latitude', 'longitude');
                    lngInput = document.querySelector(modifiedSelector);
                    if (lngInput) {
                        console.log('Found longitude input with selector:', modifiedSelector);
                        break;
                    }
                }
                
                for (let selector of selectors) {
                    const modifiedSelector = selector.replace('latitude', 'location_address');
                    addressInput = document.querySelector(modifiedSelector);
                    if (addressInput) {
                        console.log('Found address input with selector:', modifiedSelector);
                        break;
                    }
                }
                
                console.log('Found inputs:', {
                    lat: !!latInput,
                    lng: !!lngInput,
                    address: !!addressInput
                });
                
                if (latInput && lngInput && addressInput && navigator.geolocation) {
                    console.log('Starting geolocation...');
                    
                    navigator.geolocation.getCurrentPosition(
                        function(position) {
                            const lat = position.coords.latitude.toFixed(8);
                            const lng = position.coords.longitude.toFixed(8);
                            
                            console.log('Geolocation success:', { lat, lng });
                            
                            // Set values using multiple methods
                            latInput.value = lat;
                            lngInput.value = lng;
                            
                            // Try to trigger Livewire update using different approaches
                            if (window.Livewire) {
                                console.log('Using Livewire to set values');
                                @this.set('data.latitude', lat);
                                @this.set('data.longitude', lng);
                            }
                            
                            // Trigger events
                            ['input', 'change', 'blur'].forEach(eventType => {
                                latInput.dispatchEvent(new Event(eventType, { bubbles: true }));
                                lngInput.dispatchEvent(new Event(eventType, { bubbles: true }));
                            });
                            
                            // Get address
                            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&addressdetails=1`)
                                .then(response => response.json())
                                .then(data => {
                                    const address = data && data.display_name ? data.display_name : `Koordinat: ${lat}, ${lng}`;
                                    addressInput.value = address;
                                    
                                    if (window.Livewire) {
                                        @this.set('data.location_address', address);
                                    }
                                    
                                    ['input', 'change', 'blur'].forEach(eventType => {
                                        addressInput.dispatchEvent(new Event(eventType, { bubbles: true }));
                                    });
                                    
                                    console.log('Address set:', address);
                                })
                                .catch(error => {
                                    console.log('Address lookup failed:', error);
                                    const fallback = `Koordinat: ${lat}, ${lng}`;
                                    addressInput.value = fallback;
                                    
                                    if (window.Livewire) {
                                        @this.set('data.location_address', fallback);
                                    }
                                    
                                    ['input', 'change', 'blur'].forEach(eventType => {
                                        addressInput.dispatchEvent(new Event(eventType, { bubbles: true }));
                                    });
                                });
                        },
                        function(error) {
                            console.log('Geolocation error:', error);
                        },
                        {
                            enableHighAccuracy: true,
                            timeout: 15000,
                            maximumAge: 30000
                        }
                    );
                } else {
                    console.log('Required inputs not found or geolocation not available');
                    // Log all form inputs for debugging
                    console.log('All form inputs:', document.querySelectorAll('form input'));
                }
            }
        });
    </script>
    @endif
</x-filament-panels::page>
