<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\ViewField;

class ProfileWidget extends Widget
{
    protected static string $view = 'filament.widgets.profile-widget';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 0;
    
    public ?User $user = null;
    
    public function mount(): void
    {
        $this->user = Auth::user();
    }
    
    protected function getViewData(): array
    {
        return [
            'user' => $this->user,
            'additionalData' => $this->getAdditionalUserData(),
        ];
    }
    
    protected function getAdditionalUserData(): array
    {
        // Fetch additional data from the users table
        return [
            'email' => $this->user->email,
            'created_at' => $this->user->created_at,
            // Add more fields as needed
        ];
    }
    
    public static function canView(): bool
    {
        return Auth::check();
    }

    public static function getSort(): int
    {
        return 1; // Ensures this widget is at the top
    }
}