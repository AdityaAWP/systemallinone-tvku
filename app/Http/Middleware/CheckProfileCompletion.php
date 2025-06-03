<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use App\Filament\InternPages\EditProfileIntern; // Import the page class

class CheckProfileCompletion
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::guard('intern')->check()) {
            $user = Auth::guard('intern')->user();
            
            // Check if profile is incomplete (adjust these conditions based on your user model)
            $isProfileIncomplete = empty($user->birth) || 
                                 empty($user->gender) || 
                                 empty($user->no_phone) ||
                                 empty($user->ktp) ||
                                 empty($user->last_education) ||
                                 empty($user->division_id) ||
                                 empty($user->address);
            
            // Check if the current route is already the edit profile page to avoid redirect loops or redundant notifications
            $isCurrentRouteEditProfile = $request->routeIs(EditProfileIntern::getRouteName()); // Assuming Filament registers a route name
            // Alternatively, check the URL path directly if route name isn't reliable:
            // $isCurrentRouteEditProfile = $request->is(ltrim(EditProfileIntern::getUrlPath(), '/'));

            if ($isProfileIncomplete && !session('profile_reminder_shown') && !$isCurrentRouteEditProfile) {
                // Show notification popup
                Notification::make()
                    ->title('Complete Your Profile')
                    ->body('Please complete your profile information to get the best experience.')
                    ->warning()
                    ->persistent()
                    ->actions([
                        \Filament\Notifications\Actions\Action::make('complete_profile')
                            ->label('Complete Profile')
                            // Use the getUrl() method from your Filament page class
                            ->url(EditProfileIntern::getUrl()) 
                            ->button(),
                        \Filament\Notifications\Actions\Action::make('later')
                            ->label('Later')
                            ->close(),
                    ])
                    ->send();
                
                // Mark that reminder has been shown in this session
                session(['profile_reminder_shown' => true]);
            }
        }
        
        return $next($request);
    }
}
