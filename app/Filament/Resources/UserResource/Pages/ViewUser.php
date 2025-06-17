<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function infolist(Infolists\Infolist $infolist): Infolists\Infolist
    {
        return $infolist
            ->record($this->getRecord())
            ->schema([
                Section::make('Informasi Pribadi')
                    ->schema([
                        TextEntry::make('avatar')
                            ->label('Avatar')
                            ->formatStateUsing(function ($state, $record) {
                                $avatarPath = $record->avatar && file_exists(public_path('storage/' . $record->avatar))
                                    ? asset('storage/' . $record->avatar)
                                    : asset('images/profile.png');
                                return '<img src="' . $avatarPath . '" alt="' . e($record->name) . '" class="h-16 w-16 rounded-full object-cover">';
                            })
                            ->html(),
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
}
