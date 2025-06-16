<?php

namespace App\Filament\InternPages;

use Filament\Pages\Page;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use App\Models\Intern;
use App\Models\InternSchool;
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
    protected static ?string $title = 'Edit Profile';
    
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
                        TextEntry::make('school.name')
                            ->label('Asal Sekolah')
                            ->icon('heroicon-o-academic-cap')
                            ->placeholder('Not provided'),
                        
                        TextEntry::make('division')
                            ->label('Divisi')
                            ->icon('heroicon-o-building-office')
                            ->placeholder('Not provided'),
                        
                        TextEntry::make('institution_supervisor')
                            ->label('Nama Pembimbing')
                            ->icon('heroicon-o-user-circle')
                            ->placeholder('Not provided'),
                        
                        TextEntry::make('college_supervisor')
                            ->label('Dosen Pembimbing/Guru Pembimbing')
                            ->icon('heroicon-o-user-circle')
                            ->placeholder('Not provided'),
                        
                        TextEntry::make('college_supervisor_phone')
                            ->label('No Telepon Dosen Pembimbing/Guru Pembimbing')
                            ->icon('heroicon-o-phone')
                            ->placeholder('Not provided'),
                    ])
                    ->columns(2),
                
                Section::make('Internship Period')
                    ->schema([
                        TextEntry::make('start_date')
                            ->label('Start Date')
                            ->date()
                            ->icon('heroicon-o-calendar-days')
                            ->placeholder('Not set'),
                        
                        TextEntry::make('end_date')
                            ->label('End Date')
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
                    ->label('Nama Lengkap')
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
                    ->maxDate(now())
                    ->default($intern->birth_date),

                Select::make('school_id')
                    ->label('Asal Sekolah')
                    ->options(InternSchool::all()->pluck('name', 'id'))
                    ->searchable()
                    ->placeholder('Select School/Institution')
                    ->default($intern->school_id),

                TextInput::make('division')
                    ->label('Divisi')
                    ->maxLength(255)
                    ->default($intern->division),

                TextInput::make('nis_nim')
                    ->label('NIS/NIM')
                    ->maxLength(255)
                    ->default($intern->nis_nim),

                TextInput::make('no_phone')
                    ->label('No Telepon')
                    ->tel()
                    ->maxLength(20)
                    ->default($intern->no_phone),

                TextInput::make('institution_supervisor')
                    ->label('Nama Pembimbing')
                    ->maxLength(255)
                    ->default($intern->institution_supervisor),

                TextInput::make('college_supervisor')
                    ->label('Dosen Pembimbing/Guru Pembimbing')
                    ->maxLength(255)
                    ->default($intern->college_supervisor),

                TextInput::make('college_supervisor_phone')
                    ->label('No Telepon Dosen Pembimbing/Guru Pembimbing')
                    ->tel()
                    ->maxLength(20)
                    ->default($intern->college_supervisor_phone),

                DatePicker::make('start_date')
                    ->label('Mulai Magang')
                    ->default($intern->start_date),

                DatePicker::make('end_date')
                    ->label('Selesai Magang')
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

    protected function getHeaderActions(): array
    {
        return [
            $this->getEditAction(),
        ];
    }
}