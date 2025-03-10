<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class UserStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();
        
        return [
            Stat::make('Tipe Akun', $user->position ? $user->position->name : 'Belum ditetapkan')
                ->description('Jabatan Anda dalam sistem')
                ->descriptionIcon('heroicon-m-user-circle')
                ->color('primary'),
            
            Stat::make('Akun Dibuat', $user->created_at->isoFormat('D MMMM Y'))
                ->description($user->created_at->diffForHumans())
                ->descriptionIcon('heroicon-m-calendar')
                ->color('success'),
            
            Stat::make('Login Terakhir', now()->isoFormat('D MMMM Y'))
                ->description('Aktif sekarang')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
        ];
    }
}