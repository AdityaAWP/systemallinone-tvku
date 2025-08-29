<?php
namespace App\Filament\Pages\Auth;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Forms\Components\View;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

class Login extends BaseLogin
{
    public function getTitle(): string|Htmlable
    {
        return 'Login Admin Panel';
    }

    public function getHeading(): string
    {
        return ''; 
    }

    public function form(Form $form): Form
    {
        return parent::form($form)
            ->schema([
                View::make('filament.pages.auth.flash-message'),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
                $this->getResetPasswordComponent(),
            ]);
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('Email')
            ->email()
            ->required()
            ->autocomplete()
            ->autofocus()
            ->extraAttributes(['tabindex' => 1]);
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('Password')
            ->password()
            ->required()
            ->revealable()
            ->autocomplete('current-password')
            ->extraAttributes(['tabindex' => 2]);
    }

    protected function getResetPasswordComponent(): Component
    {
        return View::make('filament.pages.auth.reset-password-link');
    }

}