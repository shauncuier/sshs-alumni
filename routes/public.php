<?php

declare(strict_types=1);

use App\Http\Controllers\Public\BatchController;
use App\Http\Controllers\Public\CommitteeController;
use App\Http\Controllers\Public\EventController;
use App\Http\Controllers\Public\GivingController;
use App\Http\Controllers\Public\JubileeController;
use App\Http\Controllers\Public\MemberVerifyController;
use App\Http\Controllers\Public\RegistrationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public routes
|--------------------------------------------------------------------------
|
| No authentication. Indexed by search engines except where noted.
|
| The alumni directory is deliberately NOT here — it requires an approved
| membership. See routes/member.php.
|
| @see docs/03-routes.md section 1
*/

Route::get('/', fn () => inertia('welcome'))->name('home');

/*
| Membership registration — five steps, validated one at a time.
|
| Step submissions are throttled generously because draft churn is legitimate;
| the final submit is throttled hard because that is what creates accounts.
*/
Route::prefix('join')->name('join.')->group(function (): void {
    Route::get('/', [RegistrationController::class, 'start'])->name('start');
    Route::get('done', [RegistrationController::class, 'done'])->name('done');

    Route::get('{step}', [RegistrationController::class, 'step'])->name('step');

    // The final step creates the account, so it gets the tighter limit.
    // Declared first so it matches before the generic {step} route; both
    // resolve to the same URL, /join/review.
    Route::post('review', [RegistrationController::class, 'store'])
        ->defaults('step', 'review')
        ->middleware('throttle:5,1')
        ->name('store.review');

    // Earlier steps only move a draft around, so churn is legitimate.
    Route::post('{step}', [RegistrationController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('store');
});

/*
| Events.
|
| An event whose date is still TBA is listed here — that is the point of the
| date rule. The Jubilee is announced and open for registration long before the
| committee fixes a day.
*/
Route::get('events', [EventController::class, 'index'])->name('events.index');
Route::get('events/{event:slug}', [EventController::class, 'show'])->name('events.show');

/*
| The Golden Jubilee microsite.
|
| Not a subsystem. Every page resolves the FLAGSHIP EVENT — one row in `events`
| with `is_flagship = true` — and renders it with a distinct treatment plus
| microsite-only copy from the `jubilee` settings group.
*/
Route::prefix('jubilee')->group(function (): void {
    Route::get('/', [JubileeController::class, 'index'])->name('jubilee');
    Route::get('schedule', [JubileeController::class, 'schedule'])->name('jubilee.schedule');
    Route::get('sponsors', [JubileeController::class, 'sponsors'])->name('jubilee.sponsors');
    Route::get('faq', [JubileeController::class, 'faq'])->name('jubilee.faq');
});

/*
| Giving.
|
| No online payment is taken. The association collects in cash, by transfer
| and through mobile financial services settled outside the platform, so these
| pages say how to give and the office records it. Pretending to take a card
| would be worse than honest instructions.
*/
Route::get('donate', [GivingController::class, 'donate'])->name('donate');
Route::get('sponsorship', [GivingController::class, 'sponsorship'])->name('sponsorship');

// Who runs the association. Serving members only.
Route::get('committees', CommitteeController::class)->name('committees.index');

/*
| Batches — AGGREGATE COUNTS ONLY.
|
| The directory is members-only. A public page listing real alumni names and
| batch years would hand a scraper exactly what the directory withholds.
*/
Route::get('batches', [BatchController::class, 'index'])->name('batches.index');
Route::get('batches/{batch:slug}', [BatchController::class, 'show'])->name('batches.show');

/*
| The QR target from a digital membership card.
|
| Returns six fields and nothing else, whatever the member's privacy settings
| say. Rate limited because scanning is bursty; ULIDs are unguessable so the
| limit is generous.
*/
Route::get('verify/member/{ulid}', MemberVerifyController::class)
    ->middleware('throttle:30,1')
    ->name('verify.member');
