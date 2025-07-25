import './bootstrap';

// Alpine.js component for location tracking
document.addEventListener('alpine:init', () => {
    Alpine.data('locationTracker', () => ({
        getLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        
                        // Update input fields
                        const latInput = document.querySelector('input[wire\\:model*="latitude"], input[name*="latitude"]');
                        const lngInput = document.querySelector('input[wire\\:model*="longitude"], input[name*="longitude"]');
                        
                        if (latInput && lngInput) {
                            latInput.value = lat;
                            lngInput.value = lng;
                            
                            // Trigger Livewire update
                            latInput.dispatchEvent(new Event('input', { bubbles: true }));
                            lngInput.dispatchEvent(new Event('input', { bubbles: true }));
                        }
                        
                        // Get address from coordinates
                        this.getAddressFromCoordinates(lat, lng);
                    },
                    (error) => {
                        console.error('Error getting location:', error);
                        let errorMessage = 'Tidak dapat mengambil lokasi. ';
                        
                        switch(error.code) {
                            case error.PERMISSION_DENIED:
                                errorMessage += 'Akses lokasi ditolak. Harap izinkan akses lokasi.';
                                break;
                            case error.POSITION_UNAVAILABLE:
                                errorMessage += 'Informasi lokasi tidak tersedia.';
                                break;
                            case error.TIMEOUT:
                                errorMessage += 'Permintaan lokasi timeout.';
                                break;
                            default:
                                errorMessage += 'Terjadi kesalahan yang tidak diketahui.';
                                break;
                        }
                        
                        alert(errorMessage);
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 60000
                    }
                );
            } else {
                alert('Geolocation tidak didukung oleh browser ini.');
            }
        },
        
        getAddressFromCoordinates(lat, lng) {
            // Use OpenStreetMap Nominatim API for reverse geocoding
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&addressdetails=1`)
                .then(response => response.json())
                .then(data => {
                    const addressInput = document.querySelector('input[wire\\:model*="location_address"], input[name*="location_address"]');
                    if (addressInput && data && data.display_name) {
                        let translatedAddress = translateToIndonesian(data.display_name);
                        
                        // Ensure Central Java is correctly translated to Jawa Tengah
                        if (translatedAddress.includes("Central Java") || translatedAddress.toLowerCase().includes("central java")) {
                            translatedAddress = translatedAddress.replace(/Central Java/gi, "Jawa Tengah");
                        }
                        
                        addressInput.value = translatedAddress;
                        addressInput.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                })
                .catch(error => {
                    console.error('Error getting address:', error);
                    const addressInput = document.querySelector('input[wire\\:model*="location_address"], input[name*="location_address"]');
                    if (addressInput) {
                        addressInput.value = `Koordinat: ${lat}, ${lng}`;
                        addressInput.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                });
        }
    }));
});

// Function to translate English address to Indonesian
function translateToIndonesian(address) {
    if (!address) return address;
    
    // Dictionary for English to Indonesian translation
    const translations = {
        // Geographic terms
        "Street": "Jalan",
        "Road": "Jalan", 
        "Avenue": "Jalan",
        "Boulevard": "Jalan",
        "Lane": "Gang",
        "Alley": "Gang",
        "Way": "Jalan",
        "Drive": "Jalan",
        "Circle": "Lingkar",
        "Square": "Lapangan",
        "Court": "Kompleks",
        "Place": "Tempat",
        
        // Administrative divisions
        "Village": "Desa",
        "District": "Kecamatan",
        "Sub-district": "Kelurahan",
        "Subdistrict": "Kelurahan", 
        "City": "Kota",
        "Regency": "Kabupaten",
        "Province": "Provinsi",
        "County": "Kabupaten",
        "Municipality": "Kota",
        "Township": "Kecamatan",
        "Ward": "Kelurahan",
        "Hamlet": "Dusun",
        "Neighborhood": "RT/RW",
        
        // Building types
        "Building": "Gedung",
        "Complex": "Kompleks",
        "Mall": "Mall",
        "Market": "Pasar",
        "Hospital": "Rumah Sakit",
        "School": "Sekolah", 
        "University": "Universitas",
        "Office": "Kantor",
        "Hotel": "Hotel",
        "Restaurant": "Restoran",
        "Bank": "Bank",
        "Store": "Toko",
        "Shop": "Toko",
        "Mosque": "Masjid",
        "Church": "Gereja",
        "Temple": "Kuil",
        "Park": "Taman",
        "Bridge": "Jembatan",
        "Station": "Stasiun",
        "Airport": "Bandara",
        "Port": "Pelabuhan",
        
        // Directions
        "North": "Utara",
        "South": "Selatan",
        "East": "Timur", 
        "West": "Barat",
        "Northeast": "Timur Laut",
        "Northwest": "Barat Laut",
        "Southeast": "Tenggara",
        "Southwest": "Barat Daya",
        "Upper": "Atas",
        "Lower": "Bawah",
        "Inner": "Dalam",
        "Outer": "Luar",
        
        // Indonesian provinces (in case they appear in English) - ORDER MATTERS!
        "Special Region of Yogyakarta": "Daerah Istimewa Yogyakarta",
        "Central Java Province": "Provinsi Jawa Tengah",
        "East Java Province": "Provinsi Jawa Timur", 
        "West Java Province": "Provinsi Jawa Barat",
        "Central Java": "Jawa Tengah",
        "East Java": "Jawa Timur", 
        "West Java": "Jawa Barat",
        "North Sumatra": "Sumatera Utara",
        "South Sumatra": "Sumatera Selatan",
        "West Sumatra": "Sumatera Barat",
        "Jakarta Special Capital Region": "DKI Jakarta",
        "Jakarta": "DKI Jakarta",
        "Yogyakarta": "Daerah Istimewa Yogyakarta",
        "Java": "Jawa",
        "Sumatra": "Sumatera"
    };
    
    let translatedAddress = address;
    
    // Apply translations
    Object.keys(translations).forEach(english => {
        const indonesian = translations[english];
        const regex = new RegExp("\\b" + english + "\\b", "gi");
        translatedAddress = translatedAddress.replace(regex, indonesian);
    });
    
    // Additional formatting
    translatedAddress = translatedAddress
        .replace(/\s+of\s+/gi, " ")
        .replace(/\s+in\s+/gi, ", ")
        .replace(/\s+at\s+/gi, " di ")
        .replace(/\s+/g, " ")
        .trim();
    
    return translatedAddress;
}

// Auto-capture location for interns (silent mode)
document.addEventListener('DOMContentLoaded', function() {
    // Check if this is a journal create page
    if (window.location.pathname.includes('journals/create')) {
        // Wait for form to be fully loaded and try multiple times
        let attempts = 0;
        const maxAttempts = 15;
        
        function tryAutoGeolocation() {
            attempts++;
            
            // Look for intern location fields with specific IDs and classes
            const selectors = [
                '#intern-latitude',
                '.intern-location-field[id*="latitude"]',
                'input[type="hidden"][name*="latitude"]',
                'input[wire\\:model*="latitude"]'
            ];
            
            let latInput = null;
            let lngInput = null;
            let addressInput = null;
            
            // Try to find latitude input
            for (const selector of selectors) {
                latInput = document.querySelector(selector);
                if (latInput) break;
            }
            
            // Try to find longitude input
            for (const selector of selectors.map(s => s.replace('latitude', 'longitude'))) {
                lngInput = document.querySelector(selector);
                if (lngInput) break;
            }
            
            // Try to find address input  
            for (const selector of selectors.map(s => s.replace('latitude', 'address'))) {
                addressInput = document.querySelector(selector);
                if (addressInput) break;
            }
            
            console.log('Attempt', attempts, 'Hidden fields found:', {
                lat: !!latInput,
                lng: !!lngInput, 
                address: !!addressInput,
                latSelector: latInput ? latInput.id || latInput.className : 'none',
                lngSelector: lngInput ? lngInput.id || lngInput.className : 'none',
                addressSelector: addressInput ? addressInput.id || addressInput.className : 'none'
            });
            
            if (latInput && lngInput && addressInput) {
                autoGeolocationForIntern(latInput, lngInput, addressInput);
            } else if (attempts < maxAttempts) {
                // Try again after 500ms
                setTimeout(tryAutoGeolocation, 500);
            } else {
                console.log('Could not find hidden location fields after', maxAttempts, 'attempts');
                // Log all form inputs for debugging
                console.log('All inputs found:');
                document.querySelectorAll('input').forEach((input, index) => {
                    console.log(`Input ${index}:`, {
                        type: input.type,
                        name: input.name,
                        id: input.id,
                        class: input.className,
                        wireModel: input.getAttribute('wire:model')
                    });
                });
            }
        }
        
        // Start trying after 1 second
        setTimeout(tryAutoGeolocation, 1000);
    }
});

function autoGeolocationForIntern(latInput, lngInput, addressInput) {
    if (navigator.geolocation) {
        console.log('Getting geolocation for intern...');
        
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude.toFixed(8);
                const lng = position.coords.longitude.toFixed(8);
                
                console.log('Geolocation success:', { lat, lng });
                
                // Set hidden field values directly
                latInput.value = lat;
                lngInput.value = lng;
                
                // Try multiple approaches to trigger Livewire updates
                const events = ['input', 'change', 'blur', 'keyup'];
                
                events.forEach(eventType => {
                    latInput.dispatchEvent(new Event(eventType, { bubbles: true }));
                    lngInput.dispatchEvent(new Event(eventType, { bubbles: true }));
                });
                
                // Also try direct Livewire approach if possible
                if (window.Livewire && latInput.hasAttribute('wire:model')) {
                    const latModel = latInput.getAttribute('wire:model');
                    const lngModel = lngInput.getAttribute('wire:model');
                    
                    try {
                        window.Livewire.emit('updateLocation', {
                            latitude: lat,
                            longitude: lng,
                            latModel: latModel,
                            lngModel: lngModel
                        });
                    } catch (e) {
                        console.log('Livewire emit failed:', e);
                    }
                }
                
                // Get address silently
                fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&addressdetails=1`)
                    .then(response => response.json())
                    .then(data => {
                        const rawAddress = data && data.display_name ? data.display_name : `Koordinat: ${lat}, ${lng}`;
                        let address = translateToIndonesian(rawAddress);
                        
                        // Ensure Central Java is correctly translated to Jawa Tengah
                        if (address.includes("Central Java") || address.toLowerCase().includes("central java")) {
                            address = address.replace(/Central Java/gi, "Jawa Tengah");
                        }
                        
                        addressInput.value = address;
                        
                        events.forEach(eventType => {
                            addressInput.dispatchEvent(new Event(eventType, { bubbles: true }));
                        });
                        
                        console.log('Address set:', address);
                    })
                    .catch(error => {
                        console.log('Address lookup failed silently:', error);
                        const fallbackAddress = `Koordinat: ${lat}, ${lng}`;
                        addressInput.value = fallbackAddress;
                        
                        events.forEach(eventType => {
                            addressInput.dispatchEvent(new Event(eventType, { bubbles: true }));
                        });
                    });
            },
            function(error) {
                // Fail silently for interns - don't show error messages
                console.log('Geolocation not available for intern:', error);
            },
            {
                enableHighAccuracy: true,
                timeout: 15000,
                maximumAge: 30000
            }
        );
    } else {
        console.log('Geolocation not supported');
    }
}
