<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssignmentResource\Pages;
use App\Models\Assignment;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AssignmentResource extends Resource
{
    protected static ?string $model = Assignment::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Assignments';

    protected static ?string $navigationGroup = 'Finance Management';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('approval_status', Assignment::STATUS_PENDING)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getNavigationBadge() > 0 ? 'warning' : null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Assignment')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Basic Information')
                            ->schema([
                                Forms\Components\Select::make('type')
                                    ->label('Assignment Type')
                                    ->options([
                                        Assignment::TYPE_FREE => 'Free',
                                        Assignment::TYPE_PAID => 'Paid',
                                        Assignment::TYPE_BARTER => 'Barter',
                                    ])
                                    ->required()
                                    ->live()
                                    ->default(Assignment::TYPE_FREE)
                                    ->disabled(fn($context) => $context === 'edit' && Auth::user()->hasRole('direktur_keuangan')),

                                    Forms\Components\DatePicker::make('created_date')
                                    ->required()
                                    ->label('Created date'),

                                Forms\Components\DatePicker::make('deadline')
                                    ->required()
                                    ->minDate(Carbon::now())
                                    ->disabled(fn($context) => $context === 'edit' && Auth::user()->hasRole('direktur_keuangan')),

                                Forms\Components\TextInput::make('client')
                                    ->required()
                                    ->maxLength(255)
                                    ->disabled(fn($context) => $context === 'edit' && Auth::user()->hasRole('direktur_keuangan')),

                                Forms\Components\TextInput::make('spp_number')
                                    ->label('SPP Number')
                                    ->required()
                                    ->maxLength(255)
                                    ->disabled(fn($context) => $context === 'edit' && Auth::user()->hasRole('direktur_keuangan')),

                                Forms\Components\TextInput::make('spk_number')
                                    ->label('SPK Number')
                                    ->maxLength(255)
                                    ->hidden(fn(Forms\Get $get) => $get('type') === Assignment::TYPE_FREE)
                                    ->disabled(fn($context) => $context === 'edit' && Auth::user()->hasRole('direktur_keuangan')),

                                Forms\Components\Textarea::make('description')
                                    ->required()
                                    ->columnSpanFull()
                                    ->disabled(fn($context) => $context === 'edit' && Auth::user()->hasRole('direktur_keuangan')),


                            ]),

                        Forms\Components\Tabs\Tab::make('Financial Details')
                            ->schema([
                                Forms\Components\TextInput::make('amount')
                                    ->label('Nominal Amount')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->inputMode('decimal')
                                    ->hidden(fn(Forms\Get $get) => $get('type') === Assignment::TYPE_FREE)
                                    ->disabled(fn($context) => $context === 'edit' && Auth::user()->hasRole('direktur_keuangan')),

                                Forms\Components\TextInput::make('marketing_expense')
                                    ->label('Marketing Expense')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->inputMode('decimal')
                                    ->hidden(fn(Forms\Get $get) => $get('type') === Assignment::TYPE_FREE)
                                    ->disabled(fn($context) => $context === 'edit' && Auth::user()->hasRole('direktur_keuangan')),
                            ])
                            ->hidden(fn(Forms\Get $get) => $get('type') === Assignment::TYPE_FREE),

                        Forms\Components\Tabs\Tab::make('Additional Information')
                            ->schema([
                                Forms\Components\Textarea::make('production_notes')
                                    ->columnSpanFull()
                                    ->disabled(fn($context) => $context === 'edit' && Auth::user()->hasRole('direktur_keuangan')),

                                Forms\Components\Select::make('priority')
                                    ->label('Priority Level')
                                    ->options([
                                        Assignment::PRIORITY_NORMAL => 'Normal',
                                        Assignment::PRIORITY_IMPORTANT => 'Important',
                                        Assignment::PRIORITY_VERY_IMPORTANT => 'Very Important',
                                    ])
                                    ->default(Assignment::PRIORITY_NORMAL)
                                    ->hidden(fn(Forms\Get $get) => $get('type') !== Assignment::TYPE_PAID)
                                    ->disabled(fn($context) => $context === 'edit' && Auth::user()->hasRole('direktur_keuangan')),
                            ]),

                        Forms\Components\Tabs\Tab::make('Approval')
                            ->schema([
                                Forms\Components\Select::make('approval_status')
                                    ->label('Status')
                                    ->options([
                                        Assignment::STATUS_PENDING => 'Pending',
                                        Assignment::STATUS_APPROVED => 'Approved',
                                        Assignment::STATUS_DECLINED => 'Declined',
                                    ])
                                    ->default(Assignment::STATUS_PENDING)
                                    ->disabled(fn() => !Auth::user()->hasRole('direktur_keuangan'))
                                    ->hidden(fn(Forms\Get $get) => in_array($get('type'), [Assignment::TYPE_FREE, Assignment::TYPE_BARTER])),

                                Forms\Components\Placeholder::make('approved_at')
                                    ->label('Approved/Declined At')
                                    ->content(fn(Assignment $record): string => $record->approved_at ? $record->approved_at->format('d M Y H:i') : '-')
                                    ->hiddenOn('create'),

                                Forms\Components\Placeholder::make('approver')
                                    ->label('Approved/Declined By')
                                    ->content(fn(Assignment $record): string => $record->approver ? $record->approver->name : '-')
                                    ->hiddenOn('create'),
                            ])
                            ->hidden(fn($context) => $context === 'create'),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('client')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('spp_number')
                    ->label('SPP Number')
                    ->searchable(),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        Assignment::TYPE_FREE => 'gray',
                        Assignment::TYPE_PAID => 'success',
                        Assignment::TYPE_BARTER => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('deadline')
                    ->date('d M Y')
                    ->sortable()
                    ->color(fn(Assignment $record) =>
                    $record->deadline->isPast() ? 'danger' : ($record->deadline->isToday() ? 'warning' : 'success')),

                Tables\Columns\TextColumn::make('amount')
                    ->money('IDR')
                    ->sortable()
                    ->hidden(fn() => Auth::user()->hasRole('direktur_keuangan')),

                Tables\Columns\TextColumn::make('priority')
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            Assignment::PRIORITY_NORMAL => 'Normal',
                            Assignment::PRIORITY_IMPORTANT => 'Important',
                            Assignment::PRIORITY_VERY_IMPORTANT => 'Very Important',
                            default => $state,
                        };
                    })
                    ->colors([
                        'secondary' => Assignment::PRIORITY_NORMAL,
                        'warning' => Assignment::PRIORITY_IMPORTANT,
                        'danger' => Assignment::PRIORITY_VERY_IMPORTANT,
                    ])
                    ->hidden(fn() => Auth::user()->hasRole('direktur_keuangan')),

                Tables\Columns\TextColumn::make('approval_status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        Assignment::STATUS_PENDING => 'warning',
                        Assignment::STATUS_APPROVED => 'success',
                        Assignment::STATUS_DECLINED => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_date')
                    ->label('Created Date')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        Assignment::TYPE_FREE => 'Free',
                        Assignment::TYPE_PAID => 'Paid',
                        Assignment::TYPE_BARTER => 'Barter',
                    ]),

                Tables\Filters\SelectFilter::make('approval_status')
                    ->options([
                        Assignment::STATUS_PENDING => 'Pending',
                        Assignment::STATUS_APPROVED => 'Approved',
                        Assignment::STATUS_DECLINED => 'Declined',
                    ]),

                Tables\Filters\Filter::make('deadline')
                    ->form([
                        Forms\Components\DatePicker::make('deadline_from'),
                        Forms\Components\DatePicker::make('deadline_to'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['deadline_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('deadline', '>=', $date),
                            )
                            ->when(
                                $data['deadline_to'],
                                fn(Builder $query, $date): Builder => $query->whereDate('deadline', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->mutateRecordDataUsing(function (Model $record, array $data): array {
                        // Set approver data when updating approval status
                        if (
                            Auth::user()->hasRole('direktur_keuangan') &&
                            $record->approval_status !== $data['approval_status'] &&
                            in_array($data['approval_status'], [Assignment::STATUS_APPROVED, Assignment::STATUS_DECLINED])
                        ) {
                            $data['approved_by'] = Auth::id();
                            $data['approved_at'] = now();
                        }
                        return $data;
                    }),
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn(Assignment $record) =>
                    $record->approval_status !== Assignment::STATUS_PENDING ||
                        Auth::user()->hasRole('direktur_keuangan')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->hidden(fn() => Auth::user()->hasRole('direktur_keuangan')),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->hidden(fn() => Auth::user()->hasRole('direktur_keuangan')),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListAssignments::route('/'),
            'create' => Pages\CreateAssignment::route('/create'),
            'view' => Pages\ViewAssignment::route('/{record}'),
            'edit' => Pages\EditAssignment::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }
}
