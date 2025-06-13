<?php

namespace App\Filament\Resources\LeaveResource\Pages;

use App\Filament\Resources\LeaveResource;
use Filament\Actions;
use App\Filament\Widgets\LeaveStatsWidget;
use App\Filament\Widgets\ManagerLeaveReminderWidget;
use App\Filament\Widgets\MonthlyLeaveSummaryWidget;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListLeave extends ListRecords
{
    protected static string $resource = LeaveResource::class;
    
    public function mount(): void
    {
        parent::mount();
        
        // Tidak perlu set default filter, biarkan kosong agar menampilkan semua data
        // HRD bisa memilih sendiri filter yang diinginkan melalui button
    }
    
    protected function getHeaderWidgets(): array
    {
        $user = Auth::user();
        $myLeave = request()->input('tableFilters.my_leave.value');
        
        $widgets = [];
        
        // Widget LeaveStatsWidget - tampil pada "Cuti Saya"
        if (LeaveStatsWidget::canView()) {
            $widgets[] = LeaveStatsWidget::class;
        }
        
        // Widget ManagerLeaveReminderWidget - existing logic
        if ($user->hasRole(['manager', 'manager_keuangan', 'manager_logistik'])) {
            $widgets[] = ManagerLeaveReminderWidget::class;
        }
        
        return $widgets;
    }
    
    protected function getFooterWidgets(): array
    {
        $widgets = [];
        if (\App\Filament\Widgets\MonthlyLeaveSummaryWidget::canView()) {
            $widgets[] = \App\Filament\Widgets\MonthlyLeaveSummaryWidget::class;
        }
        return $widgets;
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
