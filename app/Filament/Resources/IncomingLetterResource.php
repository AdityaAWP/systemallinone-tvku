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

    protected static ?string $navigationGroup = 'Letter Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Letter Information')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->options([
                                'internal' => 'Internal',
                                'general' => 'General (Umum)',
                            ])
                            ->required()
                            ->label('Letter Type')
                            ->helperText('Internal for company letters, General for external letters'),
                        Forms\Components\TextInput::make('reference_number')
                            ->disabled()
                            ->dehydrated(false)
                            ->label('Reference Number')
                            ->helperText('Will be automatically generated after saving'),
                        Forms\Components\TextInput::make('sender')
                            ->required()
                            ->maxLength(255)
                            ->label('Sender'),
                        Forms\Components\TextInput::make('subject')
                            ->required()
                            ->maxLength(255)
                            ->label('Subject'),
                        Forms\Components\DatePicker::make('letter_date')
                            ->required()
                            ->label('Letter Date'),
                        Forms\Components\DatePicker::make('received_date')
                            ->required()
                            ->default(now())
                            ->label('Received Date'),
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
                            ->directory('letters/incoming')
                            ->storeFileNamesIn('original_filename')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                            ->saveUploadedFileUsing(function (TemporaryUploadedFile $file, callable $get) {
                                $filename = $file->getClientOriginalName();
                                $path = $file->storeAs('letters/incoming', Str::random(40) . '.' . $file->getClientOriginalExtension(), 'public');
                                
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
                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'primary' => 'internal',
                        'success' => 'general',
                    ])
                    ->label('Type'),
                Tables\Columns\TextColumn::make('sender')
                    ->searchable()
                    ->label('Sender'),
                Tables\Columns\TextColumn::make('subject')
                    ->searchable()
                    ->limit(30)
                    ->label('Subject'),
                Tables\Columns\TextColumn::make('letter_date')
                    ->date()
                    ->sortable()
                    ->label('Letter Date'),
                Tables\Columns\TextColumn::make('received_date')
                    ->date()
                    ->sortable()
                    ->label('Received Date'),
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
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'internal' => 'Internal',
                        'general' => 'General (Umum)',
                    ]),
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
                Tables\Filters\Filter::make('received_date')
                    ->form([
                        Forms\Components\DatePicker::make('received_date_from'),
                        Forms\Components\DatePicker::make('received_date_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['received_date_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('received_date', '>=', $date),
                            )
                            ->when(
                                $data['received_date_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('received_date', '<=', $date),
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
            'index' => Pages\ListIncomingLetters::route('/'),
            'create' => Pages\CreateIncomingLetter::route('/create'),
            'edit' => Pages\EditIncomingLetter::route('/{record}/edit'),
        ];
    }
}