<?php

declare(strict_types=1);

use App\Http\Controllers\Member\BatchController;
use App\Http\Controllers\Member\CardController;
use App\Http\Controllers\Member\DashboardController;
use App\Http\Controllers\Member\DirectoryController;
use App\Http\Controllers\Member\EventController;
use App\Http\Controllers\Member\PaymentController;
use App\Http\Controllers\Member\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Member routes
|--------------------------------------------------------------------------
|
| `auth` + `verified` for everything. The directory carries an additional
| `member.approved`, because it exposes other members' data and a pending
| application has not been checked by anyone yet.
|
| @see docs/03-routes.md section 2
*/

Route::middleware(['auth', 'verified'])->group(function (): void {

    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::prefix('my')->name('my.')->group(function (): void {
        Route::get('profile', [ProfileController::class, 'edit'])->name('profile');
        Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::patch('privacy', [ProfileController::class, 'updatePrivacy'])->name('privacy.update');
        Route::post('photo', [ProfileController::class, 'updatePhoto'])
            ->middleware('throttle:10,1')
            ->name('photo.update');

        /*
        | Events the member has registered for, and their passes.
        |
        | The pass is bound by ULID so tickets cannot be walked, and the
        | controller checks ownership on top of that.
        */
        /*
        | The member's own money. Scoped to their own records throughout.
        */
        Route::get('payments', [PaymentController::class, 'index'])->name('payments');
        Route::get('payments/{payment}/receipt', [PaymentController::class, 'receipt'])
            ->name('payments.receipt');
        Route::get('donations', [PaymentController::class, 'donations'])->name('donations');

        Route::get('events', [EventController::class, 'index'])->name('events');
        Route::get('events/{registration}', [EventController::class, 'show'])->name('events.ticket');
        Route::delete('events/{registration}', [EventController::class, 'destroy'])->name('events.cancel');
    });

    /*
    | Registering for an event.
    |
    | Throttled: this creates a row and, for a paid event, an amount owed.
    */
    Route::post('events/{event:slug}/register', [EventController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('events.register');

    /*
    | Approved members only.
    |
    | EnsureMemberApproved redirects to the profile with an explanation rather
    | than throwing a bare 403 — a member waiting on the committee is not an
    | intruder.
    */
    Route::middleware('member.approved')->group(function (): void {
        Route::get('directory', [DirectoryController::class, 'index'])->name('directory.index');

        // The member's own cohort — the directory narrowed to one batch, with
        // the extra `show_in_batch_list` opt-out honoured.
        Route::get('my/batch', BatchController::class)->name('my.batch');

        // The digital membership card. Proof of membership, so an
        // application still under review does not get one.
        Route::get('my/card', CardController::class)->name('my.card');

        // Bound by ULID so profiles cannot be walked by incrementing an id.
        Route::get('directory/{member:ulid}', [DirectoryController::class, 'show'])
            ->name('directory.show');
    });
});
