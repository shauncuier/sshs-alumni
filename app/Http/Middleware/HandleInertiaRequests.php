<?php

namespace App\Http\Middleware;

use App\Services\Settings\SettingsService;
use App\Support\Locale;
use App\Support\Navigation;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
                // Sent so the UI can HIDE controls the user cannot use.
                // Hiding a button is a courtesy; the policy is the control, and
                // every protected route is enforced server-side regardless.
                'permissions' => $user?->getAllPermissions()->pluck('name')->all() ?? [],
                'roles' => $user?->getRoleNames()->all() ?? [],
            ],
            // Built from routes that actually exist, so a half-built phase
            // can never render a link that 404s.
            'nav' => fn (): array => Navigation::for(
                $user,
                $user?->member?->isApproved() ?? false,
            ),
            'locale' => app()->getLocale(),
            'locales' => Locale::available(),
            // Only the ACTIVE locale, so the payload never carries both
            // languages.
            'translations' => Locale::flattenedFor(app()->getLocale()),
            // Only settings explicitly marked public reach the frontend.
            'settings' => fn (): array => app(SettingsService::class)->publicSettings(),
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'warning' => $request->session()->get('warning'),
                'info' => $request->session()->get('info'),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
