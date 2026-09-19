<?php

declare(strict_types=1);

use App\Http\Controllers\Member\BatchController;
use App\Http\Controllers\Member\CardController;
use App\Http\Controllers\Member\CommentController;
use App\Http\Controllers\Member\CommunityController;
use App\Http\Controllers\Member\ContentReportController;
use App\Http\Controllers\Member\DashboardController;
use App\Http\Controllers\Member\DirectoryController;
use App\Http\Controllers\Member\EventController;
use App\Http\Controllers\Member\NotificationController;
use App\Http\Controllers\Member\PaymentController;
use App\Http\Controllers\Member\ProfileController;
use App\Http\Controllers\Member\ReactionController;
use App\Http\Controllers\Member\StoryController;
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

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/{id}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');

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

        /*
        | The member's own alumni story.
        |
        | Submitted as pending and read by somebody before it appears. The
        | member sees it whatever state it is in, including rejected: a
        | submission that silently never appears is how people conclude they
        | were ignored.
        */
        Route::get('stories', [StoryController::class, 'index'])->name('stories');
        Route::post('stories', [StoryController::class, 'store'])
            ->middleware('throttle:5,1')
            ->name('stories.store');
        Route::put('stories/{story}', [StoryController::class, 'update'])->name('stories.update');
        Route::delete('stories/{story}', [StoryController::class, 'destroy'])->name('stories.destroy');

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

        /*
        | The community.
        |
        | ORDER MATTERS. The comment routes are declared BEFORE the
        | `community/{post:ulid}` routes, because `DELETE community/comments/3`
        | would otherwise be matched by the post route and fail on a ULID that
        | is the word "comments".
        |
        | Writes are throttled. A feed is the one place in the application
        | where a script can produce unbounded rows, and the limits are set
        | where a real person will never meet them.
        */
        Route::prefix('community')->name('community.')->group(function (): void {
            Route::delete('comments/{comment}', [CommentController::class, 'destroy'])
                ->name('comments.destroy');
            Route::post('comments/{comment}/reactions', [ReactionController::class, 'comment'])
                ->middleware('throttle:60,1')
                ->name('comments.reactions');
            Route::post('comments/{comment}/reports', [ContentReportController::class, 'comment'])
                ->middleware('throttle:10,1')
                ->name('comments.reports');

            Route::post('{post:ulid}/comments', [CommentController::class, 'store'])
                ->middleware('throttle:30,1')
                ->name('comments.store');
            Route::post('{post:ulid}/reactions', [ReactionController::class, 'post'])
                ->middleware('throttle:60,1')
                ->name('reactions');
            Route::post('{post:ulid}/reports', [ContentReportController::class, 'post'])
                ->middleware('throttle:10,1')
                ->name('reports');

            Route::put('{post:ulid}', [CommunityController::class, 'update'])->name('update');
            Route::delete('{post:ulid}', [CommunityController::class, 'destroy'])->name('destroy');
            Route::get('{post:ulid}', [CommunityController::class, 'show'])->name('show');
        });

        Route::get('community', [CommunityController::class, 'index'])->name('community.index');
        Route::post('community', [CommunityController::class, 'store'])
            ->middleware('throttle:20,1')
            ->name('community.store');
    });
});
