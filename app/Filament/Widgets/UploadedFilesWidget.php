<?php

namespace App\Filament\Widgets;

use App\Models\UploadedFile;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;

class UploadedFilesWidget extends BaseWidget
{
    protected static ?string $heading = 'File yang Diupload';
    
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                UploadedFile::query()
                    ->where(function($query) {
                        $query->where('file_type', 'daily_report_import')
                              ->orWhere('file_type', 'daily_report_document');
                    })
                    ->when(!$this->isHrd(Auth::user()), function($query) {
                        // Jika bukan HRD, hanya tampilkan file yang diupload user sendiri
                        if (!$this->isManager(Auth::user()) && !$this->isKepala(Auth::user())) {
                            $query->where('uploaded_by', Auth::id());
                        }
                    })
                    ->latest()
            )
            ->columns([
                TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->default(fn($record) => $record->title ?: $record->original_filename),
                
                TextColumn::make('file_type_display')
                    ->label('Jenis File')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Import Laporan Harian' => 'success',
                        'Dokumen Laporan Harian' => 'info',
                        default => 'gray',
                    }),
                
                TextColumn::make('original_filename')
                    ->label('Nama File')
                    ->searchable()
                    ->limit(30),
                
                TextColumn::make('file_size_formatted')
                    ->label('Ukuran')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('file_size', $direction);
                    }),
                
                TextColumn::make('uploader.name')
                    ->label('Diupload Oleh')
                    ->searchable()
                    ->visible(fn() => $this->isHrd(Auth::user()) || $this->isManager(Auth::user()) || $this->isKepala(Auth::user())),
                
                TextColumn::make('created_at')
                    ->label('Tanggal Upload')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                
                TextColumn::make('description')
                    ->label('Deskripsi')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),
            ])
            ->actions([
                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function (UploadedFile $record) {
                        $filePath = storage_path('app/public/' . $record->file_path);
                        
                        if (!file_exists($filePath)) {
                            \Filament\Notifications\Notification::make()
                                ->title('File Tidak Ditemukan')
                                ->body('File tidak dapat ditemukan di server.')
                                ->danger()
                                ->send();
                            return;
                        }

                        return response()->download($filePath, $record->original_filename);
                    }),
                
                Action::make('view_details')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading('Detail File')
                    ->modalContent(function (UploadedFile $record) {
                        return view('filament.widgets.file-details', compact('record'));
                    })
                    ->modalWidth('lg')
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->modalActions([
                        \Filament\Actions\Action::make('close')
                            ->label('Tutup')
                            ->color('gray')
                            ->close()
                    ]),
                
                Tables\Actions\DeleteAction::make()
                    ->visible(function (UploadedFile $record) {
                        return $record->uploaded_by === Auth::id() || 
                               $this->isHrd(Auth::user()) || 
                               $this->isManager(Auth::user()) || 
                               $this->isKepala(Auth::user());
                    })
                    ->action(function (UploadedFile $record) {
                        // Hapus file dari storage
                        if (Storage::disk('public')->exists($record->file_path)) {
                            Storage::disk('public')->delete($record->file_path);
                        }
                        
                        // Hapus record dari database
                        $record->delete();
                        
                        \Filament\Notifications\Notification::make()
                            ->title('File Dihapus')
                            ->body('File berhasil dihapus.')
                            ->success()
                            ->send();
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('file_type')
                    ->label('Jenis File')
                    ->options([
                        'daily_report_import' => 'Import Laporan Harian',
                        'daily_report_document' => 'Dokumen Laporan Harian',
                    ]),
                
                Tables\Filters\SelectFilter::make('uploaded_by')
                    ->label('Diupload Oleh')
                    ->options(function () {
                        return \App\Models\User::whereHas('uploadedFiles')
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->visible(fn() => $this->isHrd(Auth::user()) || $this->isManager(Auth::user()) || $this->isKepala(Auth::user())),
                
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('created_from')
                            ->label('Dari Tanggal'),
                        \Filament\Forms\Components\DatePicker::make('created_until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    private function isManager($user): bool
    {
        return $user->roles()->where('name', 'like', 'manager%')->exists();
    }
    
    private function isKepala($user): bool
    {
        return $user->roles()->where('name', 'like', 'kepala%')->exists();
    }
    
    private function isHrd($user): bool
    {
        return $user->roles()->where('name', 'hrd')->exists();
    }

    public static function canView(): bool
    {
        // Only show on specific pages, not on dashboard
        return request()->routeIs('filament.admin.resources.daily-reports.index');
    }

}