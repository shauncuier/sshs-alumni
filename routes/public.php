<?php

declare(strict_types=1);

use App\Http\Controllers\Public\BatchController;
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
