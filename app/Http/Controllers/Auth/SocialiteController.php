<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AppSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class SocialiteController extends Controller
{
    public function __construct(private readonly AppSettingsService $settings) {}

    /**
     * Redirect to Zoho OAuth consent screen.
     */
    public function redirect(): RedirectResponse
    {
        if (! $this->settings->get('auth.zoho_enabled', false)) {
            abort(403, 'Zoho login is disabled.');
        }

        try {
            $config = config('services.zoho');
            if (empty($config['client_id']) || empty($config['client_secret']) || empty($config['redirect'])) {
                Log::error('Incomplete Zoho configuration', ['config' => array_keys($config ?? [])]);

                return redirect()->route('login')
                    ->with('error', __('auth.zoho.config_incomplete'));
            }

            $redirectUri = rtrim((string) $config['redirect'], '/');

            Log::info('Zoho OAuth redirect', [
                'client_id' => $config['client_id'],
                'redirect_uri' => $redirectUri,
                'domain' => $config['domain'] ?? 'default',
            ]);

            return Socialite::driver('zoho')
                ->redirectUrl($redirectUri)
                ->scopes(['aaaserver.profile.READ', 'openid', 'email', 'profile'])
                ->redirect();
        } catch (Throwable $e) {
            Log::error('Zoho redirect failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('login')
                ->with('error', __('auth.zoho.redirect_failed', ['message' => $e->getMessage()]));
        }
    }

    /**
     * Handle the Zoho OAuth callback.
     */
    public function callback(): RedirectResponse
    {
        if (! $this->settings->get('auth.zoho_enabled', false)) {
            abort(403, 'Zoho login is disabled.');
        }

        try {
            $zohoUser = Socialite::driver('zoho')->user();

            if (! $zohoUser->email) {
                return redirect()->route('login')
                    ->with('error', __('auth.zoho.email_missing'));
            }

            if (! $zohoUser->id) {
                return redirect()->route('login')
                    ->with('error', __('auth.zoho.id_missing'));
            }

            // Prefer immutable Zoho ID — never trust email alone for account takeover
            $user = User::where('zoho_id', $zohoUser->id)->first();

            if (! $user) {
                if (! $this->isZohoEmailVerified($zohoUser)) {
                    Log::warning('Zoho login rejected: unverified email used for account linking', [
                        'zoho_id' => $zohoUser->id,
                        'email' => $zohoUser->email,
                    ]);

                    return redirect()->route('login')
                        ->with('error', __('auth.zoho.email_unverified'));
                }

                $user = User::where('email', $zohoUser->email)->first();

                if (! $user) {
                    return redirect()->route('login')
                        ->with('error', __('auth.zoho.account_missing'));
                }

                if ($user->zoho_id && $user->zoho_id !== (string) $zohoUser->id) {
                    Log::warning('Zoho login rejected: email matches account linked to another zoho_id', [
                        'user_id' => $user->id,
                        'zoho_id' => $zohoUser->id,
                    ]);

                    return redirect()->route('login')
                        ->with('error', __('auth.zoho.already_linked'));
                }

                $user->zoho_id = $zohoUser->id;
                if ($zohoUser->avatar) {
                    $user->avatar = $zohoUser->avatar;
                }
                $user->save();
            }

            $dirty = false;
            if ($zohoUser->name && $user->name !== $zohoUser->name) {
                $user->name = $zohoUser->name;
                $dirty = true;
            }
            if ($zohoUser->avatar && $user->avatar !== $zohoUser->avatar) {
                $user->avatar = $zohoUser->avatar;
                $dirty = true;
            }
            if ($dirty) {
                $user->save();
            }

            Auth::login($user, remember: true);

            return redirect()->intended(route('dashboard', absolute: false));
        } catch (Throwable $e) {
            Log::error('Zoho authentication failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('login')
                ->with('error', __('auth.zoho.auth_failed', ['message' => $e->getMessage()]));
        }
    }

    /**
     * Whether first-time email linking is allowed for this Zoho identity.
     * Explicit false from Zoho always wins. Explicit true allows linking.
     * If Zoho omits the claim, only configured trusted corporate domains may link.
     */
    private function isZohoEmailVerified(object $zohoUser): bool
    {
        foreach ($this->emailVerifiedClaims($zohoUser) as $value) {
            $decision = $this->interpretVerifiedClaim($value);

            if ($decision !== null) {
                return $decision;
            }
        }

        return $this->hasTrustedEmailDomain($zohoUser);
    }

    /**
     * @return array<int, mixed>
     */
    private function emailVerifiedClaims(object $zohoUser): array
    {
        $raw = is_array($zohoUser->user ?? null) ? $zohoUser->user : [];

        return [
            $raw['email_verified'] ?? null,
            $raw['EmailVerified'] ?? null,
            $raw['VerifiedEmail'] ?? null,
            data_get($zohoUser, 'email_verified'),
        ];
    }

    private function interpretVerifiedClaim(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $decision = $this->interpretVerifiedString($value);

            if ($decision !== null) {
                return $decision;
            }
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        return null;
    }

    private function interpretVerifiedString(string $value): ?bool
    {
        $normalized = strtolower(trim($value));

        if (in_array($normalized, ['true', '1', 'yes'], true)) {
            return true;
        }

        if (in_array($normalized, ['false', '0', 'no'], true)) {
            return false;
        }

        return null;
    }

    private function hasTrustedEmailDomain(object $zohoUser): bool
    {
        $trustedDomains = config('services.zoho.trusted_email_domains', []);

        if ($trustedDomains === []) {
            return false;
        }

        $email = strtolower((string) $zohoUser->email);
        $domain = substr(strrchr($email, '@') ?: '', 1);

        return $domain !== '' && in_array($domain, array_map('strtolower', $trustedDomains), true);
    }
}
