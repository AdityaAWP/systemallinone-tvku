<div x-data="internLocationCapture" x-init="init()" style="display: none;">
    <!-- Hidden component for location capture -->
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('internLocationCapture', () => ({
        latitude: null,
        longitude: null,
        locationAddress: null,
        
        init() {
            console.log('Intern location capture component initialized');
            this.captureLocation();
            
            // Also try to capture when form is about to be submitted
            this.setupFormInterception();
        },
        
        captureLocation() {
            if (navigator.geolocation) {
                console.log('Attempting to get geolocation...');
                
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        this.latitude = position.coords.latitude.toFixed(8);
                        this.longitude = position.coords.longitude.toFixed(8);
                        
                        console.log('Location captured:', {
                            lat: this.latitude,
                            lng: this.longitude
                        });
                        
                        // Store in component data
                        this.updateFormFields();
                        
                        // Get address
                        this.getAddress();
                    },
                    (error) => {
                        console.log('Geolocation error:', error);
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 15000,
                        maximumAge: 30000
                    }
                );
            }
        },
        
        updateFormFields() {
            // Try to update hidden form fields
            const selectors = [
                '#intern-latitude',
                'input[name="latitude"]',
                'input[wire\\:model*="latitude"]'
            ];
            
            for (const selector of selectors) {
                const input = document.querySelector(selector);
                if (input) {
                    input.value = this.latitude;
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                    console.log('Updated latitude field:', selector);
                    break;
                }
            }
            
            for (const selector of selectors.map(s => s.replace('latitude', 'longitude'))) {
                const input = document.querySelector(selector);
                if (input) {
                    input.value = this.longitude;
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                    console.log('Updated longitude field:', selector);
                    break;
                }
            }
        },
        
        getAddress() {
            if (this.latitude && this.longitude) {
                fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${this.latitude}&lon=${this.longitude}&addressdetails=1`)
                    .then(response => response.json())
                    .then(data => {
                        this.locationAddress = data && data.display_name ? data.display_name : `Koordinat: ${this.latitude}, ${this.longitude}`;
                        
                        console.log('Address obtained:', this.locationAddress);
                        
                        // Update address field
                        const selectors = [
                            '#intern-address',
                            'input[name="location_address"]',
                            'input[wire\\:model*="location_address"]'
                        ];
                        
                        for (const selector of selectors) {
                            const input = document.querySelector(selector);
                            if (input) {
                                input.value = this.locationAddress;
                                input.dispatchEvent(new Event('input', { bubbles: true }));
                                input.dispatchEvent(new Event('change', { bubbles: true }));
                                console.log('Updated address field:', selector);
                                break;
                            }
                        }
                    })
                    .catch(error => {
                        console.log('Address lookup failed:', error);
                        this.locationAddress = `Koordinat: ${this.latitude}, ${this.longitude}`;
                    });
            }
        },
        
        setupFormInterception() {
            // Intercept form submission to ensure location data is included
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', (e) => {
                    console.log('Form being submitted, ensuring location data is present...');
                    
                    // Double-check that location fields have values
                    if (this.latitude && this.longitude) {
                        this.updateFormFields();
                        
                        // Also try direct Livewire update
                        if (window.Livewire && this.$wire) {
                            try {
                                this.$wire.set('data.latitude', this.latitude);
                                this.$wire.set('data.longitude', this.longitude);
                                this.$wire.set('data.location_address', this.locationAddress);
                                console.log('Updated via Livewire wire');
                            } catch (err) {
                                console.log('Livewire update failed:', err);
                            }
                        }
                    }
                }, true);
            }
        }
    }));
});
</script>
