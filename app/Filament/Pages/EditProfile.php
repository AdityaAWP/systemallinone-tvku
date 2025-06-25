<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ProfileWidget;
use Filament\Pages\Page;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Grid;
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
use Illuminate\Support\Facades\Hash;

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
            ->modalWidth('7xl')
            ->modalSubmitActionLabel('Save Changes')
            ->form([
                Grid::make(4)
                    ->schema([
                        FileUpload::make('avatar')
                            ->label('Foto Profil')
                            ->image()
                            ->directory('profile-photos')
                            ->avatar()
                            ->imageEditor()
                            ->columnSpan(1)
                            ->default($user->avatar),
                        TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(4)
                            ->default($user->name),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->columnSpan(2)
                            ->default($user->email),
                        TextInput::make('npp')
                            ->label('NPP')
                            ->required()
                            ->maxLength(20)
                            ->columnSpan(1)
                            ->default($user->npp),
                        DatePicker::make('birth')
                            ->label('Tanggal Lahir')
                            ->maxDate(now())
                            ->columnSpan(1)
                            ->default($user->birth),
                        Select::make('gender')
                            ->label('Jenis Kelamin')
                            ->options([
                                'Laki-laki' => 'Laki-laki',
                                'Perempuan' => 'Perempuan',
                            ])
                            ->columnSpan(1)
                            ->default($user->gender),
                        TextInput::make('no_phone')
                            ->label('No. Telepon')
                            ->tel()
                            ->maxLength(20)
                            ->columnSpan(1)
                            ->default($user->no_phone),
                        TextInput::make('ktp')
                            ->label('No. KTP')
                            ->maxLength(20)
                            ->columnSpan(1)
                            ->default($user->ktp),
                        Select::make('last_education')
                            ->label('Pendidikan Terakhir')
                            ->options([
                                'sd' => 'SD',
                                'smp' => 'SMP',
                                'sma' => 'SMA',
                                'diploma' => 'Diploma',
                                's1' => 'S1',
                                's2' => 'S2',
                                's3' => 'S3',
                            ])
                            ->columnSpan(1)
                            ->default($user->last_education),
                        Select::make('division_id')
                            ->label('Divisi')
                            ->options(Division::all()->pluck('name', 'id'))
                            ->searchable()
                            ->placeholder('Pilih Divisi')
                            ->columnSpan(2)
                            ->default($user->division_id),
                        TextInput::make('position')
                            ->label('Jabatan')
                            ->maxLength(100)
                            ->columnSpan(2)
                            ->default($user->position),
                        TextInput::make('address')
                            ->label('Alamat')
                            ->maxLength(500)
                            ->columnSpan(4)
                            ->default($user->address),
                    ]),
            ])
            ->action(function (array $data): void {
                $user = User::find(Auth::id());

                $data = array_map(fn($value) => $value === '' ? null : $value, $data);

                if (isset($data['avatar'])) {
                    $user->avatar = $data['avatar'];
                }

                $user->update($data);

                Notification::make()
                    ->title('Profile updated successfully')
                    ->success()
                    ->send();
            });
    }

    public function getChangePasswordAction(): Action
    {
        return Action::make('changePassword')
            ->label('Ganti Password')
            ->icon('heroicon-o-key')
            ->color('warning')
            ->modalHeading('Ganti Password')
            ->modalWidth('md')
            ->modalSubmitActionLabel('Update Password')
            ->form([
                TextInput::make('current_password')
                    ->label('Password Lama')
                    ->password()
                    ->required()
                    ->rule(function () {
                        return function (string $attribute, $value, \Closure $fail) {
                            if (!Hash::check($value, (string) Auth::user()->password)) {
                                $fail('Password lama salah.');
                            }
                        };
                    }),
                TextInput::make('new_password')
                    ->label('Password Baru')
                    ->password()
                    ->required()
                    ->minLength(8)
                    ->helperText('Password minimal 8 karakter dan mengandung huruf besar, kecil, dan angka.'),
                TextInput::make('new_password_confirmation')
                    ->label('Konfirmasi Password Baru')
                    ->password()
                    ->required()
                    ->same('new_password'),
            ])
            ->action(function (array $data): void {
                $user = User::find(Auth::id());
                $user->update([
                    'password' => Hash::make($data['new_password'])
                ]);
                Notification::make()
                    ->title('Password berhasil diubah')
                    ->success()
                    ->send();
            })
            ->modalSubmitAction(fn ($action) => $action->color('warning'));
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
            $this->getChangePasswordAction(),
        ];
    }
}
