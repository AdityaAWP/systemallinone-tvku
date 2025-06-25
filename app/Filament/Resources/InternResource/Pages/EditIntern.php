<?php

namespace App\Filament\Resources\InternResource\Pages;

use App\Filament\Resources\InternResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;

class EditIntern extends EditRecord
{
    protected static string $resource = InternResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->label('Batal'),
            Actions\Action::make('changePassword')
                ->label('Ganti Password')
                ->icon('heroicon-o-key')
                ->color('warning')
                ->modalHeading('Ganti Password Anak Magang')
                ->modalWidth('md')
                ->modalSubmitActionLabel('Update Password')
                ->form([
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
                ->action(function (array $data) {
                    $this->record->update([
                        'password' => Hash::make($data['new_password'])
                    ]);
                    Notification::make()
                        ->title('Password berhasil diubah')
                        ->success()
                        ->send();
                })
                ->modalSubmitAction(fn ($action) => $action->color('warning')),
        ];
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
