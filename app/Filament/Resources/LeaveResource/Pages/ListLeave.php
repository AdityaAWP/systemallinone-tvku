<?php

namespace App\Filament\Resources\LeaveResource\Pages;

use App\Filament\Resources\LeaveResource;
use Filament\Actions;
use App\Filament\Widgets\LeaveStatsWidget;
use App\Filament\Widgets\ManagerLeaveReminderWidget;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListLeave extends ListRecords
{
    protected static string $resource = LeaveResource::class;
    protected function getHeaderWidgets(): array
    {
        return [
            LeaveStatsWidget::class,
            ManagerLeaveReminderWidget::class,
        ];
    }
    

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),
            'pending' => Tab::make('Pending')
                ->query(fn($query) => $query->where('status', 'pending')),
            'approved' => Tab::make('Approved')
                ->query(fn($query) => $query->where('status', 'approved')),
            'rejected' => Tab::make('Rejected')
                ->query(fn($query) => $query->where('status', 'rejected')),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Data')
                ->icon('heroicon-o-plus'),
        ];
    }
}
