<?php

namespace App\Filament\Resources\JournalResource\Pages;

use App\Filament\Resources\JournalResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CreateJournal extends CreateRecord
{
    protected static string $resource = JournalResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set intern_id
        $data['intern_id'] = Auth::id();
        
        // For intern users, try to get location from browser if not already set
        if (Auth::guard('intern')->check()) {
            // Initialize location fields as null if not set
            if (!isset($data['latitude'])) $data['latitude'] = null;
            if (!isset($data['longitude'])) $data['longitude'] = null;
            if (!isset($data['location_address'])) $data['location_address'] = null;
            
            // Log the data being saved for debugging
            Log::info('Journal data before create for intern:', [
                'intern_id' => $data['intern_id'],
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'location_address' => $data['location_address'],
                'status' => $data['status'] ?? 'not_set',
                'entry_date' => $data['entry_date'] ?? 'not_set',
                'all_data_keys' => array_keys($data)
            ]);
            
            // Validate location data
            if ($data['latitude'] && $data['longitude']) {
                $lat = floatval($data['latitude']);
                $lng = floatval($data['longitude']);
                
                // Basic validation for realistic coordinates
                if ($lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180) {
                    Log::info('Valid location coordinates received', [
                        'latitude' => $lat,
                        'longitude' => $lng,
                        'is_indonesia_region' => ($lat >= -11 && $lat <= 6 && $lng >= 95 && $lng <= 141)
                    ]);
                } else {
                    Log::warning('Invalid location coordinates received', [
                        'latitude' => $lat,
                        'longitude' => $lng
                    ]);
                    // Reset invalid coordinates
                    $data['latitude'] = null;
                    $data['longitude'] = null;
                    $data['location_address'] = 'Koordinat tidak valid';
                }
            } else {
                Log::warning('No location data received from browser');
            }
        }
        
        return $data;
    }
    
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
    
    protected function getViewData(): array
    {
        return [
            'needsGeolocation' => Auth::guard('intern')->check(),
        ];
    }
    
    protected function getHeaderActions(): array
    {
        return [];
    }
    
    public function getHeading(): string
    {
        if (Auth::guard('intern')->check()) {
            return 'Buat Jurnal Harian';
        }
        return parent::getHeading();
    }
}
