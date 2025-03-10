<?php
namespace App\Filament\Pages;
use App\Models\Position;
use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
class EditProfile extends Page
{
    use InteractsWithForms;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.pages.edit-profile';
    public ?array $data = [];
    
    public function mount(): void
    {
        $user = Auth::user();
        $this->form->fill([
            'name' => $user->name,
            'email' => $user->email,
            'no_phone' => $user->no_phone ?? '',
            'npp' => $user->npp ?? '',
            'position_id' => $user->position_id ?? null,
        ]);
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
                TextInput::make('no_phone'),
                TextInput::make('npp'),
                Select::make('position_id')
                    ->label('Position')
                    ->options(Position::all()->pluck('name', 'id'))
                    ->preload(),
            ])
            ->statePath('data');
    }
    
    public function save(): void
    {
        $data = $this->form->getState();
        $user = Auth::user();
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->no_phone = $data['no_phone'] ?? null;
        $user->npp = $data['npp'] ?? null;
        $user->position_id = $data['position_id'] ?? null;
        $user->save();
        
        Notification::make()
            ->title('Profile updated successfully')
            ->success()
            ->send();
    }
}