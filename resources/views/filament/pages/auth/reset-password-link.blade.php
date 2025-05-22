@if (filament()->hasPasswordReset())
<div class="flex items-center justify-end mt-2">
    <a href="{{ filament()->getRequestPasswordResetUrl() }}" class="text-sm text-primary-600 hover:text-primary-500">
        {{ __('filament-panels::pages/auth/login.actions.request_password_reset.label') }}
    </a>
</div>
@endif