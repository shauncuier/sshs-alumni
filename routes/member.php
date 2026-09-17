<?php

declare(strict_types=1);

use App\Http\Controllers\Member\BatchController;
use App\Http\Controllers\Member\DashboardController;
use App\Http\Controllers\Member\DirectoryController;
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
    });

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

        // Bound by ULID so profiles cannot be walked by incrementing an id.
        Route::get('directory/{member:ulid}', [DirectoryController::class, 'show'])
            ->name('directory.show');
    });
});
