<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as Widget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class UserStatsWidget extends Widget
{
    protected function getStats(): array
    {
        $user = Auth::user();
        $roleName = $user->roles->first()->name ?? 'No Role';

        return [
            Stat::make('Positions', $roleName)
            ->description('Jabatan Anda dalam sistem')->icon('heroicon-o-user')
            ->color('primary'),

            Stat::make('Akun Dibuat', $user->created_at->isoFormat('D MMMM Y'))->icon('heroicon-o-calendar')
                ->description($user->created_at->diffForHumans())
                ->color('success'),

            Stat::make('Login Terakhir', now()->isoFormat('D MMMM Y'))->icon('heroicon-o-clock')
                ->description('Aktif sekarang')
                ->color('warning'),
        ];
    }

    public static function getSort(): int
    {
        return 1; // Ensures this widget is at the top
    }
}
