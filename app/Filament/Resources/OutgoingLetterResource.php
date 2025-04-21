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

    protected static ?string $navigationGroup = 'Letter Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Letter Information')
                    ->schema([
                        Forms\Components\TextInput::make('reference_number')
                            ->disabled()
                            ->dehydrated(false)
                            ->label('Reference Number')
                            ->helperText('Will be automatically generated after saving'),
                        Forms\Components\TextInput::make('recipient')
                            ->required()
                            ->maxLength(255)
                            ->label('Recipient'),
                        Forms\Components\TextInput::make('subject')
                            ->required()
                            ->maxLength(255)
                            ->label('Subject'),
                        Forms\Components\DatePicker::make('letter_date')
                            ->required()
                            ->default(now())
                            ->label('Letter Date'),
                        Forms\Components\RichEditor::make('content')
                            ->columnSpanFull()
                            ->label('Letter Content'),
                        Forms\Components\Textarea::make('notes')
                            ->maxLength(65535)
                            ->columnSpanFull()
                            ->label('Notes'),
                    ]),
                    
                Forms\Components\Section::make('Attachments')
                    ->schema([
                        Forms\Components\FileUpload::make('attachments')
                            ->multiple()
                            ->maxSize(5120) // 5MB limit
                            ->directory('letters/outgoing')
                            ->storeFileNamesIn('original_filename')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                            ->saveUploadedFileUsing(function (TemporaryUploadedFile $file, callable $get) {
                                $filename = $file->getClientOriginalName();
                                $path = $file->storeAs('letters/outgoing', Str::random(40) . '.' . $file->getClientOriginalExtension(), 'public');
                                
                                return [
                                    'path' => $path,
                                    'filename' => $filename,
                                    'mime_type' => $file->getMimeType(),
                                    'size' => $file->getSize()
                                ];
                            })
                            ->columnSpanFull()
                            ->label('Attachments (Max 5MB each)'),
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
                    ->label('Reference Number'),
                Tables\Columns\TextColumn::make('recipient')
                    ->searchable()
                    ->label('Recipient'),
                Tables\Columns\TextColumn::make('subject')
                    ->searchable()
                    ->limit(30)
                    ->label('Subject'),
                Tables\Columns\TextColumn::make('letter_date')
                    ->date()
                    ->sortable()
                    ->label('Letter Date'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('letter_date')
                    ->form([
                        Forms\Components\DatePicker::make('letter_date_from'),
                        Forms\Components\DatePicker::make('letter_date_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['letter_date_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('letter_date', '>=', $date),
                            )
                            ->when(
                                $data['letter_date_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('letter_date', '<=', $date),
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