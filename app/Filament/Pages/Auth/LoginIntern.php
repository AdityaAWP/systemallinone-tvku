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
    public bool $remember = false;

    public function getTitle(): string|Htmlable
    {
        return 'Login Magang Panel';
    }

    public function getHeading(): string
    {
        return '';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getNameFormComponent(),
                $this->getPasswordFormComponent(),
                View::make('filament.notes.first-login-info'),
                $this->getRememberFormComponent(),
            ])
            ->statePath('data')
            ->extraAttributes(['class' => 'my-10']);
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
            ->revealable()
            ->required()
            ->extraAttributes(['tabindex' => 2]);
    }

    protected function getGuardName(): string
    {
        return 'intern';
    }

    public function authenticate(): ?LoginResponse
    {
        
        // <--- START: IP Address Check --->
        $allowedIpsString = env('OFFICE_IP_ADDRESSES');

        // Only perform the check if the environment variable is set.
        if (!empty($allowedIpsString)) {
            // Get the user's IP address
            $userIp = request()->ip();

            // Convert the comma-separated string from .env into an array
            $allowedIps = array_map('trim', explode(',', $allowedIpsString));

            // Check if the user's IP is in the allowed list
            if (!in_array($userIp, $allowedIps)) {
                // If not, throw a validation exception to block login and show a message.
                throw ValidationException::withMessages([
                    'data.name' => 'Akses ditolak. Anda harus terhubung ke jaringan kantor untuk login.',
                ]);
            }
        }
        // <--- END: IP Address Check --->

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
        
        Auth::guard('intern')->login($user, $this->remember);
        session()->regenerate();

        return app(LoginResponse::class);
    }
}