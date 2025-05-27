<?php

namespace App\Filament\Intern\Pages;

use App\Models\Intern;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class EditProfileIntern extends Page
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static string $view = 'filament.pages.edit-profile-intern';

    protected static ?string $navigationLabel = 'Edit My Profile';

    protected static ?string $title = 'Edit My Profile';

    protected static ?string $slug = 'edit-profile';

    public static function getPanel(): ?string
    {
        return 'intern';
    }

    public static function canViewAny(): bool
    {
        return true;
    }

    public ?array $data = [];

    public function mount(): void
    {
        // Load the authenticated intern's data into the form
        $intern = Auth::guard('intern')->user(); // Assuming 'intern' is your guard name
        if ($intern) {
            // Exclude password from being loaded into the form
            $formData = $intern->attributesToArray();
            unset($formData['password']);
            $this->form->fill($formData);
        } else {
            // Handle case where user is not authenticated or not an intern
            // This shouldn't happen if middleware is set up correctly
            abort(403, 'User not authenticated as an intern.');
        }
    }

    protected function getFormSchema(): array
    {
        return [
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            TextInput::make('email')
                ->email()
                ->required()
                ->maxLength(255)
                // Ensure email is unique, ignoring the current user's record
                ->unique(Intern::class, 'email', ignoreRecord: true),
            DatePicker::make('birth_date')
                ->required(),
            // Assuming school_id is not editable by the intern
            // TextInput::make('school.name') // Display school name
            //     ->disabled()
            //     ->label('School/University'),
            TextInput::make('division')
                ->required()
                ->maxLength(255),
            TextInput::make('nis_nim')
                ->required()
                ->maxLength(255)
                ->label('NIS/NIM'),
            TextInput::make('no_phone')
                ->tel()
                ->required()
                ->maxLength(20)
                ->label('Phone Number'),
            TextInput::make('institution_supervisor')
                ->required()
                ->maxLength(255)
                ->label('Institution Supervisor'),
            TextInput::make('college_supervisor')
                ->required()
                ->maxLength(255)
                ->label('College Supervisor'),
            TextInput::make('college_supervisor_phone')
                ->tel()
                ->required()
                ->maxLength(20)
                ->label('College Supervisor Phone'),
            // Start/End dates might also be non-editable by the intern
            // DatePicker::make('start_date')
            //     ->disabled(),
            // DatePicker::make('end_date')
            //     ->disabled(),

            // Password Update Section (Optional - consider a separate page)
            TextInput::make('new_password')
                ->password()
                ->maxLength(255)
                ->label('New Password (leave blank to keep current)')
                ->nullable()
                ->confirmed(), // Adds confirmation field
            TextInput::make('new_password_confirmation')
                ->password()
                ->label('Confirm New Password')
                ->requiredWith('new_password') // Only required if new_password is filled
                ->dehydrated(false), // Don't save this confirmation field
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema($this->getFormSchema())
            ->statePath('data')
            ->model(Auth::guard('intern')->user()); // Load data for the current intern
    }

    public function save(): void
    {
        $this->validate();

        $intern = Auth::guard('intern')->user();
        $formData = $this->form->getState();

        // Handle password update
        if (!empty($formData['new_password'])) {
            $formData['password'] = Hash::make($formData['new_password']);
        }
        // Remove password fields from the main update array if not changing
        unset($formData['new_password']);
        unset($formData['new_password_confirmation']);

        // Filter out fields that shouldn't be updated directly if needed
        // e.g., unset($formData['school_id']);

        $intern->update($formData);

        // Refresh form data to reflect changes (optional, depends on desired UX)
        // $this->mount();

        Notification::make()
            ->title('Profile updated successfully')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Changes')
                ->submit('save'),
        ];
    }
}

