<?php

declare(strict_types=1);

use App\Http\Controllers\Member\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Member routes
|--------------------------------------------------------------------------
|
| `auth` + `verified` for everything. The directory and community carry an
| additional `member.approved`, because those expose other members' data and a
| pending application has not been checked by anyone yet.
|
| @see docs/03-routes.md section 2
*/

Route::middleware(['auth', 'verified'])->group(function (): void {

    Route::get('dashboard', DashboardController::class)->name('dashboard');

    /*
    | Approved members only.
    |
    | EnsureMemberApproved redirects to the profile with an explanation rather
    | than throwing a bare 403 — a member waiting on the committee is not an
    | intruder.
    */
    Route::middleware('member.approved')->group(function (): void {
        // Directory and community land here in Phase 2 and Phase 6.
    });
});
