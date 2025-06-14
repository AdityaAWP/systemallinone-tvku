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
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        $prefix = $state == 'internal' ? 'I-' : 'U-';
                                        $set('reference_number', $prefix . '000');
                                    }),

                                Forms\Components\TextInput::make('reference_number')
                                    ->label('Nomor Referensi')
                                    ->required()
                                    ->default(function (Forms\Get $get) {
                                        $prefix = $get('type') == 'internal' ? 'I-' : 'U-';
                                        return $prefix . '000';
                                    }),
                                Forms\Components\DatePicker::make('letter_date')
                                    ->required()
                                    ->default(now())
                                    ->label('Tanggal Surat'),
                            ])
                            ->columns(3),
                        Forms\Components\Grid::make()
                            ->schema([

                                Forms\Components\TextArea::make('recipient')
                                    ->required()
                                    ->maxLength(255)
                                    ->rows(2)
                                    ->label('Penerima'),
                                Forms\Components\TextArea::make('subject')
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
                            ->storeFileNamesIn('original_filename')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                            ->saveUploadedFileUsing(function (TemporaryUploadedFile $file) {
                                return $file->storeAs('letters/outgoing', Str::random(40) . '.' . $file->getClientOriginalExtension(), 'public');
                            })
                            ->columnSpanFull()
                            ->label('Lampiran (Maksimal 5MB per file)'),
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
                    ->label('Nomor Referensi'),
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
            'index' => Pages\ListOutgoingLetters::route('/'),
            'create' => Pages\CreateOutgoingLetter::route('/create'),
            'edit' => Pages\EditOutgoingLetter::route('/{record}/edit'),
        ];
    }
}
