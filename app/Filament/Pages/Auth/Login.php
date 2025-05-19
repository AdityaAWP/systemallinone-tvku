<?php

namespace App\Filament\Pages\Auth;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Facades\Filament;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\View;
use Filament\Forms\Form;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    use WithRateLimiting;

    public function getTitle(): string|Htmlable
    {
        return 'Login Admin Panel';
    }

    public function form(Form $form): Form
    {
        return parent::form($form)
            ->schema([
                // Tambahkan flash message view dan tombol login Google
                View::make('filament.pages.auth.flash-message'),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
                View::make('filament.pages.auth.google-button'),
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

    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            Notification::make()
                ->title(__('filament-panels::pages/auth/login.notifications.throttled.title', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]))
                ->body(__('filament-panels::pages/auth/login.notifications.throttled.body', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]))
                ->danger()
                ->send();

            return null;
        }

        $data = $this->form->getState();

        // Autentikasi sebagai staff (default guard)
        if (Auth::attempt([
            'email' => $data['email'],
            'password' => $data['password'],
        ], $data['remember'] ?? false)) {
            if (Filament::getCurrentPanel()->getId() === 'admin') {
                return app(LoginResponse::class);
            }

            Auth::logout();

            $this->throwValidationException(
                'email',
                __('filament-panels::pages/auth/login.messages.failed'),
            );
        }

        // Autentikasi sebagai intern
        if (Auth::guard('intern')->attempt([
            'email' => $data['email'],
            'password' => $data['password'],
        ], $data['remember'] ?? false)) {
            if (Filament::getCurrentPanel()->getId() === 'intern') {
                return app(LoginResponse::class);
            }

            Auth::guard('intern')->logout();

            $this->throwValidationException(
                'email',
                __('filament-panels::pages/auth/login.messages.failed'),
            );
        }

        $this->throwValidationException(
            'email',
            __('filament-panels::pages/auth/login.messages.failed'),
        );
    }

    protected function throwValidationException(string $field, string $message): never
    {
        throw ValidationException::withMessages([
            $field => $message,
        ]);
    }
}
