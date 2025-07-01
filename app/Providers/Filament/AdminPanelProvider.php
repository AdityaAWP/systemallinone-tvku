<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Auth\RequestPasswordReset;
use App\Filament\Widgets\UserStatsWidget;
use Filament\Http\Middleware\Authenticate;
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Filament\Widgets\ManagerLeaveReminderWidget;
use App\Filament\Widgets\MonthlyOvertimeReminderWidget;

// ADD THESE TWO
use App\Models\Setting;
use App\Models\SettingSite;
use Illuminate\Support\Facades\Storage;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
    
        return $panel
            ->default()
            ->id('admin')
            ->path('/')
            ->login(Login::class)
            ->passwordReset()
            ->colors([
                'primary' => Color::Blue,
                'secondary' => Color::Gray,
                'success' => Color::Green,
                'warning' => Color::Yellow,
                'danger' => Color::Red,
                'error' => Color::Red,
                'info' => Color::Cyan,
                'gray' => Color::Gray,
                'blue' => Color::Blue,
                'indigo' => Color::Indigo,
                'purple' => Color::Purple,
                'pink' => Color::Pink,
                'red' => Color::Red,
                'orange' => Color::Orange,
                'yellow' => Color::Yellow,
                'green' => Color::Green,
                'teal' => Color::Teal,
                'cyan' => Color::Cyan,
                'zinc' => Color::Zinc,
                'dark' => Color::Zinc,
            ])
            ->plugin(
                FilamentFullCalendarPlugin::make()
                    ->schedulerLicenseKey('')
                    ->selectable(true)
                    ->editable()
                    ->timezone(config('app.timezone'))
                    ->locale(config('app.locale'))
                    ->plugins([
                        'dayGrid',
                        'timeGrid',
                    ])
                    ->config([])
            )
            ->databaseNotifications()
            ->databaseNotificationsPolling('5s')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                \Filament\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                UserStatsWidget::class,
                ManagerLeaveReminderWidget::class,
                MonthlyOvertimeReminderWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugins([
                \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make()
            ])
            ->spa()
            ->sidebarCollapsibleOnDesktop();
    }
}