<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            // Check if user has is_active field and if it's false
            if (isset($user->is_active) && !$user->is_active) {
                Auth::logout();
                
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Akun Anda telah dinonaktifkan. Silakan hubungi administrator.'
                    ], 403);
                }
                
                return redirect()->route('filament.admin.auth.login')
                    ->withErrors(['email' => 'Akun Anda telah dinonaktifkan. Silakan hubungi administrator.']);
            }
        }
        
        return $next($request);
    }
}
