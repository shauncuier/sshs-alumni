<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Locale;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the active locale for the request.
 *
 * Order: session (set by an explicit switch) → the authenticated user's stored
 * preference → the configured default.
 *
 * Runs before HandleInertiaRequests so shared translations are already for the
 * right language.
 *
 * @see docs/06-localization.md section 2
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale($this->resolve($request));

        return $next($request);
    }

    private function resolve(Request $request): string
    {
        $session = $request->session()->get('locale');

        if (is_string($session) && Locale::isSupported($session)) {
            return $session;
        }

        // Null-safe: a freshly created User has no locale in memory until it
        // is reloaded, because the column default is applied by the database
        // on insert rather than by Eloquent.
        $preferred = $request->user()?->locale?->value;

        if ($preferred !== null && Locale::isSupported($preferred)) {
            return $preferred;
        }

        return (string) config('app.locale');
    }
}
