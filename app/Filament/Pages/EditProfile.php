<?php
namespace App\Filament\Pages;

use App\Filament\Widgets\ProfileWidget;
use App\Models\Position;
use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
class EditProfile extends Page
{
    use InteractsWithForms;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'User Management';
    protected static ?int $navigationSort = 2;
    protected static string $view = 'filament.pages.edit-profile';
    protected static ?string $title = 'Profile';


    public ?array $data = [];
    
    public function mount(): void
    {
        $user = User::find(Auth::id());
        $this->form->fill([
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone ?? '',
            'ktp' => $user->ktp ?? '',
            'address' => $user->address ?? '',
        ]);
    }
    protected function getHeaderWidgets(): array
    {
        return [
            ProfileWidget::class,
        ];
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->email()
                    ->required(),
                TextInput::make('phone'),
                TextInput::make('ktp'),
                TextInput::make('address'),
            ])
            ->statePath('data');
    }
    
    public function save(): void
    {
        $data = $this->form->getState();
        $user = User::find(Auth::id());
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->phone = $data['phone'] ?? null;
        $user->ktp = $data['ktp'] ?? null;
        $user->address = $data['address'] ?? null;
        $user->save();
        
        Notification::make()
            ->title('Profile updated successfully')
            ->success()
            ->send();
    }
}