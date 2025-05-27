<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IncomingLetterResource\Pages;
use App\Models\IncomingLetter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class IncomingLetterResource extends Resource
{
    protected static ?string $model = IncomingLetter::class;
    protected static ?string $navigationIcon = 'heroicon-o-inbox';
    protected static ?string $label = 'Surat Masuk';
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
                                        'visit' => 'Kunjungan/Prakerin',
                                    ])
                                    ->required()
                                    ->label('Jenis Surat')
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        $prefix = match ($state) {
                                            'internal' => 'I-',
                                            'general' => 'U-',
                                            'visit' => 'KP-',
                                            default => ''
                                        };
                                        $set('reference_number', $prefix . '000');
                                    }),

                                Forms\Components\TextInput::make('reference_number')
                                    ->label('No.Agenda')
                                    ->required(),

                                Forms\Components\DatePicker::make('received_date')
                                    ->required()
                                    ->default(now())
                                    ->label('Tanggal Agenda'),
                            ])
                            ->columns(3),

                        Forms\Components\TextInput::make('sender')
                            ->required()
                            ->maxLength(255)
                            ->label('Dari')
                            ->columnSpanFull(),

                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\TextInput::make('letter_number')
                                    ->maxLength(255)
                                    ->label('No. Surat'),

                                Forms\Components\DatePicker::make('letter_date')
                                    ->required()
                                    ->label('Tanggal Surat (dd/mm/yyyy)'),
                            ])
                            ->columns(2),

                        Forms\Components\TextArea::make('subject')
                            ->required()
                            ->maxLength(255)
                            ->label('Hal')
                            ->columnSpanFull()
                            ->helperText('Contoh: Permohonan Kerjasama, Undangan Rapat, dll.')
                            ->rows(5),

                        Forms\Components\RichEditor::make('content')
                            ->columnSpanFull()
                            ->label('Isi Surat'),

                        Forms\Components\Textarea::make('notes')
                            ->maxLength(65535)
                            ->columnSpanFull()
                            ->label('Catatan'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Lampiran')
                    ->schema([
                        Forms\Components\FileUpload::make('attachments')
                            ->multiple()
                            ->maxSize(5120)
                            ->directory('letters/incoming')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
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
                    ->label('Nomor Agenda'),

                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'primary' => 'internal',
                        'success' => 'general',
                        'warning' => 'visit',
                    ])
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'internal' => 'Internal',
                            'general' => 'Umum',
                            'visit' => 'Kunjungan/Prakerin',
                            default => $state
                        };
                    })
                    ->label('Jenis Surat'),

                Tables\Columns\TextColumn::make('sender')
                    ->searchable()
                    ->label('Dari'),

                Tables\Columns\TextColumn::make('letter_number')
                    ->searchable()
                    ->label('No. Surat'),

                Tables\Columns\TextColumn::make('subject')
                    ->searchable()
                    ->limit(30)
                    ->label('Hal'),

                Tables\Columns\TextColumn::make('letter_date')
                    ->date('d/m/Y')
                    ->sortable()
                    ->label('Tanggal Surat'),

                Tables\Columns\TextColumn::make('received_date')
                    ->date('d/m/Y')
                    ->sortable()
                    ->label('Tanggal Agenda'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'internal' => 'Internal',
                        'general' => 'Umum',
                        'visit' => 'Kunjungan/Prakerin',
                    ])
                    ->label('Jenis Surat'),

                Tables\Filters\Filter::make('dates')
                    ->form([
                        Forms\Components\DatePicker::make('letter_date_from')
                            ->label('Tanggal Surat Dari'),
                        Forms\Components\DatePicker::make('letter_date_to')
                            ->label('Tanggal Surat Sampai'),
                        Forms\Components\DatePicker::make('received_date_from')
                            ->label('Tanggal Agenda Dari'),
                        Forms\Components\DatePicker::make('received_date_to')
                            ->label('Tanggal Agenda Sampai'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['letter_date_from'],
                                fn($q) => $q->where('letter_date', '>=', $data['letter_date_from'])
                            )
                            ->when(
                                $data['letter_date_to'],
                                fn($q) => $q->where('letter_date', '<=', $data['letter_date_to'])
                            )
                            ->when(
                                $data['received_date_from'],
                                fn($q) => $q->where('received_date', '>=', $data['received_date_from'])
                            )
                            ->when(
                                $data['received_date_to'],
                                fn($q) => $q->where('received_date', '<=', $data['received_date_to'])
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
        return [];
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
