<?php

namespace App\Http\Middleware;

use App\Services\AppSettingsService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class HandleAppearance
{
    public function __construct(private readonly AppSettingsService $settings) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $forced = $this->settings->get('theme.force_appearance');
        $default = $this->settings->get('theme.default_appearance', 'system');

        // If admin has forced a mode, ignore user cookie
        $appearance = $forced ?? $request->cookie('appearance') ?? $default;

        View::share('appearance', $appearance);

        return $next($request);
    }
}
