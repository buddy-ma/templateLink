<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AppSettingsService;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class SocialiteController extends Controller
{
    public function __construct(private readonly AppSettingsService $settings) {}

    /**
     * Redirect to Zoho OAuth consent screen.
     */
    public function redirect()
    {
        if (! $this->settings->get('auth.zoho_enabled', false)) {
            abort(403, 'Zoho login is disabled.');
        }

        return Socialite::driver('zoho')->redirect();
    }

    /**
     * Handle the Zoho OAuth callback.
     */
    public function callback()
    {
        if (! $this->settings->get('auth.zoho_enabled', false)) {
            abort(403, 'Zoho login is disabled.');
        }

        try {
            $socialUser = Socialite::driver('zoho')->user();
        } catch (Throwable) {
            return redirect()->route('login')->withErrors([
                'email' => 'Zoho authentication failed. Please try again.',
            ]);
        }

        $user = User::where('zoho_id', $socialUser->getId())
            ->orWhere('email', $socialUser->getEmail())
            ->first();

        if ($user) {
            // Link Zoho ID if this user hasn't used Zoho before
            if (! $user->zoho_id) {
                $user->update([
                    'zoho_id' => $socialUser->getId(),
                    'avatar'  => $socialUser->getAvatar(),
                ]);
            }
        } else {
            $user = User::create([
                'name'              => $socialUser->getName(),
                'email'             => $socialUser->getEmail(),
                'zoho_id'           => $socialUser->getId(),
                'avatar'            => $socialUser->getAvatar(),
                'email_verified_at' => now(),
            ]);
        }

        Auth::login($user, remember: true);

        return redirect()->intended(route('dashboard'));
    }
}
