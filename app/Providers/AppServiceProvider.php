<?php

namespace App\Providers;

use App\Filament\Resources\AssignmentResource\Pages\ListAssignments;
use App\Models\Setting;
use App\Models\SettingSite;
use Filament\Facades\Filament;
use Illuminate\Support\ServiceProvider;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Storage;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE,
            fn (): View => view('filament.hooks.table-filters'),
            ListAssignments::class,
        );
        FilamentView::registerRenderHook(
            PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
            fn (): View|string => 
                request()->route()?->getName() !== 'filament.admin.auth.login'
                    ? ''
                    : view('filament.pages.auth.google-button'),
        );
        FilamentView::registerRenderHook(
            PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
            fn (): View|string => 
                request()->route()?->getName() !== 'filament.admin.auth.login'
                    ? ''
                    : view('filament.pages.auth.intern-redirect-link'),
        );
    }
}
