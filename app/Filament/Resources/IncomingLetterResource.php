<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IncomingLetterResource\Pages;
use App\Models\IncomingLetter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use PDF;
use Illuminate\Support\Facades\Response;
use Filament\Forms\Components\Textarea;


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
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get, $context) {
                                        if ($state && $context === 'create') {
                                            $nextNumber = IncomingLetter::generateNextReferenceNumber($state);
                                            $set('reference_number', $nextNumber);
                                        } elseif ($state && $context === 'edit') {
                                            // Pada mode edit, hanya update jika jenis surat berubah
                                            $currentRefNumber = $get('reference_number');
                                            $currentPrefix = match ($state) {
                                                'internal' => 'I-',
                                                'general' => 'U-',
                                                'visit' => 'KP-',
                                                default => ''
                                            };
                                            
                                            // Jika prefix tidak cocok dengan yang sekarang, generate ulang
                                            if (!str_starts_with($currentRefNumber, $currentPrefix)) {
                                                $nextNumber = IncomingLetter::generateNextReferenceNumber($state);
                                                $set('reference_number', $nextNumber);
                                            }
                                        }
                                    }),

                                Forms\Components\TextInput::make('reference_number')
                                    ->label('No.Agenda')
                                    ->required()
                                    ->disabled()
                                    ->dehydrated()
                                    ->helperText('Nomor agenda akan dibuat otomatis berdasarkan jenis surat'),

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

                        TextArea::make('subject')
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
                            ->disk('public')
                            ->visibility('public')
                            ->columnSpanFull()
                            ->label('Lampiran (Maks 5MB per file)')
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

                Tables\Columns\IconColumn::make('attachments')
                    ->label('Lampiran')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->getStateUsing(function ($record) {
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
                Tables\Actions\ViewAction::make()
                    ->form([
                        Forms\Components\Section::make('Informasi Surat')
                            ->schema([
                                Forms\Components\TextInput::make('reference_number')
                                    ->label('No. Agenda')
                                    ->disabled(),
                                Forms\Components\TextInput::make('type')
                                    ->label('Jenis Surat')
                                    ->formatStateUsing(function ($state) {
                                        return match ($state) {
                                            'internal' => 'Internal',
                                            'general' => 'Umum',
                                            'visit' => 'Kunjungan/Prakerin',
                                            default => $state
                                        };
                                    })
                                    ->disabled(),
                                Forms\Components\DatePicker::make('received_date')
                                    ->label('Tanggal Agenda')
                                    ->disabled(),
                                Forms\Components\TextInput::make('sender')
                                    ->label('Dari')
                                    ->disabled(),
                                Forms\Components\TextInput::make('letter_number')
                                    ->label('No. Surat')
                                    ->disabled(),
                                Forms\Components\DatePicker::make('letter_date')
                                    ->label('Tanggal Surat')
                                    ->disabled(),
                                Forms\Components\Textarea::make('subject')
                                    ->label('Hal')
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
                Tables\Actions\Action::make('download_pdf')
                    ->label('Download PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function (IncomingLetter $record) {
                        return static::downloadPdf($record);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function downloadPdf(IncomingLetter $record)
    {
        $pdf = PDF::loadView('incoming-letter-disposition', compact('record'));
        $pdf->setPaper('A4', 'portrait');
        
        $filename = 'Disposisi_' . $record->reference_number . '.pdf';
        
        return Response::streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename, [
            'Content-Type' => 'application/pdf',
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