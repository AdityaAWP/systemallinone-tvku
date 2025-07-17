@php
    $logoPath = \App\Models\SettingSite::get('site_logo');

    $defaultLogoUrl = asset('images/tvku-logo.png');

    $finalLogoUrl = $logoPath ? \Illuminate\Support\Facades\Storage::disk('public')->url($logoPath) : $defaultLogoUrl;

    $isLogin = request()->routeIs('filament.admin.auth.login') || request()->routeIs('filament.intern.auth.login');

    $logoHeight = $isLogin ? '100px' : '50px';
@endphp

<img src="{{ $finalLogoUrl }}" alt="Logo" style="height: {{ $logoHeight }}; width: auto;">