<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaveResource\Pages;
use App\Filament\Widgets\LeaveStatsWidget;
use App\Models\Leave;
use App\Models\LeaveQuota;
use App\Models\User;
use App\Notifications\LeaveStatusUpdated;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Actions\ExportAction;
use App\Filament\Exports\LeaveExporter;

class LeaveResource extends Resource
{
    protected static ?string $model = Leave::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationLabel = 'Leave';
    protected static ?string $title = 'Leave';
    protected function getHeaderWidgets(): array
    {
        return [
            LeaveStatsWidget::class,
        ];
    }

    public static function form(Form $form): Form
    {
        $user = Auth::user();
        $isStaff = $user->hasRole('staff');
        $isCreating = $form->getOperation() === 'create';

        // Get user's quota if staff member
        $casualQuotaRemaining = 0;
        if ($isStaff) {
            $quota = LeaveQuota::getUserQuota($user->id);
            $casualQuotaRemaining = $quota->remaining_casual_quota;
        }

        return $form
            ->schema([
                Forms\Components\Section::make('Leave Request Details')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Employee')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->visible(!$isStaff)
                            ->default(fn() => $isStaff ? $user->id : null),

                        Forms\Components\Hidden::make('user_id')
                            ->default(fn() => $user->id)
                            ->visible($isStaff),

                        Forms\Components\Select::make('leave_type')
                            ->label('Leave Type')
                            ->options([
                                'casual' => 'Casual Leave',
                                'medical' => 'Medical Leave',
                                'maternity' => 'Maternity Leave',
                                'other' => 'Other Leave',
                            ])
                            ->required()
                            ->reactive()
                            ->disabled(!$isCreating && !$isStaff)
                            ->helperText(fn(?string $state) => $state === 'casual' && $isStaff ? "You have {$casualQuotaRemaining} casual leaves remaining this year." : null),

                        Forms\Components\DatePicker::make('from_date')
                            ->label('From Date')
                            ->required()
                            ->disabled(!$isCreating && !$isStaff)
                            ->minDate(fn() => Carbon::now())
                            ->reactive(),

                        Forms\Components\DatePicker::make('to_date')
                            ->label('To Date')
                            ->required()
                            ->disabled(!$isCreating && !$isStaff)
                            ->minDate(fn(callable $get) => Carbon::parse($get('from_date')))
                            ->reactive()
                            ->afterStateUpdated(function (callable $set, callable $get) {
                                if ($get('from_date') && $get('to_date')) {
                                    $fromDate = Carbon::parse($get('from_date'));
                                    $toDate = Carbon::parse($get('to_date'));
                                    $days = abs($toDate->diffInDays($fromDate)) + 1;
                                    $set('days', $days);
                                }
                            }),

                        Forms\Components\TextInput::make('days')
                            ->label('Number of Days')
                            ->numeric()
                            ->disabled()
                            ->required(),

                        Forms\Components\Textarea::make('reason')
                            ->label('Reason')
                            ->required()
                            ->maxLength(500)
                            ->disabled(!$isCreating && !$isStaff),

                        Forms\Components\FileUpload::make('attachment')
                            ->label('Attachment (if any)')
                            ->directory('leave-attachments')
                            ->disabled(!$isCreating && !$isStaff)
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png']),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Approval Section')
                    ->schema([
                        Forms\Components\Toggle::make('approval_manager')
                            ->label('Manager Approval')
                            ->helperText('Approve or reject this leave request')
                            ->visible(fn() => $user->hasRole('manager') && !$isCreating) // Pastikan role menggunakan huruf kecil
                            ->reactive(),

                        Forms\Components\Toggle::make('approval_hrd')
                            ->label('HRD Approval')
                            ->helperText('Approve or reject this leave request')
                            ->visible(fn() => $user->hasRole('hrd') && !$isCreating) // Pastikan role menggunakan huruf kecil
                            ->reactive(),

                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Rejection Reason')
                            ->maxLength(500)
                            ->visible(function (callable $get) use ($user, $isCreating) {
                                if ($isCreating) return false;

                                if ($user->hasRole('manager') && $get('approval_manager') === false) {
                                    return true;
                                }

                                if ($user->hasRole('hrd') && $get('approval_hrd') === false) {
                                    return true;
                                }

                                return false;
                            })
                            ->required(function (callable $get) use ($user) {
                                if ($user->hasRole('manager') && $get('approval_manager') === false) {
                                    return true;
                                }

                                if ($user->hasRole('hrd') && $get('approval_hrd') === false) {
                                    return true;
                                }

                                return false;
                            }),
                    ])
                    ->visible(!$isCreating),

                Forms\Components\Section::make('Status Information')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Current Status')
                            ->options([
                                'pending' => 'Pending',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                            ])
                            ->disabled()
                            ->default('pending')
                            ->visible(!$isCreating)
                    ])
                    ->visible($isStaff && !$isCreating),
            ]);
    }

    public static function table(Table $table): Table
    {
        $user = Auth::user();
        $isStaff = $user->hasRole('staff'); 

        return $table
            ->headerActions([
                ExportAction::make()
                ->exporter(LeaveExporter::class),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable()
                    ->visible(!$isStaff),

                Tables\Columns\TextColumn::make('leave_type')
                    ->label('Leave Type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'casual' => 'success',
                        'medical' => 'warning',
                        'maternity' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('from_date')
                    ->label('From')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('to_date')
                    ->label('To')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('days')
                    ->label('Days')
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('approval_manager')
                    ->label('Manager')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('approval_hrd')
                    ->label('HRD')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->alignCenter(),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'danger' => 'rejected',
                        'warning' => 'pending',
                        'success' => 'approved',
                    ]),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Requested On')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('leave_type')
                    ->label('Leave Type')
                    ->options([
                        'casual' => 'Casual Leave',
                        'medical' => 'Medical Leave',
                        'maternity' => 'Maternity Leave',
                        'other' => 'Other Leave',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),

                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('to'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('from_date', '>=', $date),
                            )
                            ->when(
                                $data['to'],
                                fn(Builder $query, $date): Builder => $query->whereDate('to_date', '<=', $date),
                            );
                    })
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => auth()->user()->hasRole('hrd')),

                    // Aksi ekspor menggunakan Filament
                    Tables\Actions\ExportAction::make()
                        ->exporter(LeaveExporter::class)
                        ->label('Ekspor')
                        ->color('success')
                        ->icon('heroicon-o-document-download')
                        // Hanya tampilkan untuk HRD, Manajer, dan Staff
                        ->visible(fn() => auth()->user()->hasAnyRole(['hrd', 'manager', 'staff'])),
                ]),
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
            'index' => Pages\ListLeave::route('/'),
            'create' => Pages\CreateLeave::route('/create'),
            'edit' => Pages\EditLeave::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        if ($user->hasRole('staff')) { // Pastikan role menggunakan huruf kecil
            return parent::getEloquentQuery()->where('user_id', $user->id);
        }

        return parent::getEloquentQuery();
    }
}
