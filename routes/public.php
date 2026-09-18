<?php

declare(strict_types=1);

use App\Http\Controllers\Public\BatchController;
use App\Http\Controllers\Public\CommitteeController;
use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\ContentController;
use App\Http\Controllers\Public\EventController;
use App\Http\Controllers\Public\GivingController;
use App\Http\Controllers\Public\HomeController;
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

Route::get('/', HomeController::class)->name('home');

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

/*
| Content.
|
| Everything here is PUBLISHED-ONLY, and a draft is a 404 rather than a 403 —
| "forbidden" tells a stranger that an article they cannot read is being
| written, which matters when the draft is an announcement about a death or a
| committee decision not yet taken.
*/
Route::get('about', [ContentController::class, 'about'])->name('about');
Route::get('about/school', [ContentController::class, 'school'])->name('about.school');

Route::get('news', [ContentController::class, 'news'])->name('news.index');
Route::get('news/{news:slug}', [ContentController::class, 'newsShow'])->name('news.show');

// Announcements narrow themselves to the reader: a stranger sees the public
// ones, a signed-in member also sees members' and their own batch's.
Route::get('announcements', [ContentController::class, 'announcements'])->name('announcements.index');

Route::get('gallery', [ContentController::class, 'gallery'])->name('gallery.index');
Route::get('gallery/{album:slug}', [ContentController::class, 'galleryShow'])->name('gallery.show');

Route::get('stories', [ContentController::class, 'stories'])->name('stories.index');
Route::get('stories/{story:slug}', [ContentController::class, 'storyShow'])->name('stories.show');

/*
| Standing pages — privacy policy, terms, and whatever else needs a URL.
|
| Prefixed with `/p/` so a page slug can never collide with a route: a page
| called "events" would otherwise shadow the events listing, and the person
| who named it would have no way of knowing why the site broke.
*/
Route::get('p/{page:slug}', [ContentController::class, 'page'])->name('pages.show');

/*
| Contact.
|
| A message becomes a CRM contact and an activity on their timeline, not a row
| in a messages table nobody opens. Throttled because a public form with no
| limit is a spam target within a week of launch.
*/
Route::get('contact', [ContactController::class, 'show'])->name('contact');
Route::post('contact', [ContactController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('contact.store');
