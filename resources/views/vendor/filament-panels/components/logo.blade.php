@php
    // Cek apakah sedang di halaman login dengan melihat URL atau class body
    $isLogin = request()->routeIs('filament.admin.auth.login') || str_contains(request()->url(), 'login');
@endphp
@if($isLogin)
    <img src="{{ asset('images/tvku-logo.png') }}" alt="Logo" style="height: 100px; width: auto;">
@else
    <img src="{{ asset('images/tvku-logo.png') }}" alt="Logo" style="height: 50px; width: auto;">
@endif