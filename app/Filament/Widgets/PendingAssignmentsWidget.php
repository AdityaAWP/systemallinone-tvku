<?php

namespace App\Filament\Widgets;

use App\Models\Assignment;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class PendingAssignmentsWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Assignment::query()
                    ->where('approval_status', Assignment::STATUS_PENDING)
                    ->where('type', Assignment::TYPE_PAID)
                    ->latest()
                    ->limit(5)
            )
            ->heading('Recent Pending Assignments')
            ->description('Showing 5 most recent assignments awaiting approval')
            ->columns([
                Tables\Columns\TextColumn::make('client')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('amount')
                    ->money('IDR')
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('priority')
                    ->options([
                        'heroicon-o-signal' => Assignment::PRIORITY_NORMAL,
                        'heroicon-o-exclamation-triangle' => Assignment::PRIORITY_IMPORTANT,
                        'heroicon-o-exclamation-circle' => Assignment::PRIORITY_VERY_IMPORTANT,
                    ])
                    ->colors([
                        'secondary' => Assignment::PRIORITY_NORMAL,
                        'warning' => Assignment::PRIORITY_IMPORTANT,
                        'danger' => Assignment::PRIORITY_VERY_IMPORTANT,
                    ]),
                
                Tables\Columns\TextColumn::make('deadline')
                    ->date('d M Y')
                    ->color(fn (Assignment $record) => 
                        $record->deadline->isPast() ? 'danger' : 
                        ($record->deadline->isToday() ? 'warning' : 'success')),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted on')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->url(fn (Assignment $record): string => route('filament.admin.resources.assignments.view', $record))
                    ->icon('heroicon-o-eye'),
                    
                Tables\Actions\Action::make('approve')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->action(function (Assignment $record) {
                        $record->update([
                            'approval_status' => Assignment::STATUS_APPROVED,
                            'approved_by' => Auth::id(),
                            'approved_at' => now(),
                        ]);
                    }),
            ])
            ->emptyStateHeading('No pending assignments')
            ->emptyStateDescription('All assignments have been reviewed.')
            ->paginated(false);
    }
    
    public static function canView(): bool
    {
        return Auth::check() && Auth::user()->hasRole('direktur_keuangan');
    }
}