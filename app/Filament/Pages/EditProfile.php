<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ProfileWidget;
use Filament\Pages\Page;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;

class EditProfile extends Page
{
    use InteractsWithForms, InteractsWithActions;

    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static ?string $navigationGroup = 'Manajemen Karyawan';
    protected static ?int $navigationSort = 5;
    protected static string $view = 'filament.pages.edit-profile';
    protected static ?string $title = 'Edit Profile';
    public function getEditAction(): Action
    {
        $user = Auth::user();

        return Action::make('edit')
            ->label('Edit Profile')
            ->icon('heroicon-o-pencil-square')
            ->modalHeading('Edit Profile Information')
            ->modalWidth('xl')
            ->modalSubmitActionLabel('Save Changes')
            ->form([
                // FileUpload::make('avatar')
                //     ->label('Profile Picture')
                //     ->image()
                //     ->directory('profile-photos')
                //     ->avatar()
                //     ->imageEditor()
                //     ->default($user->avatar),

                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->default($user->name),

                TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->default($user->email),

                DatePicker::make('birth')
                    ->label('Date of Birth')
                    ->maxDate(now())
                    ->default($user->birth),

                Select::make('gender')
                    ->options([
                        'Laki-laki' => 'Laki-laki',
                        'Perempuan' => 'Perempuan',
                    ])
                    ->default($user->gender),
                TextInput::make('phone')
                    ->tel()
                    ->maxLength(20)
                    ->default($user->phone),

                TextInput::make('ktp')
                    ->label('KTP Number')
                    ->maxLength(20)
                    ->default($user->ktp),

                Select::make('last_education')
                    ->label('Last Education')
                    ->options([
                        'sd' => 'SD',
                        'smp' => 'SMP',
                        'sma' => 'SMA',
                        'diploma' => 'Diploma',
                        's1' => 'S1',
                        's2' => 'S2',
                        's3' => 'S3',
                    ])
                    ->default($user->last_education),

                TextInput::make('address')
                    ->maxLength(500)
                    ->columnSpanFull()
                    ->default($user->address),
            ])
            ->action(function (array $data): void {
                $user = User::find(Auth::id());

                // Handle empty values
                $data = array_map(function ($value) {
                    return $value === '' ? null : $value;
                }, $data);

                $user->update($data);

                Notification::make()
                    ->title('Profile updated successfully')
                    ->success()
                    ->send();
            });
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getEditAction(),
        ];
    }
}
