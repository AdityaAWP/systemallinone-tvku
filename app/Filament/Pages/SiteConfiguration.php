<?php
namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Actions\Action; // Correct import for header actions
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SiteConfiguration extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static string $view = 'filament.pages.site-configuration';
    protected static ?string $navigationIcon = 'heroicon-o-cog';
    protected static ?string $title = 'Site Configuration';
    
    public ?array $data = [];
    
    public function mount(): void
    {
        $this->form->fill([
            'site_name' => config('app.name'),
            'site_logo' => null, // Load from your settings storage
        ]);
    }
    
    public function listBackups(): void
    {
        $backupDisk = config('backup.backup.destination.disks.0', 'local');
        $disk = Storage::disk($backupDisk);
        
        $backups = collect($disk->files('Laravel'))
            ->map(function ($file) use ($disk) {
                return [
                    'name' => basename($file),
                    'size' => number_format($disk->size($file) / 1024 / 1024, 2) . ' MB',
                    'date' => date('Y-m-d H:i:s', $disk->lastModified($file)),
                ];
            })
            ->sortByDesc('date')
            ->take(10); // Show last 10 backups
            
        $backupCount = $backups->count();
        
        if ($backupCount > 0) {
            $backupList = $backups->map(function ($backup) {
                return "{$backup['name']} ({$backup['size']}) - {$backup['date']}";
            })->join("\n");
            
            Notification::make()
                ->title("Found {$backupCount} backup files")
                ->body($backupList)
                ->info()
                ->persistent() // Makes notification stay longer
                ->send();
        } else {
            Notification::make()
                ->title('No backup files found')
                ->body('No backups have been created yet.')
                ->warning()
                ->send();
        }
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('site_name')
                    ->label('Site Name')
                    ->required(),
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
        
        // Save your configuration here
        // For example, update config cache or database
        
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
            
            $backupPath = storage_path('app/backups');
            if (!file_exists($backupPath)) {
                mkdir($backupPath, 0755, true);
            }
            
            $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
            $filepath = $backupPath . '/' . $filename;
            
            // Create mysqldump command
            $command = sprintf(
                'mysqldump --user=%s --password=%s --host=%s %s > %s',
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($host),
                escapeshellarg($database),
                escapeshellarg($filepath)
            );
            
            // Execute the command
            $output = [];
            $return_var = 0;
            exec($command, $output, $return_var);
            
            if ($return_var === 0) {
                Notification::make()
                    ->title('Database backup completed!')
                    ->body("Backup saved as: {$filename}")
                    ->success()
                    ->send();
            } else {
                throw new \Exception('Backup command failed');
            }
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('Backup failed!')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    protected function getSaveFormAction(): Action
    {
        return Action::make('save')
            ->label('Save Configuration')
            ->submit('save')
            ->color('primary');
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
                ->modalDescription('Are you sure you want to create a database backup?'),
            Action::make('listBackups')
                ->label('View Backups')
                ->action('listBackups')
                ->color('gray')
                ->icon('heroicon-o-folder'),
        ];
    }
}