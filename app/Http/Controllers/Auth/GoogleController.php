<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    /**
     * Redirect to Google OAuth.
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')
            ->redirect();
    }

    /**
     * Handle the Google callback.
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            
            // Check if user exists
            $existingUser = User::where('email', $googleUser->email)->first();
            
            if ($existingUser) {
                // Update Google ID if not set
                if (empty($existingUser->google_id)) {
                    $existingUser->google_id = $googleUser->id;
                    $existingUser->avatar = $googleUser->avatar;
                    $existingUser->save();
                }
                
                Auth::login($existingUser);
                
                return redirect()->route('filament.admin.pages.dashboard');
            } else {
                // No access if the user doesn't exist and needs to be created by admin
                return redirect()->route('filament.admin.auth.login')
                    ->with('error', 'Akun tidak ditemukan. Silakan hubungi administrator.');
            }
            
        } catch (\Exception $e) {
            if ($e->getCode() == 403) {
                return redirect()->route('error.403');
            }
            return redirect()->route('filament.admin.auth.login')
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}