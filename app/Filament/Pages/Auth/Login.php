<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;

class Login extends BaseLogin
{
    public function getTitle(): string|Htmlable
    {
        return 'Login Admin Panel';
    }

    public function form(Form $form): Form
    {
        return parent::form($form)
            ->schema([
                // Tambahkan komponen view untuk flash message
                \Filament\Forms\Components\View::make('filament.pages.auth.flash-message'),
                
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
                
                \Filament\Forms\Components\View::make('filament.pages.auth.google-button'),
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
}