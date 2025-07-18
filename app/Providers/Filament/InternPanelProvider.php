<?php
namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Auth\LoginIntern;
use App\Filament\Resources\JournalResource; // Add this import
use App\Filament\InternWidgets\JournalReminderWidget;
use App\Filament\InternWidgets\InternProfileWidget;
use App\Http\Middleware\CheckProfileCompletion;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Auth\Register;
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

class InternPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('intern')
            ->path('intern')
            ->brandLogo(asset('images/tvku-logo.png'))
            ->login(LoginIntern::class)
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
            ->resources([
                JournalResource::class,
            ])
            ->discoverResources(in: app_path('Filament/InternResources'), for: 'App\\Filament\\InternResources')
            ->discoverPages(in: app_path('Filament/InternPages'), for: 'App\\Filament\\InternPages')
            ->pages([
                \Filament\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/InternWidgets'), for: 'App\\Filament\\InternWidgets')
            ->widgets([
                InternProfileWidget::class,
                JournalReminderWidget::class,
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
                CheckProfileCompletion::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->authGuard('intern')
            ->spa()
            ->sidebarCollapsibleOnDesktop();
    }
}