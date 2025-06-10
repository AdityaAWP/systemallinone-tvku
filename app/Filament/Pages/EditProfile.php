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
use App\Models\Division;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Infolist;
use Filament\Infolists\Concerns\InteractsWithInfolists;

class EditProfile extends Page
{
    use InteractsWithForms, InteractsWithActions, InteractsWithInfolists;

    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static ?string $navigationGroup = 'Manajemen Karyawan';
    protected static ?int $navigationSort = 5;
    protected static string $view = 'filament.pages.edit-profile';
    protected static ?string $title = 'Profile';
    
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

                TextInput::make('npp')
                    ->label('NPP')
                    ->required()
                    ->maxLength(20)
                    ->default($user->npp),

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

                TextInput::make('no_phone')
                    ->label('No. Telepon')
                    ->tel()
                    ->maxLength(20)
                    ->default($user->no_phone),

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

                Select::make('division_id')
                    ->label('Division')
                    ->options(Division::all()->pluck('name', 'id'))
                    ->searchable()
                    ->placeholder('Select Division')
                    ->default($user->division_id),

                TextInput::make('position')
                    ->label('Jabatan')
                    ->maxLength(100)
                    ->default($user->position),
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

    public function profileInfolist($infolist = null)
    {
        $user = Auth::user();
        if ($infolist === null) {
            $infolist = \Filament\Infolists\Infolist::make();
        }
        return $infolist
            ->record($user)
            ->schema([
                Section::make('Informasi Pribadi')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nama Lengkap')
                            ->icon('heroicon-o-user'),
                        TextEntry::make('npp')
                            ->label('NPP')
                            ->icon('heroicon-o-identification'),
                        TextEntry::make('email')
                            ->label('Email')
                            ->icon('heroicon-o-envelope'),
                        TextEntry::make('birth')
                            ->label('Tanggal Lahir')
                            ->date()
                            ->icon('heroicon-o-calendar-days'),
                        TextEntry::make('gender')
                            ->label('Jenis Kelamin')
                            ->icon('heroicon-o-user-group'),
                        TextEntry::make('no_phone')
                            ->label('No. Telepon')
                            ->icon('heroicon-o-phone'),
                        TextEntry::make('ktp')
                            ->label('No. KTP')
                            ->icon('heroicon-o-identification'),
                    ])->columns(2),
                Section::make('Informasi Pekerjaan')
                    ->schema([
                        TextEntry::make('last_education')
                            ->label('Pendidikan Terakhir')
                            ->icon('heroicon-o-academic-cap'),
                        TextEntry::make('division.name')
                            ->label('Divisi')
                            ->icon('heroicon-o-building-office'),
                        TextEntry::make('address')
                            ->label('Alamat')
                            ->icon('heroicon-o-map-pin'),
                        TextEntry::make('position')
                            ->label('Jabatan')
                            ->icon('heroicon-o-briefcase'),
                    ])->columns(2),
            ]);
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $this->profileInfolist($infolist);
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getEditAction(),
        ];
    }
}