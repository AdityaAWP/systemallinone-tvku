<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JournalResource\Pages;
use App\Models\Journal;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Unique; 

class JournalResource extends Resource
{
    protected static ?string $model = Journal::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?int $navigationSort = 5;

    public static function getNavigationSort(): ?int
    {
        if (Auth::guard('intern')->check()) {
            return -2;
        }
        return static::$navigationSort;
    }

    public static function getNavigationGroup(): ?string
    {
        if (Auth::guard('intern')->check()) {
            return 'Main Menu';
        }
        return 'Manajemen Magang';
    }

    public static function getNavigationLabel(): string
    {
        if (Auth::guard('intern')->check()) {
            return 'Rangkuman Jurnal';
        }
        return 'Jurnal Magang';
    }

    public static function getModelLabel(): string
    {
        if (Auth::guard('intern')->check()) {
            return 'Rangkuman Jurnal';
        }
        return 'Jurnal Magang';
    }

    public static function canViewAny(): bool
    {
        if (Auth::guard('intern')->check()) {
            return true;
        }

        if (Auth::guard('web')->check()) {
            /** @var User $user */
            $user = Auth::guard('web')->user();
            return $user->hasRole('admin_magang') || 
                   ($user->canSuperviseInterns() && \App\Models\Intern::where('supervisor_id', $user->id)->exists());
        }

        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        if (Auth::guard('intern')->check()) {
            return true;
        }

        if (Auth::guard('web')->check()) {
            /** @var User $user */
            $user = Auth::guard('web')->user();
            return $user->hasRole('admin_magang');
        }

        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (Auth::guard('intern')->check()) {
            return $query->where('intern_id', Auth::guard('intern')->id());
        }

        if (Auth::guard('web')->check()) {
            /** @var User $user */
            $user = Auth::guard('web')->user();
            
            if ($user->hasRole('admin_magang')) {
                return $query;
            }
            
            if ($user->canSuperviseInterns()) {
                return $query->whereHas('intern', function (Builder $subQuery) use ($user) {
                    $subQuery->where('supervisor_id', $user->id);
                });
            }
        }

        return $query->whereRaw('1 = 0');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('intern_id')
                    ->label('Nama Magang')
                    ->relationship('intern', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live() 
                    ->visible(fn() => Auth::guard('web')->check())
                    ->default(null),
                Hidden::make('intern_id')
                    ->default(fn() => Auth::guard('intern')->check() ? Auth::guard('intern')->id() : null)
                    ->visible(fn() => Auth::guard('intern')->check()),
                DatePicker::make('entry_date')
                    ->label('Tanggal')
                    ->required()
                    ->default(now())
                    ->unique(
                        table: Journal::class,
                        column: 'entry_date',
                        modifyRuleUsing: function (Unique $rule, Forms\Get $get) {
                            $internId = Auth::guard('intern')->check()
                                ? Auth::guard('intern')->id()
                                : $get('intern_id');
                            if (!$internId) {
                                return $rule;
                            }
                            return $rule->where('intern_id', $internId);
                        }
                    )
                    ->validationMessages([
                        'unique' => 'Anda sudah membuat laporan untuk tanggal ini.',
                    ]),
                Select::make('status')
                    ->options([
                        'Hadir' => 'Hadir',
                        'Izin' => 'Izin',
                        'Sakit' => 'Sakit',
                    ])
                    ->default('Hadir')
                    ->live()
                    ->required(),
                TimePicker::make('start_time')
                    ->label('Waktu Mulai')
                    ->seconds(false)
                    ->visible(fn(Forms\Get $get): bool => $get('status') === 'Hadir')
                    ->required(fn(Forms\Get $get): bool => $get('status') === 'Hadir'),
                TimePicker::make('end_time')
                    ->label('Waktu Selesai')
                    ->seconds(false)
                    ->visible(fn(Forms\Get $get): bool => $get('status') === 'Hadir')
                    ->required(fn(Forms\Get $get): bool => $get('status') === 'Hadir'),
                Textarea::make('activity')
                    ->label('Aktivitas')
                    ->visible(fn(Forms\Get $get): bool => $get('status') === 'Hadir')
                    ->required(fn(Forms\Get $get): bool => $get('status') === 'Hadir'),
                FileUpload::make('image')
                    ->image()
                    ->directory('journal-images')
                    ->preserveFilenames()
                    ->nullable()
                    ->label('Bukti Gambar')
                    ->visible(fn(Forms\Get $get): bool => $get('status') === 'Hadir'),
                Textarea::make('reason_of_absence')
                    ->label('Alasan Ketidakhadiran')
                    ->visible(fn(Forms\Get $get): bool => in_array($get('status'), ['Izin', 'Sakit']))
                    ->required(fn(Forms\Get $get): bool => in_array($get('status'), ['Izin', 'Sakit']))
                    ->placeholder('Silakan isi alasan ketidakhadiran'),
                
                \Filament\Forms\Components\Placeholder::make('location_capture')
                    ->content('')
                    ->visible(fn(): bool => Auth::guard('intern')->check())
                    ->extraAttributes([
                        'x-data' => '{
                            locationPermissionGranted: false,
                            init() {
                                console.log("Location capture initialized for intern");
                                this.disableSubmitButton();
                                this.$nextTick(() => {
                                    setTimeout(() => {
                                        this.checkLocationPermission();
                                    }, 1000);
                                });

                                // Listen for form submission
                                const form = this.$el.closest("form");
                                if (form) {
                                    form.addEventListener("submit", (event) => {
                                        if (!this.locationPermissionGranted) {
                                            event.preventDefault();
                                            event.stopPropagation();
                                            alert("Akses lokasi diperlukan untuk membuat jurnal. Harap izinkan akses lokasi di browser Anda dan coba lagi.");
                                            this.checkLocationPermission();
                                        }
                                    });
                                }
                            },
                            disableSubmitButton() {
                                const form = this.$el.closest("form");
                                const submitButton = form.querySelector("button[type=submit]");
                                if (submitButton) {
                                    submitButton.disabled = true;
                                    submitButton.style.opacity = "0.5";
                                    submitButton.style.cursor = "not-allowed";
                                    submitButton.addEventListener("mouseover", () => {
                                        if (!this.locationPermissionGranted) {
                                            alert("Tombol Buat dinonaktifkan karena akses lokasi ditolak. Harap izinkan akses lokasi di pengaturan browser.");
                                        }
                                    });
                                }
                            },
                            enableSubmitButton() {
                                const form = this.$el.closest("form");
                                const submitButton = form.querySelector("button[type=submit]");
                                if (submitButton) {
                                    submitButton.disabled = false;
                                    submitButton.style.opacity = "1";
                                    submitButton.style.cursor = "pointer";
                                    // Remove mouseover event listener to prevent alert after permission is granted
                                    submitButton.removeEventListener("mouseover", this.handleMouseOver);
                                }
                            },
                            checkLocationPermission() {
                                console.log("Checking location permission...");
                                
                                if (!navigator.geolocation) {
                                    console.error("Geolocation not supported");
                                    alert("Browser Anda tidak mendukung geolocation. Harap gunakan browser yang mendukung fitur ini.");
                                    this.disableSubmitButton();
                                    return;
                                }
                                
                                if (typeof navigator.permissions !== "undefined") {
                                    navigator.permissions.query({name: "geolocation"}).then((permission) => {
                                        console.log("Geolocation permission:", permission.state);
                                        if (permission.state === "granted") {
                                            this.locationPermissionGranted = true;
                                            this.enableSubmitButton();
                                            this.getCurrentPosition();
                                        } else if (permission.state === "prompt") {
                                            this.locationPermissionGranted = false;
                                            this.disableSubmitButton();
                                            alert("Akses lokasi diperlukan. Harap izinkan akses lokasi saat diminta oleh browser.");
                                            this.getCurrentPosition();
                                        } else {
                                            this.locationPermissionGranted = false;
                                            this.disableSubmitButton();
                                            alert("Akses lokasi ditolak. Harap izinkan akses lokasi di pengaturan browser untuk melanjutkan.");
                                        }
                                    });
                                } else {
                                    this.getCurrentPosition();
                                }
                            },
                            getCurrentPosition() {
                                const options = {
                                    enableHighAccuracy: true,
                                    timeout: 20000,
                                    maximumAge: 0
                                };
                                
                                console.log("Getting current position with options:", options);
                                
                                navigator.geolocation.getCurrentPosition(
                                    (position) => {
                                        console.log("Raw position data:", position);
                                        
                                        const lat = parseFloat(position.coords.latitude.toFixed(8));
                                        const lng = parseFloat(position.coords.longitude.toFixed(8));
                                        const accuracy = position.coords.accuracy;
                                        
                                        console.log("Processed location:", { 
                                            lat, 
                                            lng, 
                                            accuracy: accuracy + " meters",
                                            timestamp: new Date(position.timestamp)
                                        });
                                        
                                        if (accuracy > 100) {
                                            console.warn("Location accuracy is low:", accuracy + " meters");
                                            setTimeout(() => {
                                                this.getCurrentPositionHighAccuracy();
                                            }, 2000);
                                            return;
                                        }
                                        
                                        this.locationPermissionGranted = true;
                                        this.enableSubmitButton();
                                        this.setLocationData(lat, lng, accuracy);
                                    },
                                    (error) => {
                                        console.error("Geolocation error:", error);
                                        this.handleLocationError(error);
                                        this.locationPermissionGranted = false;
                                        this.disableSubmitButton();
                                    },
                                    options
                                );
                            },
                            getCurrentPositionHighAccuracy() {
                                console.log("Attempting high accuracy location...");
                                
                                const highAccuracyOptions = {
                                    enableHighAccuracy: true,
                                    timeout: 30000,
                                    maximumAge: 0
                                };
                                
                                navigator.geolocation.getCurrentPosition(
                                    (position) => {
                                        const lat = parseFloat(position.coords.latitude.toFixed(8));
                                        const lng = parseFloat(position.coords.longitude.toFixed(8));
                                        const accuracy = position.coords.accuracy;
                                        
                                        console.log("High accuracy location:", { 
                                            lat, 
                                            lng, 
                                            accuracy: accuracy + " meters" 
                                        });
                                        
                                        this.locationPermissionGranted = true;
                                        this.enableSubmitButton();
                                        this.setLocationData(lat, lng, accuracy);
                                    },
                                    (error) => {
                                        console.error("High accuracy geolocation failed:", error);
                                        this.getCurrentPositionFallback();
                                        this.locationPermissionGranted = false;
                                        this.disableSubmitButton();
                                    },
                                    highAccuracyOptions
                                );
                            },
                            getCurrentPositionFallback() {
                                console.log("Using fallback location method...");
                                
                                const fallbackOptions = {
                                    enableHighAccuracy: false,
                                    timeout: 15000,
                                    maximumAge: 300000
                                };
                                
                                navigator.geolocation.getCurrentPosition(
                                    (position) => {
                                        const lat = parseFloat(position.coords.latitude.toFixed(8));
                                        const lng = parseFloat(position.coords.longitude.toFixed(8));
                                        const accuracy = position.coords.accuracy;
                                        
                                        console.log("Fallback location:", { lat, lng, accuracy: accuracy + " meters" });
                                        
                                        this.locationPermissionGranted = true;
                                        this.enableSubmitButton();
                                        this.setLocationData(lat, lng, accuracy);
                                    },
                                    (error) => {
                                        console.error("All geolocation attempts failed:", error);
                                        this.handleLocationError(error);
                                        this.locationPermissionGranted = false;
                                        this.disableSubmitButton();
                                    },
                                    fallbackOptions
                                );
                            },
                            setLocationData(lat, lng, accuracy) {
                                if (this.$wire) {
                                    this.$wire.set("data.latitude", lat);
                                    this.$wire.set("data.longitude", lng);
                                    
                                    console.log("Location data set via Livewire:", { lat, lng });
                                    
                                    this.getDetailedAddress(lat, lng, accuracy);
                                } else {
                                    console.error("Livewire wire not available");
                                }
                            },
                            getDetailedAddress(lat, lng, accuracy) {
                                console.log("Getting address for coordinates:", { lat, lng });
                                
                                const geocodingPromises = [
                                    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&addressdetails=1&zoom=18`)
                                        .then(response => response.json())
                                        .then(data => ({
                                            service: "OpenStreetMap",
                                            address: data?.display_name || null,
                                            details: data?.address || null
                                        }))
                                        .catch(() => ({ service: "OpenStreetMap", address: null }))
                                ];
                                
                                Promise.allSettled(geocodingPromises).then(results => {
                                    let bestAddress = null;
                                    
                                    for (const result of results) {
                                        if (result.status === "fulfilled" && result.value.address) {
                                            bestAddress = result.value.address;
                                            console.log("Address from", result.value.service + ":", bestAddress);
                                            break;
                                        }
                                    }
                                    
                                    if (!bestAddress) {
                                        bestAddress = `Koordinat: ${lat}, ${lng} (Akurasi: ${accuracy}m)`;
                                        console.log("Using fallback address:", bestAddress);
                                    } else {
                                        bestAddress = this.translateAddressToIndonesian(bestAddress);
                                    }
                                    
                                    if (this.$wire) {
                                        this.$wire.set("data.location_address", bestAddress);
                                        console.log("Address set via Livewire:", bestAddress);
                                    }
                                });
                            },
                            translateAddressToIndonesian(address) {
                                if (!address) return address;
                                
                                const translations = {
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
                                    "Jakarta": "DKI Jakarta",
                                    "Java": "Jawa",
                                    "Central Java": "Jawa Tengah",
                                    "East Java": "Jawa Timur",
                                    "West Java": "Jawa Barat",
                                    "Sumatra": "Sumatera",
                                    "Kalimantan": "Kalimantan",
                                    "Sulawesi": "Sulawesi",
                                    "Papua": "Papua",
                                    "Bali": "Bali",
                                    "Lombok": "Lombok",
                                    "Yogyakarta": "Daerah Istimewa Yogyakarta",
                                    "Special Region of Yogyakarta": "Daerah Istimewa Yogyakarta",
                                    "First": "Pertama",
                                    "Second": "Kedua",
                                    "Third": "Ketiga",
                                    "Fourth": "Keempat",
                                    "Fifth": "Kelima",
                                    "One": "Satu",
                                    "Two": "Dua",
                                    "Three": "Tiga",
                                    "Four": "Empat",
                                    "Five": "Lima"
                                };
                                
                                let translatedAddress = address;
                                
                                Object.keys(translations).forEach(english => {
                                    const indonesian = translations[english];
                                    const regex = new RegExp("\\b" + english + "\\b", "gi");
                                    translatedAddress = translatedAddress.replace(regex, indonesian);
                                });
                                
                                translatedAddress = translatedAddress
                                    .replace(/\s+of\s+/gi, " ")
                                    .replace(/\s+in\s+/gi, ", ")
                                    .replace(/\s+at\s+/gi, " di ")
                                    .replace(/\s+/g, " ")
                                    .trim();
                                
                                console.log("Address translation:", { original: address, translated: translatedAddress });
                                
                                return translatedAddress;
                            },
                            handleLocationError(error) {
                                let errorMessage = "Tidak dapat mengambil lokasi. ";
                                
                                switch(error.code) {
                                    case error.PERMISSION_DENIED:
                                        errorMessage += "Akses lokasi ditolak. Harap izinkan akses lokasi di pengaturan browser.";
                                        break;
                                    case error.POSITION_UNAVAILABLE:
                                        errorMessage += "Informasi lokasi tidak tersedia. Pastikan GPS aktif.";
                                        break;
                                    case error.TIMEOUT:
                                        errorMessage += "Permintaan lokasi timeout. Coba lagi.";
                                        break;
                                    default:
                                        errorMessage += "Terjadi kesalahan yang tidak diketahui.";
                                        break;
                                }
                                
                                console.error("Location error:", errorMessage);
                                
                                if (this.$wire) {
                                    this.$wire.set("data.location_address", "Lokasi tidak tersedia - " + new Date().toLocaleString());
                                }
                                
                                this.locationPermissionGranted = false;
                                this.disableSubmitButton();
                            }
                        }'
                    ]),
                Hidden::make('latitude')
                    ->visible(fn(): bool => Auth::guard('intern')->check())
                    ->default(null)
                    ->extraAttributes([
                        'id' => 'intern-latitude',
                        'class' => 'intern-location-field'
                    ]),
                Hidden::make('longitude')
                    ->visible(fn(): bool => Auth::guard('intern')->check())
                    ->default(null)
                    ->extraAttributes([
                        'id' => 'intern-longitude',
                        'class' => 'intern-location-field'
                    ]),
                Hidden::make('location_address')
                    ->visible(fn(): bool => Auth::guard('intern')->check())
                    ->default(null)
                    ->extraAttributes([
                        'id' => 'intern-address',
                        'class' => 'intern-location-field'
                    ]),
                Section::make('Informasi Lokasi')
                    ->description('Lokasi akan diambil secara otomatis untuk monitoring kehadiran')
                    ->schema([
                        TextInput::make('latitude')
                            ->label('Latitude')
                            ->numeric()
                            ->step('any')
                            ->placeholder('Mengambil lokasi...')
                            ->extraInputAttributes([
                                'readonly' => true,
                            ]),
                        TextInput::make('longitude')
                            ->label('Longitude')
                            ->numeric()
                            ->step('any')
                            ->placeholder('Mengambil lokasi...')
                            ->extraInputAttributes([
                                'readonly' => true,
                            ]),
                        TextInput::make('location_address')
                            ->label('Alamat Lokasi')
                            ->placeholder('Mengambil alamat...')
                            ->extraInputAttributes([
                                'readonly' => true,
                            ]),
                    ])
                    ->visible(function(Forms\Get $get): bool {
                        if (Auth::guard('intern')->check()) {
                            return false;
                        }
                        return Auth::guard('web')->check() && $get('status') === 'Hadir';
                    })
                    ->collapsible()
                    ->extraAttributes([
                        'x-data' => '{
                            init() {
                                this.$nextTick(() => {
                                    this.getLocation();
                                });
                            },
                            getLocation() {
                                if (navigator.geolocation) {
                                    navigator.geolocation.getCurrentPosition(
                                        (position) => {
                                            const lat = position.coords.latitude.toFixed(8);
                                            const lng = position.coords.longitude.toFixed(8);
                                            
                                            const form = this.$el.closest("form");
                                            const latInput = form.querySelector("input[data-field-name=\"latitude\"], input[wire\\:model*=\"latitude\"]");
                                            const lngInput = form.querySelector("input[data-field-name=\"longitude\"], input[wire\\:model*=\"longitude\"]");
                                            
                                            if (latInput && lngInput) {
                                                latInput.value = lat;
                                                lngInput.value = lng;
                                                
                                                latInput.dispatchEvent(new Event("input", { bubbles: true }));
                                                lngInput.dispatchEvent(new Event("input", { bubbles: true }));
                                                
                                                this.getAddressFromCoordinates(lat, lng, form);
                                            }
                                        },
                                        (error) => {
                                            console.error("Geolocation error:", error);
                                            const form = this.$el.closest("form");
                                            const latInput = form.querySelector("input[data-field-name=\"latitude\"], input[wire\\:model*=\"latitude\"]");
                                            const lngInput = form.querySelector("input[data-field-name=\"longitude\"], input[wire\\:model*=\"longitude\"]");
                                            const addressInput = form.querySelector("input[data-field-name=\"location_address\"], input[wire\\:model*=\"location_address\"]");
                                            
                                            if (latInput) latInput.placeholder = "Gagal mengambil lokasi";
                                            if (lngInput) lngInput.placeholder = "Gagal mengambil lokasi";
                                            if (addressInput) addressInput.placeholder = "Gagal mengambil alamat";
                                            
                                            alert("Tidak dapat mengambil lokasi. Pastikan GPS aktif dan izinkan akses lokasi.");
                                        },
                                        {
                                            enableHighAccuracy: true,
                                            timeout: 10000,
                                            maximumAge: 60000
                                        }
                                    );
                                } else {
                                    alert("Browser tidak mendukung geolocation.");
                                }
                            },
                            getAddressFromCoordinates(lat, lng, form) {
                                const addressInput = form.querySelector("input[data-field-name=\"location_address\"], input[wire\\:model*=\"location_address\"]");
                                if (!addressInput) return;
                                
                                fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&addressdetails=1&zoom=18`)
                                    .then(response => response.json())
                                    .then(data => {
                                        let address = data && data.display_name ? data.display_name : `Koordinat: ${lat}, ${lng}`;
                                        address = this.translateAddressToIndonesian(address);
                                        if (address.includes("Central Java") || address.includes("Jawa Tengah") || address.toLowerCase().includes("central java")) {
                                            address = address.replace(/Central Java/gi, "Jawa Tengah");
                                        }
                                        addressInput.value = address;
                                        addressInput.dispatchEvent(new Event("input", { bubbles: true }));
                                    })
                                    .catch(error => {
                                        console.error("Address lookup error:", error);
                                        addressInput.value = `Koordinat: ${lat}, ${lng}`;
                                        addressInput.dispatchEvent(new Event("input", { bubbles: true }));
                                    });
                            },
                            translateAddressToIndonesian(address) {
                                if (!address) return address;
                                
                                const translations = {
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
                                
                                Object.keys(translations).forEach(english => {
                                    const indonesian = translations[english];
                                    const regex = new RegExp("\\b" + english + "\\b", "gi");
                                    translatedAddress = translatedAddress.replace(regex, indonesian);
                                });
                                
                                translatedAddress = translatedAddress
                                    .replace(/\s+of\s+/gi, " ")
                                    .replace(/\s+in\s+/gi, ", ")
                                    .replace(/\s+at\s+/gi, " di ")
                                    .replace(/\s+/g, " ")
                                    .trim();
                                
                                return translatedAddress;
                            }
                        }'
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('intern.fullname')
                    ->label('Nama Magang')
                    ->searchable()
                    ->sortable()
                    ->visible(fn() => Auth::guard('web')->check()),
                TextColumn::make('entry_date')
                    ->date()
                    ->label('Tanggal')
                    ->sortable(),
                TextColumn::make('start_time')
                    ->time('H:i')
                    ->label('Waktu Mulai')
                    ->sortable(),
                TextColumn::make('end_time')
                    ->time('H:i')
                    ->label('Waktu Selesai')
                    ->sortable(),
                TextColumn::make('activity')
                    ->label('Aktivitas')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),
                TextColumn::make('reason_of_absence')
                    ->label('Alasan Ketidakhadiran')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Hadir' => 'success',
                        'Izin' => 'warning',
                        'Sakit' => 'danger',
                    }),
                ImageColumn::make('image')
                    ->label('Bukti Gambar')
                    ->circular(),
                TextColumn::make('location_address')
                    ->label('Lokasi')
                    ->color('primary')
                    ->url(
                        fn (Journal $record): ?string => ($record->latitude && $record->longitude) 
                            ? "https://www.google.com/maps?q={$record->latitude},{$record->longitude}" 
                            : null,
                        shouldOpenInNewTab: true 
                    )
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 30) {
                            return null;
                        }
                        return $state;
                    })
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->visible(function(): bool {
                        if (Auth::guard('intern')->check()) {
                            return false;
                        }
                        if (Auth::guard('web')->check()) {
                            $user = Auth::guard('web')->user();
                            return $user instanceof \App\Models\User && 
                                   ($user->hasRole('admin_magang') || $user->canSuperviseInterns());
                        }
                        return false;
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'Hadir' => 'Hadir',
                        'Izin' => 'Izin',
                        'Sakit' => 'Sakit',
                    ]),
                Tables\Filters\SelectFilter::make('intern_id')
                    ->label('Nama Magang')
                    ->relationship('intern', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn() => Auth::guard('web')->check()),
                Tables\Filters\Filter::make('entry_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('entry_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('entry_date', '<=', $date),
                            );
                    }),
            ])
            ->headerActions([
                Tables\Actions\Action::make('downloadAll')
                    ->label('Download Semua')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->visible(fn() => Auth::guard('intern')->check())
                    ->url(fn() => route('journal.report'))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('downloadMonthly')
                    ->label('Download Bulanan')
                    ->icon('heroicon-o-calendar')
                    ->color('primary')
                    ->visible(fn() => Auth::guard('intern')->check())
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('month')
                                    ->label('Bulan')
                                    ->options([
                                        1 => 'Januari',
                                        2 => 'Februari',
                                        3 => 'Maret',
                                        4 => 'April',
                                        5 => 'Mei',
                                        6 => 'Juni',
                                        7 => 'Juli',
                                        8 => 'Agustus',
                                        9 => 'September',
                                        10 => 'Oktober',
                                        11 => 'November',
                                        12 => 'Desember',
                                    ])
                                    ->default(Carbon::now()->month)
                                    ->required(),
                                Forms\Components\TextInput::make('year')
                                    ->label('Tahun')
                                    ->numeric()
                                    ->default(Carbon::now()->year)
                                    ->minValue(2020)
                                    ->maxValue(2030)
                                    ->required(),
                            ])
                    ])
                    ->action(function (array $data) {
                        $url = route('journal.monthly') . '?' . http_build_query([
                            'month' => $data['month'],
                            'year' => $data['year']
                        ]);
                        return redirect()->away($url);
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('downloadReport')
                    ->label('Download Laporan')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->tooltip('Download laporan bulanan untuk magang ini')
                    ->visible(function(): bool {
                        if (!Auth::guard('web')->check()) return false;
                        $user = Auth::guard('web')->user();
                        return $user instanceof \App\Models\User && $user->hasRole(['admin_magang', 'super_admin']);
                    })
                    ->modalHeading(fn(Journal $record) => 'Download Laporan - ' . $record->intern->name)
                    ->form([
                        Forms\Components\Select::make('month')
                            ->label('Bulan')
                            ->options([
                                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                            ])
                            ->default(Carbon::now()->month)
                            ->required(),
                        Forms\Components\TextInput::make('year')
                            ->label('Tahun')
                            ->numeric()
                            ->default(Carbon::now()->year)
                            ->minValue(2020)
                            ->maxValue(2030)
                            ->required(),
                    ])
                    ->action(function (Journal $record, array $data) {
                        $url = route('journal.report.user') . '?' . http_build_query([
                            'intern_id' => $record->intern_id,
                            'month' => $data['month'],
                            'year' => $data['year']
                        ]);
                        return redirect()->to($url, true);
                    }),
                Tables\Actions\EditAction::make()
                    ->visible(function(): bool {
                        if (Auth::guard('intern')->check()) return true;
                        if (!Auth::guard('web')->check()) return false;
                        $user = Auth::guard('web')->user();
                        return $user instanceof \App\Models\User && $user->hasRole('admin_magang');
                    }),
                Tables\Actions\DeleteAction::make()
                    ->visible(function(): bool {
                        if (Auth::guard('intern')->check()) return true;
                        if (!Auth::guard('web')->check()) return false;
                        $user = Auth::guard('web')->user();
                        return $user instanceof \App\Models\User && $user->hasRole('admin_magang');
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(function(): bool {
                            if (!Auth::guard('web')->check()) return false;
                            $user = Auth::guard('web')->user();
                            return $user instanceof \App\Models\User && $user->hasRole('admin_magang');
                        }),
                ]),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                if (Auth::guard('intern')->check()) {
                    return $query->where('intern_id', Auth::guard('intern')->id());
                }
                return $query;
            });
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJournals::route('/'),
            'create' => Pages\CreateJournal::route('/create'),
            'edit' => Pages\EditJournal::route('/{record}/edit'),
        ];
    }
}