<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\FileUpload;
use Filament\Actions\Action;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
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
    public array $backups = []; // Property to hold backup data for the table

    public function mount(): void
    {
        // Fill the form with existing data
        $this->form->fill([
            'site_logo' => null, // You would load your saved logo path here
        ]);

        // Load the backup files into the $backups property
        $this->loadBackups();
    }
    
    /**
     * Loads backup file information from storage into the public $backups property.
     */
    public function loadBackups(): void
    {
        $disk = Storage::disk('local');
        $backupPath = 'backups'; // The directory where your backups are stored

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
            ->values() // Reset array keys
            ->all();
    }

    /**
     * Handles the download request for a specific backup file.
     */
    public function downloadBackup(string $filename): ?StreamedResponse
    {
        $path = 'backups/' . $filename;

        // Security check: ensure the file exists in the correct directory
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
                FileUpload::make('site_logo')
                    ->label('Site Logo')
                    ->image()
                    ->directory('logos')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif'])
                    ->maxSize(2048),
            ])
            ->statePath('data');
    }
    
    public function save(): void
    {
        $data = $this->form->getState();
        
        // Save your configuration here...
        
        Notification::make()
            ->title('Configuration saved successfully!')
            ->success()
            ->send();
    }
    
    public function backupDatabase(): void
    {
        try {
            $database = config('database.connections.mysql.database');
            $username = config('database.connections.mysql.username');
            $password = config('database.connections.mysql.password');
            $host = config('database.connections.mysql.host');
            
            // Use Laravel's Storage facade for consistency
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
                
                // Refresh the backup list in the table
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
    
    protected function getFormActions(): array // Renamed from getSaveFormAction for clarity
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
        // Keep your original logic for showing the page
        return Auth::check() && Auth::user() && Auth::user()->hasRole('super_admin');
    }
}