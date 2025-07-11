<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OutgoingLetterResource\Pages;
use App\Models\OutgoingLetter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\ImageColumn;

class OutgoingLetterResource extends Resource
{
    protected static ?string $model = OutgoingLetter::class;
    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';
    protected static ?string $label = 'Surat Keluar';
    protected static ?string $navigationGroup = 'Administrasi Surat';
    protected static ?int $navigationSort = 2;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Surat')
                    ->schema([
                        Forms\Components\Grid::make()
                            ->schema([

                                Forms\Components\Select::make('type')
                                    ->options([
                                        'internal' => 'Internal',
                                        'general' => 'Umum',
                                    ])
                                    ->required()
                                    ->label('Jenis Surat')
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get, $context) {
                                        if ($state && $context === 'create') {
                                            $nextNumber = OutgoingLetter::generateNextReferenceNumber($state);
                                            $set('reference_number', $nextNumber);
                                        } elseif ($state && $context === 'edit') {
                                            // Pada mode edit, hanya update jika jenis surat berubah
                                            $currentRefNumber = $get('reference_number');
                                            $currentPrefix = match ($state) {
                                                'internal' => 'I-',
                                                'general' => 'U-',
                                                default => ''
                                            };
                                            
                                            // Jika prefix tidak cocok dengan yang sekarang, generate ulang
                                            if (!str_starts_with($currentRefNumber, $currentPrefix)) {
                                                $nextNumber = OutgoingLetter::generateNextReferenceNumber($state);
                                                $set('reference_number', $nextNumber);
                                            }
                                        }
                                    }),

                                Forms\Components\TextInput::make('reference_number')
                                    ->label('Nomor Surat')
                                    ->required()
                                    ->dehydrated()
                                    ->helperText('Nomor Surat akan dibuat otomatis berdasarkan jenis surat'),
                                Forms\Components\DatePicker::make('letter_date')
                                    ->required()
                                    ->default(now())
                                    ->label('Tanggal Surat'),
                            ])
                            ->columns(3),
                        Forms\Components\Grid::make()
                            ->schema([

                                TextArea::make('recipient')
                                    ->required()
                                    ->maxLength(255)
                                    ->rows(2)
                                    ->label('Penerima'),
                                TextArea::make('subject')
                                    ->required()
                                    ->maxLength(255)
                                    ->rows(2)
                                    ->label('Perihal'),
                            ])
                            ->columns(2),
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
                            ->directory('letters/outgoing')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                            ->disk('public')
                            ->visibility('public')
                            ->columnSpanFull()
                            ->label('Lampiran (Maksimal 5MB per file)')
                            ->getUploadedFileNameForStorageUsing(function ($file, $livewire) {
                                $reference = $livewire->data['reference_number'] ?? 'lampiran';
                                $original = $file->getClientOriginalName();
                                return $reference . '_' . $original;
                            }),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'primary' => 'internal',
                        'success' => 'general',
                    ])
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'internal' => 'Internal',
                            'general' => 'Umum',
                            default => $state
                        };
                    })
                    ->label('Jenis Surat'),
                Tables\Columns\TextColumn::make('reference_number')
                    ->searchable()
                    ->sortable()
                    ->label('Nomor Surat'),
                Tables\Columns\TextColumn::make('recipient')
                    ->searchable()
                    ->label('Penerima'),
                Tables\Columns\TextColumn::make('subject')
                    ->searchable()
                    ->limit(30)
                    ->label('Perihal'),
                Tables\Columns\TextColumn::make('letter_date')
                    ->date()
                    ->sortable()
                    ->label('Tanggal Surat'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->hidden()
                    ->label('Dibuat Pada'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->hidden()
                    ->label('Diperbarui Pada'),
                Tables\Columns\IconColumn::make('attachments')
                    ->label('Lampiran')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->state(function ($record) {
                        $attachments = $record->attachments;
                        if (is_array($attachments)) {
                            foreach ($attachments as $file) {
                                if (is_string($file) && trim($file) !== '') {
                                    return true;
                                }
                            }
                        } elseif (is_string($attachments) && trim($attachments) !== '') {
                            return true;
                        }
                        return false;
                    }),
            ])
            ->filters([
                Tables\Filters\Filter::make('letter_date')
                    ->form([
                        Forms\Components\DatePicker::make('letter_date_from')
                            ->label('Tanggal Surat Dari'),
                        Forms\Components\DatePicker::make('letter_date_until')
                            ->label('Tanggal Surat Hingga'),
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
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->form([
                        Forms\Components\Section::make('Informasi Surat')
                            ->schema([
                                Forms\Components\TextInput::make('reference_number')
                                    ->label('Nomor Referensi')
                                    ->disabled(),
                                Forms\Components\TextInput::make('type')
                                    ->label('Jenis Surat')
                                    ->formatStateUsing(fn ($state) => $state === 'internal' ? 'Internal' : 'Umum')
                                    ->disabled(),
                                Forms\Components\DatePicker::make('letter_date')
                                    ->label('Tanggal Surat')
                                    ->disabled(),
                                Forms\Components\Textarea::make('recipient')
                                    ->label('Penerima')
                                    ->disabled(),
                                Forms\Components\Textarea::make('subject')
                                    ->label('Perihal')
                                    ->disabled(),
                                Forms\Components\RichEditor::make('content')
                                    ->label('Isi Surat')
                                    ->disabled(),
                                Forms\Components\Textarea::make('notes')
                                    ->label('Catatan')
                                    ->disabled(),
                            ])
                            ->columns(2),
                        Forms\Components\Section::make('Lampiran')
                            ->schema([
                                Forms\Components\FileUpload::make('attachments')
                                    ->label('File Lampiran')
                                    ->multiple()
                                    ->disabled()
                                    ->downloadable()
                                    ->openable()
                                    ->columnSpanFull(),
                            ])
                            ->collapsible(),
                    ]),
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
            'index' => Pages\ListOutgoingLetters::route('/'),
            'create' => Pages\CreateOutgoingLetter::route('/create'),
            'edit' => Pages\EditOutgoingLetter::route('/{record}/edit'),
        ];
    }
}
