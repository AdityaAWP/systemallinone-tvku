<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IncomingLetterResource\Pages;
use App\Models\IncomingLetter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class IncomingLetterResource extends Resource
{
    protected static ?string $model = IncomingLetter::class;
    protected static ?string $navigationIcon = 'heroicon-o-inbox';
    protected static ?string $label = 'Surat Masuk';
    protected static ?string $navigationGroup = 'Administrasi';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Surat')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->options([
                                'internal' => 'Internal',
                                'general' => 'Umum',
                            ])
                            ->required()
                            ->label('Jenis Surat')
                            ->helperText('Internal untuk surat perusahaan, Umum untuk surat eksternal'),
                        Forms\Components\TextInput::make('reference_number')
                            ->disabled()
                            ->dehydrated(false)
                            ->label('Nomor Referensi')
                            ->helperText('Akan otomatis dihasilkan setelah disimpan'),
                        Forms\Components\TextInput::make('sender')
                            ->required()
                            ->maxLength(255)
                            ->label('Pengirim'),
                        Forms\Components\TextInput::make('subject')
                            ->required()
                            ->maxLength(255)
                            ->label('Subjek'),
                        Forms\Components\DatePicker::make('letter_date')
                            ->required()
                            ->label('Tanggal Surat'),
                        Forms\Components\DatePicker::make('received_date')
                            ->required()
                            ->default(now())
                            ->label('Tanggal Diterima'),
                        Forms\Components\RichEditor::make('content')
                            ->columnSpanFull()
                            ->label('Isi Surat'),
                        Forms\Components\Textarea::make('notes')
                            ->maxLength(65535)
                            ->columnSpanFull()
                            ->label('Catatan'),
                    ]),

                Forms\Components\Section::make('Lampiran')
                    ->schema([
                        Forms\Components\FileUpload::make('attachments')
                            ->multiple()
                            ->maxSize(5120) // Batas 5MB
                            ->directory('letters/incoming')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                            ->saveUploadedFileUsing(function (TemporaryUploadedFile $file) {
                                // Buat nama file unik
                                $filename = Str::random(40) . '.' . $file->getClientOriginalExtension();

                                // Simpan file di direktori yang ditentukan
                                $file->storeAs('letters/incoming', $filename, 'public');

                                // Kembalikan hanya nama file sebagai string
                                return $filename;
                            })
                            ->columnSpanFull()
                            ->label('Lampiran (Maks 5MB per file)'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference_number')
                    ->searchable()
                    ->sortable()
                    ->label('Nomor Referensi'),
                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'primary' => 'internal',
                        'success' => 'general',
                    ])
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'internal' => 'Internal',
                            'general' => 'Umum',
                        };
                    })
                    ->label('Jenis Surat'),
                Tables\Columns\TextColumn::make('sender')
                    ->searchable()
                    ->label('Pengirim'),
                Tables\Columns\TextColumn::make('subject')
                    ->searchable()
                    ->limit(30)
                    ->label('Subjek'),
                Tables\Columns\TextColumn::make('letter_date')
                    ->date()
                    ->sortable()
                    ->label('Tanggal Surat'),
                Tables\Columns\TextColumn::make('received_date')
                    ->date()
                    ->sortable()
                    ->label('Tanggal Diterima'),
                Tables\Columns\TextColumn::make('created_at')
                    ->date()
                    ->sortable()
                    ->hidden()
                    ->label('Dibuat Pada'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->date()
                    ->sortable()
                    ->hidden()
                    ->label('Diperbarui Pada'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'internal' => 'Internal',
                        'general' => 'Umum',
                    ])
                    ->label('Jenis Surat'),
                Tables\Filters\Filter::make('letter_date')
                    ->form([
                        Forms\Components\DatePicker::make('letter_date_from')
                            ->label('Tanggal Surat Dari'),
                        Forms\Components\DatePicker::make('letter_date_until')
                            ->label('Tanggal Surat Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['letter_date_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('letter_date', '>=', $date),
                            )
                            ->when(
                                $data['letter_date_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('letter_date', '<=', $date),
                            );
                    })
                    ->label('Tanggal Surat'),
                Tables\Filters\Filter::make('received_date')
                    ->form([
                        Forms\Components\DatePicker::make('received_date_from')
                            ->label('Tanggal Diterima Dari'),
                        Forms\Components\DatePicker::make('received_date_until')
                            ->label('Tanggal Diterima Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['received_date_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('received_date', '>=', $date),
                            )
                            ->when(
                                $data['received_date_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('received_date', '<=', $date),
                            );
                    })
                    ->label('Tanggal Diterima'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIncomingLetters::route('/'),
            'create' => Pages\CreateIncomingLetter::route('/create'),
            'edit' => Pages\EditIncomingLetter::route('/{record}/edit'),
        ];
    }
}
