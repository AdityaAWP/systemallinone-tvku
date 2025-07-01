<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\TextInput; // NEW: Import TextInput
use Filament\Forms\Components\FileUpload;
use Filament\Actions\Action;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Artisan; // NEW: Import Artisan facade
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SiteConfiguration extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static string $view = 'filament.pages.site-configuration';
    protected static ?string $navigationIcon = 'heroicon-o-cog';
    protected static ?string $title = 'Settings Configuration';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 5;
    
    public ?array $data = [];
    public array $backups = [];

    public function mount(): void
    {
        // UPDATED: Now fills the form with the current site name from the config
        $this->form->fill([
            'site_name' => config('app.name'),
            'site_logo' => null,
        ]);

        $this->loadBackups();
    }
    
    public function loadBackups(): void
    {
        $disk = Storage::disk('local');
        $backupPath = 'backups';

        $files = $disk->files($backupPath);

        $this->backups = collect($files)
            ->map(function ($file) use ($disk) {
                return [
                    'name' => basename($file),
                    'size' => number_format($disk->size($file) / 1024, 2) . ' KB',
                    'date' => date('Y-m-d H:i:s', $disk->lastModified($file)),
                    'path' => $file,
                ];
            })
            ->sortByDesc('date')
            ->values()
            ->all();
    }

    public function downloadBackup(string $filename): ?StreamedResponse
    {
        $path = 'backups/' . $filename;

        if (!Storage::disk('local')->exists($path)) {
            Notification::make()
                ->title('File Not Found')
                ->body('The requested backup file could not be found.')
                ->danger()
                ->send();
            return null;
        }

        return Storage::disk('local')->download($path);
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // NEW: Added a text input for the site name
                TextInput::make('site_name')
                    ->label('Site Name')
                    ->required()
                    ->maxLength(50),
                
                FileUpload::make('site_logo')
                    ->label('Site Logo')
                    ->image()
                    ->directory('logos')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif'])
                    ->maxSize(2048),
            ])
            ->statePath('data');
    }
    
    // UPDATED: The save method now handles updating the .env file
    public function save(): void
    {
        $data = $this->form->getState();

        // --- Save Site Name ---
        $this->updateEnvFile('APP_NAME', $data['site_name']);
        
        // --- Save Site Logo (example logic) ---
        if (!empty($data['site_logo'])) {
            // Save the path $data['site_logo'] to your settings database or file
            // For example: Setting::set('site_logo', $data['site_logo']);
        }

        // Clear the config cache to apply changes
        Artisan::call('config:clear');

        Notification::make()
            ->title('Configuration saved successfully!')
            ->success()
            ->send();
            
        // Reload the page to see the new site name in the panel
        $this->js('window.location.reload()');
    }

    /**
     * NEW: Helper function to update a key-value pair in the .env file.
     */
    private function updateEnvFile(string $key, string $value): void
    {
        $envFilePath = base_path('.env');
        $envFileContent = file_get_contents($envFilePath);

        // To prevent issues with values containing spaces or special characters
        $escapedValue = '"' . addcslashes($value, '"\\') . '"';

        $keyToFind = "{$key}=";
        
        if (str_contains($envFileContent, $keyToFind)) {
            // Key exists, replace it
            $envFileContent = preg_replace("/^{$key}=.*/m", "{$key}={$escapedValue}", $envFileContent);
        } else {
            // Key does not exist, append it
            $envFileContent .= "\n{$key}={$escapedValue}\n";
        }
        
        file_put_contents($envFilePath, $envFileContent);
    }
    
    public function backupDatabase(): void
    {
        // ... (your backupDatabase method remains unchanged)
        try {
            $database = config('database.connections.mysql.database');
            $username = config('database.connections.mysql.username');
            $password = config('database.connections.mysql.password');
            $host = config('database.connections.mysql.host');
            
            $backupDisk = 'local';
            $backupDir = 'backups';
            Storage::disk($backupDisk)->makeDirectory($backupDir);
            
            $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
            $filepath = Storage::disk($backupDisk)->path($backupDir . '/' . $filename);
            
            $command = sprintf(
                'mysqldump --user=%s --password=%s --host=%s %s > %s',
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($host),
                escapeshellarg($database),
                escapeshellarg($filepath)
            );
            
            $output = [];
            $return_var = 0;
            exec($command, $output, $return_var);
            
            if ($return_var === 0) {
                Notification::make()
                    ->title('Database backup completed!')
                    ->body("Backup saved as: {$filename}")
                    ->success()
                    ->send();
                
                $this->loadBackups();
            } else {
                throw new \Exception('The `mysqldump` command failed. Ensure it is installed and in your system\'s PATH.');
            }
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('Backup failed!')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Configuration')
                ->submit('save')
                ->keyBindings(['mod+s']),
        ];
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Action::make('backup')
                ->label('Backup Database')
                ->action('backupDatabase')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Backup Database')
                ->modalDescription('Are you sure you want to create a new database backup? This may take a few moments.'),
        ];
    }
    
    public static function shouldRegisterNavigation(): bool
    {
        return Auth::check() && Auth::user() && Auth::user()->hasRole('super_admin');
    }
}