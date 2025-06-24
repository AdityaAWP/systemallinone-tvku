<?php

namespace App\Filament\InternPages;

use Filament\Pages\Page;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Intern;
use App\Models\InternSchool;
use App\Models\InternDivision;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Infolist;
use Filament\Infolists\Concerns\InteractsWithInfolists;

class EditProfileIntern extends Page
{
    use InteractsWithForms, InteractsWithActions, InteractsWithInfolists;

    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static ?string $navigationGroup = 'Profile Management';
    protected static ?int $navigationSort = 5;
    protected static string $view = 'filament.intern-pages.edit-profile-intern';
    protected static ?string $title = 'Profile';
    
    public function profileInfolist(Infolist $infolist): Infolist
    {
        $intern = Auth::user();
        
        return $infolist
            ->record($intern)
            ->schema([
                Section::make('Personal Information')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nama Lengkap')
                            ->icon('heroicon-o-user'),
                        
                        TextEntry::make('email')
                            ->label('Email')
                            ->icon('heroicon-o-envelope'),
                        
                        TextEntry::make('birth_date')
                            ->label('Tanggal Lahir')
                            ->date()
                            ->icon('heroicon-o-calendar-days'),
                        
                        TextEntry::make('no_phone')
                            ->label('No Telepon')
                            ->icon('heroicon-o-phone')
                            ->placeholder('Not provided'),
                        
                        TextEntry::make('nis_nim')
                            ->label('NIS/NIM')
                            ->icon('heroicon-o-identification')
                            ->placeholder('Not provided'),
                    ])
                    ->columns(2),
                
                Section::make('Academic Information')
                    ->schema([
                        TextEntry::make('institution_type')
                            ->label('Jenjang Pendidikan')
                            ->icon('heroicon-o-academic-cap')
                            ->placeholder('Not provided'),
                        
                        TextEntry::make('school.name')
                            ->label(function ($record) {
                                if (!$record || !$record->institution_type) {
                                    return 'Universitas/Sekolah';
                                }
                                return $record->institution_type === 'Perguruan Tinggi' ? 'Perguruan Tinggi' : 'Asal Sekolah';
                            })
                            ->icon('heroicon-o-academic-cap')
                            ->placeholder('Not provided'),
                        
                        TextEntry::make('internDivision.name')
                            ->label('Divisi Magang')
                            ->icon('heroicon-o-building-office')
                            ->placeholder('Not provided'),
                        
                        TextEntry::make('institution_supervisor')
                            ->label('Pembimbing TVKU')
                            ->icon('heroicon-o-user-circle')
                            ->placeholder('Not provided'),
                        
                        TextEntry::make('college_supervisor')
                            ->label(function ($record) {
                                if (!$record || !$record->institution_type) {
                                    return 'Pembimbing Asal';
                                }
                                return $record->institution_type === 'Perguruan Tinggi' ? 'Dosen Pembimbing' : 'Guru Pembimbing';
                            })
                            ->icon('heroicon-o-user-circle')
                            ->placeholder('Not provided'),
                        
                        TextEntry::make('college_supervisor_phone')
                            ->label('Telepon Pembimbing')
                            ->icon('heroicon-o-phone')
                            ->placeholder('Not provided'),
                    ])
                    ->columns(2),
                
                Section::make('Internship Period')
                    ->schema([
                        TextEntry::make('start_date')
                            ->label('Mulai Magang')
                            ->date()
                            ->icon('heroicon-o-calendar-days')
                            ->placeholder('Not set'),
                        
                        TextEntry::make('end_date')
                            ->label('Selesai Magang')
                            ->date()
                            ->icon('heroicon-o-calendar-days')
                            ->placeholder('Not set'),
                    ])
                    ->columns(2),
            ]);
    }
    
    public function getEditAction(): Action
    {
        $intern = Auth::user();

        return Action::make('edit')
            ->label('Edit Profile')
            ->icon('heroicon-o-pencil-square')
            ->modalHeading('Edit Profile Information')
            ->modalWidth('xl')
            ->modalSubmitActionLabel('Save Changes')
            ->form([
                TextInput::make('name')
                    ->required()
                    ->label('Nama')
                    ->maxLength(255)
                    ->default($intern->name),

                TextInput::make('email')
                    ->email()   
                    ->label('Email')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->default($intern->email),

                DatePicker::make('birth_date')
                    ->label('Tanggal Lahir')
                    ->required()
                    ->maxDate(now())
                    ->default($intern->birth_date),

                TextInput::make('nis_nim')
                    ->label('NIS/NIM')
                    ->maxLength(50)
                    ->default($intern->nis_nim),

                Select::make('institution_type')
                    ->label('Jenjang Pendidikan')
                    ->options([
                        'Perguruan Tinggi' => 'Perguruan Tinggi',
                        'SMA/SMK' => 'SMA/SMK',
                    ])
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn(Set $set) => $set('school_id', null))
                    ->default($intern->institution_type),

                Select::make('school_id')
                    ->label(function (Get $get) {
                        $type = $get('institution_type');
                        return $type === 'Perguruan Tinggi' ? 'Perguruan Tinggi' : 'Asal Sekolah';
                    })
                    ->options(function (Get $get) {
                        $type = $get('institution_type');
                        if (!$type) return [];

                        return InternSchool::where('type', $type)
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->default($intern->school_id),

                Select::make('intern_division_id')
                    ->label('Divisi Magang')
                    ->options(function () {
                        return InternDivision::all()->pluck('name', 'id');
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->default($intern->intern_division_id),

                TextInput::make('no_phone')
                    ->label('Telepon Magang')
                    ->tel()
                    ->maxLength(20)
                    ->default($intern->no_phone),

                TextInput::make('college_supervisor')
                    ->label(function (Get $get) {
                        $type = $get('institution_type');
                        return $type === 'Perguruan Tinggi' ? 'Dosen Pembimbing' : 'Guru Pembimbing';
                    })
                    ->maxLength(255)
                    ->default($intern->college_supervisor),

                TextInput::make('institution_supervisor')
                    ->label('Pembimbing TVKU')
                    ->maxLength(255)
                    ->default($intern->institution_supervisor),

                TextInput::make('college_supervisor_phone')
                    ->label('Telepon Pembimbing')
                    ->tel()
                    ->maxLength(20)
                    ->default($intern->college_supervisor_phone),

                DatePicker::make('start_date')
                    ->label('Mulai Magang')
                    ->required()
                    ->default($intern->start_date),

                DatePicker::make('end_date')
                    ->label('Selesai Magang')
                    ->required()
                    ->after('start_date')
                    ->default($intern->end_date),
            ])
            ->action(function (array $data): void {
                $intern = Intern::find(Auth::id());

                // Handle empty values
                $data = array_map(function ($value) {
                    return $value === '' ? null : $value;
                }, $data);

                $intern->update($data);

                Notification::make()
                    ->title('Profile updated successfully')
                    ->success()
                    ->send();
            });
    }

    public function getChangePasswordAction(): Action
    {
        return Action::make('changePassword')
            ->label('Change Password')
            ->icon('heroicon-o-key')
            ->color('warning')
            ->modalHeading('Change Password')
            ->modalWidth('md')
            ->modalSubmitActionLabel('Update Password')
            ->form([
                TextInput::make('current_password')
                    ->label('Current Password')
                    ->password()
                    ->required()
                    ->rule(function () {
                        return function (string $attribute, $value, \Closure $fail) {
                            if (!Hash::check($value, Auth::user()->password)) {
                                $fail('The current password is incorrect.');
                            }
                        };
                    }),

                TextInput::make('new_password')
                    ->label('New Password')
                    ->password()
                    ->required()
                    ->minLength(8)
                    ->helperText('Password must be at least 8 characters and contain at least one uppercase letter, one lowercase letter, and one number.')
                    ->live()
                    ->dehydrated(fn ($state) => filled($state)),

                TextInput::make('new_password_confirmation')
                    ->label('Confirm New Password')
                    ->password()
                    ->required()
                    ->same('new_password')
                    ->dehydrated(false),
            ])
            ->action(function (array $data): void {
                $intern = Intern::find(Auth::id());
                
                $intern->update([
                    'password' => Hash::make($data['new_password'])
                ]);

                Notification::make()
                    ->title('Password updated successfully')
                    ->body('Your password has been changed successfully.')
                    ->success()
                    ->duration(5000)
                    ->send();
            })
            ->modalSubmitAction(fn ($action) => $action->color('warning'));
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getEditAction(),
            $this->getChangePasswordAction(),
        ];
    }
}