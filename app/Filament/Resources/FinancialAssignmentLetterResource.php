<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FinancialAssignmentLetterResource\Pages;
use App\Models\FinancialAssignmentLetter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class FinancialAssignmentLetterResource extends Resource
{
    protected static ?string $model = FinancialAssignmentLetter::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Letter Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Letter Information')
                    ->schema([
                        Forms\Components\TextInput::make('reference_number')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->label('Reference Number'),
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->label('Letter Title'),
                        Forms\Components\DatePicker::make('letter_date')
                            ->required()
                            ->label('Date'),
                        Forms\Components\RichEditor::make('content')
                            ->required()
                            ->columnSpanFull()
                            ->label('Letter Content'),
                        Forms\Components\TextInput::make('created_by')
                            ->default(function () {
                                return Auth::user()->name;
                            })
                            ->required()
                            ->maxLength(255)
                            ->label('Created By')
                            ->disabled(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'pending_manager' => 'Pending Manager Approval',
                                'pending_director' => 'Pending Director Approval',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                            ])
                            ->default('draft')
                            ->required()
                            ->disabled(function () {
                                $user = Auth::user();
                                
                                // If user is manager_keuangan or direktur_utama, don't disable
                                if ($user->hasRole(['manager_keuangan', 'direktur_utama'])) {
                                    return false;
                                }
                                
                                return true;
                            }),
                        Forms\Components\Textarea::make('notes')
                            ->maxLength(65535)
                            ->columnSpanFull()
                            ->label('Notes'),
                    ]),
                
                Forms\Components\Section::make('Approvals')
                    ->schema([
                        Forms\Components\Toggle::make('manager_approval')
                            ->label('Manager Approval')
                            ->default(false)
                            ->disabled(function () {
                                // Only enable for manager_keuangan role
                                return !Auth::user()->hasRole('manager_keuangan');
                            })
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state) {
                                    $set('manager_approval_date', now());
                                    $set('status', 'pending_director');
                                } else {
                                    $set('manager_approval_date', null);
                                    $set('status', 'pending_manager');
                                }
                            }),
                        Forms\Components\DateTimePicker::make('manager_approval_date')
                            ->label('Manager Approval Date')
                            ->disabled(),
                            
                        Forms\Components\Toggle::make('director_approval')
                            ->label('Director Approval')
                            ->default(false)
                            ->disabled(function () {
                                // Only enable for direktur_utama role
                                return !Auth::user()->hasRole('direktur_utama');
                            })
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state) {
                                    $set('director_approval_date', now());
                                    $set('status', 'approved');
                                } else {
                                    $set('director_approval_date', null);
                                    $set('status', 'pending_director');
                                }
                            }),
                        Forms\Components\DateTimePicker::make('director_approval_date')
                            ->label('Director Approval Date')
                            ->disabled(),
                    ]),
                    
                Forms\Components\Section::make('Attachments')
                    ->schema([
                        Forms\Components\FileUpload::make('attachments')
                            ->multiple()
                            ->maxSize(5120) // 5MB limit
                            ->directory('letters/financial')
                            ->storeFileNamesIn('original_filename')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                            ->saveUploadedFileUsing(function (TemporaryUploadedFile $file, callable $get) {
                                $filename = $file->getClientOriginalName();
                                $path = $file->storeAs('letters/financial', Str::random(40) . '.' . $file->getClientOriginalExtension(), 'public');
                                
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
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->limit(30)
                    ->label('Title'),
                Tables\Columns\TextColumn::make('letter_date')
                    ->date()
                    ->sortable()
                    ->label('Date'),
                Tables\Columns\TextColumn::make('created_by')
                    ->searchable()
                    ->label('Created By'),
                Tables\Columns\IconColumn::make('manager_approval')
                    ->boolean()
                    ->label('Manager Approval'),
                Tables\Columns\IconColumn::make('director_approval')
                    ->boolean()
                    ->label('Director Approval'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'pending_manager' => 'warning',
                        'pending_director' => 'info',
                        'approved' => 'success',
                        'rejected' => 'danger',
                    })
                    ->label('Status'),
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
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'pending_manager' => 'Pending Manager Approval',
                        'pending_director' => 'Pending Director Approval',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),
                Tables\Filters\Filter::make('manager_approval')
                    ->query(fn (Builder $query): Builder => $query->where('manager_approval', true))
                    ->label('Manager Approved'),
                Tables\Filters\Filter::make('director_approval')
                    ->query(fn (Builder $query): Builder => $query->where('director_approval', true))
                    ->label('Director Approved'),
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
            'index' => Pages\ListFinancialAssignmentLetters::route('/'),
            'create' => Pages\CreateFinancialAssignmentLetter::route('/create'),
            'edit' => Pages\EditFinancialAssignmentLetter::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        // If it's a regular staff, only show their own letters
        if (Auth::user()->hasRole('staff_keuangan')) {
            return $query->where('created_by', Auth::user()->name);
        }
        
        return $query;
    }
}