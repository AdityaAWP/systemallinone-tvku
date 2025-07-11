<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Forms\Components\View;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use App\Models\Intern;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;

class LoginIntern extends BaseLogin
{
    // <--- CHANGE #1: Add the public property for the "Remember me" state.
    public bool $remember = false;

    public function getTitle(): string|Htmlable
    {
        return 'Login Magang Panel';
    }

    public function getHeading(): string
    {
        return ''; // Menghapus teks "Masuk ke akun Anda"
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                View::make('filament.notes.first-login-info'),
                $this->getNameFormComponent(),
                $this->getPasswordFormComponent(),
                // <--- CHANGE #2: Add the "Remember me" checkbox to the form.
                $this->getRememberFormComponent(),
            ])
            ->statePath('data')
            ->extraAttributes(['class' => 'my-16']);
    }

    protected function getNameFormComponent(): Component
    {
        return TextInput::make('name')
            ->label('Username')
            ->required()
            ->autocomplete('name')
            ->autofocus()
            ->extraAttributes(['tabindex' => 1]);
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label(__('filament-panels::pages/auth/login.form.password.label'))
            ->password()
            ->required()
            ->extraAttributes(['tabindex' => 2]);
    }

    // You don't need to override this since the base class already provides it.
    // Calling it in the form() schema is enough.
    // protected function getRememberFormComponent(): Component
    // {
    //     return Checkbox::make('remember')
    //         ->label(__('filament-panels::pages/auth/login.form.remember.label'));
    // }

    protected function getGuardName(): string
    {
        return 'intern';
    }

    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (ThrottleRequestsException $exception) {
            throw ValidationException::withMessages([
                'data.name' => __('filament-panels::pages/auth/login.messages.throttled', [
                    'seconds' => $exception->getHeaders()['Retry-After'] ?? 60,
                    'minutes' => ceil(($exception->getHeaders()['Retry-After'] ?? 60) / 60),
                ]),
            ]);
        }

        $data = $this->form->getState();
        $user = Intern::where('name', $data['name'])->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'data.name' => __('filament-panels::pages/auth/login.messages.failed'),
            ]);
        }

        if (empty($user->password)) {
            $user->update([
                'password' => Hash::make($data['password'])
            ]);
            $user->refresh();

            // The code is now valid because $this->remember exists.
            Auth::guard('intern')->login($user, $this->remember);
            session()->regenerate();

            Notification::make()
                ->title('Password Berhasil Dibuat')
                ->body('Password Anda telah disimpan. Harap ingat untuk login selanjutnya.')
                ->success()
                ->send();

            return app(LoginResponse::class);
        }

        if (!Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'data.name' => __('filament-panels::pages/auth/login.messages.failed'),
            ]);
        }
        
        // The code is now valid because $this->remember exists.
        Auth::guard('intern')->login($user, $this->remember);
        session()->regenerate();

        return app(LoginResponse::class);
    }
}