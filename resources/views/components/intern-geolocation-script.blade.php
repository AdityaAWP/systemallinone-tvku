<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-capture location for interns without showing UI
    if (window.location.pathname.includes('journals/create')) {
        setTimeout(function() {
            captureLocationForIntern();
        }, 1000);
    }
    
    function captureLocationForIntern() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const lat = position.coords.latitude.toFixed(8);
                    const lng = position.coords.longitude.toFixed(8);
                    
                    // Find hidden inputs for intern
                    const latInput = document.querySelector('input[wire\\:model*="latitude"]');
                    const lngInput = document.querySelector('input[wire\\:model*="longitude"]');
                    
                    if (latInput && lngInput) {
                        latInput.value = lat;
                        lngInput.value = lng;
                        
                        // Trigger Livewire update
                        latInput.dispatchEvent(new Event('input', { bubbles: true }));
                        lngInput.dispatchEvent(new Event('input', { bubbles: true }));
                        
                        // Get address silently
                        getAddressForIntern(lat, lng);
                    }
                },
                function(error) {
                    console.log('Geolocation tidak tersedia:', error);
                    // Fail silently for interns - don't show error
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 60000
                }
            );
        }
    }
    
    function getAddressForIntern(lat, lng) {
        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&addressdetails=1`)
            .then(response => response.json())
            .then(data => {
                const addressInput = document.querySelector('input[wire\\:model*="location_address"]');
                if (addressInput) {
                    const address = data && data.display_name ? data.display_name : `Koordinat: ${lat}, ${lng}`;
                    addressInput.value = address;
                    addressInput.dispatchEvent(new Event('input', { bubbles: true }));
                }
            })
            .catch(error => {
                console.log('Address lookup failed:', error);
                const addressInput = document.querySelector('input[wire\\:model*="location_address"]');
                if (addressInput) {
                    addressInput.value = `Koordinat: ${lat}, ${lng}`;
                    addressInput.dispatchEvent(new Event('input', { bubbles: true }));
                }
            });
    }
});
</script>
