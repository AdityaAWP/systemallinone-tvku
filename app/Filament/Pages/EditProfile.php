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
    protected static ?string $navigationGroup = 'General';
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
            'no_phone' => $user->no_phone ?? '',
            'npp' => $user->npp ?? '',
            'position_id' => $user->position_id ?? null,
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
        $user = User::find(Auth::id());
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