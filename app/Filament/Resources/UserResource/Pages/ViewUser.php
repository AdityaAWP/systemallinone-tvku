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
                Section::make('Informasi User')
                    ->schema([
                        TextEntry::make('name')->label('Nama'),
                        TextEntry::make('email')->label('Email'),
                        TextEntry::make('roles.name')->label('Role'),
                        TextEntry::make('division.name')->label('Divisi'),
                        TextEntry::make('position')->label('Jabatan'),
                        TextEntry::make('created_at')->label('Dibuat Pada')
                            ->dateTime('d M Y H:i'),
                    ])->columns(2),
            ]);
    }
}
