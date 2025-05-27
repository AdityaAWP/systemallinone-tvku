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
use App\Models\Intern; // Use Intern model instead of User
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Filament\Notifications\Notification;

class LoginIntern extends BaseLogin
{
    public function getTitle(): string|Htmlable
    {
        return 'Login Magang Panel';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                View::make('filament.pages.auth.flash-message'),
                $this->getNameFormComponent(),
                $this->getPasswordFormComponent(),
            ]);
    }

    protected function getNameFormComponent(): Component
    {
        return TextInput::make('name')
            ->label('Name')
            ->required()
            ->autocomplete('name')
            ->autofocus()
            ->extraAttributes(['tabindex' => 1]);
    }

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
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]),
            ]);
        }

        $data = $this->form->getState();

        // Find user by name
        $user = Intern::where('name', $data['name'])->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'data.name' => 'User not found with this name.',
            ]);
        }

        // Check if user has no password (first time login)
        if (empty($user->password)) {
            // For first time login, use the provided password to set as their password
            $user->update([
                'password' => Hash::make($data['password'])
            ]);

            // Refresh the user model to get the updated password
            $user->refresh();

            // Login the user immediately after setting the password
            Auth::guard('intern')->login($user);
            session()->regenerate();

            // Show notification that password was set
            Notification::make()
                ->title('Password Set Successfully')
                ->body('Your password has been set. Please remember it for future logins.')
                ->success()
                ->send();

            return app(LoginResponse::class);
        }

        // For users with existing passwords, verify the password manually
        if (!Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'data.password' => 'The provided password is incorrect.',
            ]);
        }

        // Login the user
        Auth::guard('intern')->login($user);
        session()->regenerate();

        return app(LoginResponse::class);
    }
}