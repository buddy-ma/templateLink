<?php

namespace App\Http\Middleware;

use App\Services\AppSettingsService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function __construct(private readonly AppSettingsService $settings) {}

    public function handle(Request $request, Closure $next): Response
    {
        $supportedLocales = $this->settings->get('localization.supported_locales', ['en']);

        if (is_string($supportedLocales)) {
            $supportedLocales = json_decode($supportedLocales, true) ?? ['en'];
        }

        $default = $this->settings->get('localization.default_locale', config('app.locale', 'en'));

        // Priority: user session -> cookie -> DB default -> env default
        $locale = $request->session()->get('locale')
            ?? $request->cookie('locale')
            ?? $default;

        // Reject unsupported locales
        if (! in_array($locale, (array) $supportedLocales, strict: true)) {
            $locale = $default;
        }

        App::setLocale($locale);

        return $next($request);
    }
}
