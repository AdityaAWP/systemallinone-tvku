<?php

namespace App\Filament\Resources\LeaveResource\Pages;

use App\Filament\Resources\LeaveResource;
use Filament\Actions;
use App\Filament\Widgets\LeaveStatsWidget;
use Filament\Resources\Pages\ListRecords;

class ListLeave extends ListRecords
{
    protected static string $resource = LeaveResource::class;
    protected function getHeaderWidgets(): array
    {
        return [
            LeaveStatsWidget::class,
        ];
    }

    protected function getHeaderActions(): array
{
    return [
        Actions\CreateAction::make(),
        // Actions\Action::make('report')
        //     ->label('Generate Report')
        //     ->url(static::getResource()::getUrl('report'))
        //     ->visible(fn () => auth()->user()->can('generate_leave_report')),
    ];
}
}
