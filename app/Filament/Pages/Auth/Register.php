<?php

namespace App\Filament\Pages\Auth;

use App\Models\Intern;
use App\Models\User;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Facades\Filament;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Http\Responses\Auth\Contracts\RegistrationResponse;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class Register extends BaseRegister
{
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
                $this->getTokenFormComponent(),
                Select::make('user_type')
                    ->label('User Type')
                    ->options([
                        'staff' => 'Staff',
                        'intern' => 'Intern',
                    ])
                    ->required()
                    ->reactive(), // Enable conditional UI

                Select::make('role')
                    ->label('Role')
                    ->options(function () {
                        return Role::pluck('name', 'name')->toArray(); // Get roles from DB
                    })
                    ->required()
                    ->visible(fn ($get) => $get('user_type') === 'staff'),
            ]);
    }

    protected function getTokenFormComponent(): Component
    {
        return TextInput::make('token')
            ->label('Registration Token')
            ->placeholder('Enter your registration token')
            ->required()
            ->maxLength(255);
    }

    public function register(): ?RegistrationResponse
    {
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            Notification::make()
                ->title(__('filament-panels::pages/auth/register.notifications.throttled.title', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]))
                ->body(__('filament-panels::pages/auth/register.notifications.throttled.body', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]))
                ->danger()
                ->send();

            return null;
        }

        $data = $this->form->getState();
        $token = $data['token'];

        // Check token and determine user type
        if ($token === 'TVKU-STAFF') {
            // Create staff user
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            event(new Registered($user));

            Auth::login($user);

            // Redirect to staff features
            return $this->getRegistrationResponse();
        } elseif ($token === 'TVKU-INTERN') {
            // Create intern user
            $intern = Intern::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            event(new Registered($intern));

            // Use intern guard for authentication
            Auth::guard('intern')->login($intern);

            // Redirect to intern features
            return app(RegistrationResponse::class);
        } else {
            // Invalid token
            Notification::make()
                ->title('Invalid Token')
                ->body('The registration token you entered is not valid.')
                ->danger()
                ->send();

            return null;
        }
    }
}