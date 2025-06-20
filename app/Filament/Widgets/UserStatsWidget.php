<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as Widget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Illuminate\Support\Str;

class UserStatsWidget extends Widget
{
    use HasWidgetShield;
    protected function getStats(): array
    {
        $user = Auth::user();
        $roleName = $user->roles->first()->name ?? 'No Role';
        
        // Wrap long role names to prevent overflow
        $wrappedRoleName = wordwrap($roleName, 20, "\n", true);

        return [
            Stat::make('Positions', $wrappedRoleName)
                ->description('Jabatan Anda dalam sistem')
                ->icon('heroicon-o-user')
                ->color('primary'),

            Stat::make('Akun Dibuat', $user->created_at->isoFormat('D MMMM Y'))
            ->icon('heroicon-o-calendar')
            ->description($user->created_at->diffForHumans())
            ->color('success'),

            Stat::make('Login Terakhir', now()->isoFormat('D MMMM Y'))
            ->icon('heroicon-o-clock')
            ->description('Aktif sekarang')
            ->color('warning'),
        ];
    }
    
    public static function canView(): bool
    {
        return Auth::check();
    }

    public static function getSort(): int
    {
        return 1; // Ensures this widget is at the top
    }
}
