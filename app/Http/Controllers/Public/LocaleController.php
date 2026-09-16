<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\Locale as LocaleEnum;
use App\Http\Controllers\Controller;
use App\Support\Locale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    /**
     * Switch the active language.
     *
     * The locale is validated against the allow-list rather than trusted from
     * the URL, and the redirect goes back rather than to a supplied target, so
     * neither can be used to point somewhere unintended.
     */
    public function switch(Request $request, string $locale): RedirectResponse
    {
        if (! Locale::isSupported($locale)) {
            return back();
        }

        $request->session()->put('locale', $locale);

        // Persist the choice for signed-in users so it survives the session.
        $user = $request->user();

        if ($user !== null) {
            $user->forceFill(['locale' => LocaleEnum::from($locale)])->save();
        }

        return back();
    }
}
