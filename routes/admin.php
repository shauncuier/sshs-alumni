<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\BatchController;
use App\Http\Controllers\Admin\CheckinController;
use App\Http\Controllers\Admin\CommitteeController;
use App\Http\Controllers\Admin\CommunityController;
use App\Http\Controllers\Admin\CrmActivityController;
use App\Http\Controllers\Admin\CrmContactController;
use App\Http\Controllers\Admin\CrmPipelineController;
use App\Http\Controllers\Admin\CrmTagController;
use App\Http\Controllers\Admin\CrmTaskController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DonationController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Admin\MembershipFeeController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SponsorController;
use App\Http\Controllers\Admin\TicketTypeController;
use App\Http\Controllers\Admin\VerificationController;
use App\Http\Controllers\Admin\VolunteerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin routes
|--------------------------------------------------------------------------
|
| EVERY route in this file carries a `can:` middleware. That is not a
| convention — a test walks this route list and fails if any admin route is
| reachable without a permission, so adding one without protecting it breaks
| the suite.
|
| `admin.access` guards the whole group on top of the per-module permission.
| Both are needed: an ordinary Member holds `batches.view` so they can read the
| PUBLIC batch pages, and without the group gate that permission would also let
| them open the admin panel.
|
| @see docs/03-routes.md section 3
*/

Route::middleware(['auth', 'verified', 'can:admin.access'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {

        Route::get('/', DashboardController::class)->name('dashboard');

        /*
        | Members & verification
        */
        Route::middleware('can:members.view')->group(function (): void {
            Route::get('members', [MemberController::class, 'index'])->name('members.index');
            Route::get('members/{member}', [MemberController::class, 'show'])->name('members.show');
        });

        Route::middleware('can:members.verify')->group(function (): void {
            Route::post('members/{member}/transition', [VerificationController::class, 'transition'])
                ->name('members.transition');

            Route::post('members/{member}/request-correction', [VerificationController::class, 'requestCorrection'])
                ->name('members.correction');

            Route::post('members/{member}/membership-number', [VerificationController::class, 'assignNumber'])
                ->name('members.number');
        });

        /*
        | Batches & coordinators
        |
        | `batches.edit` is the gate; BatchPolicy is the reach. A Batch
        | Coordinator holds the permission but may only edit the batches they
        | actually coordinate.
        */
        Route::middleware('can:batches.view')->group(function (): void {
            Route::get('batches', [BatchController::class, 'index'])->name('batches.index');
            Route::get('batches/{batch}', [BatchController::class, 'show'])->name('batches.show');
        });

        Route::middleware('can:batches.create')->group(function (): void {
            Route::post('batches', [BatchController::class, 'store'])->name('batches.store');
        });

        Route::middleware('can:batches.edit')->group(function (): void {
            Route::put('batches/{batch}', [BatchController::class, 'update'])->name('batches.update');

            Route::post('batches/{batch}/coordinators', [BatchController::class, 'addCoordinator'])
                ->name('batches.coordinators.store');

            Route::delete('batches/{batch}/coordinators/{member}', [BatchController::class, 'removeCoordinator'])
                ->name('batches.coordinators.destroy');
        });

        /*
        | Events
        |
        | `events.publish` is separate from `events.edit` because publishing an
        | event and announcing its date are what make them public. `events.checkin`
        | is separate again: a volunteer runs the gate on the day without being
        | able to change the event.
        */
        Route::middleware('can:events.view')->group(function (): void {
            Route::get('events', [EventController::class, 'index'])->name('events.index');
            Route::get('events/{event}', [EventController::class, 'show'])->name('events.show');
            Route::get('events/{event}/registrations', [EventController::class, 'registrations'])
                ->name('events.registrations');
        });

        Route::middleware('can:events.create')->group(function (): void {
            Route::post('events', [EventController::class, 'store'])->name('events.store');
        });

        Route::middleware('can:events.edit')->group(function (): void {
            Route::put('events/{event}', [EventController::class, 'update'])->name('events.update');

            // Ticket types. `sold_count` is the registrar's, never a form's.
            Route::post('events/{event}/tickets', [TicketTypeController::class, 'store'])
                ->name('events.tickets.store');
            Route::put('events/{event}/tickets/{ticketType}', [TicketTypeController::class, 'update'])
                ->name('events.tickets.update');
            Route::delete('events/{event}/tickets/{ticketType}', [TicketTypeController::class, 'destroy'])
                ->name('events.tickets.destroy');

            // A walk-in: someone who turns up having never registered.
            Route::post('events/{event}/registrations', [EventController::class, 'storeRegistration'])
                ->name('events.registrations.store');

            Route::post('events/{event}/registrations/{registration}/promote', [EventController::class, 'promote'])
                ->name('events.promote');
        });

        Route::middleware('can:events.publish')->group(function (): void {
            Route::post('events/{event}/status', [EventController::class, 'transition'])
                ->name('events.transition');

            // THE DATE RULE: the only place `date_status` moves.
            Route::post('events/{event}/date', [EventController::class, 'announceDate'])
                ->name('events.date');
        });

        /*
        | The gate.
        |
        | Rate limited generously because a queue is bursty, and scanning is
        | a GET that shows the operator who is in front of them — admitting
        | someone is the separate POST below.
        */
        Route::middleware(['can:events.checkin', 'throttle:120,1'])->group(function (): void {
            Route::get('events/{event}/checkin', [CheckinController::class, 'index'])
                ->name('events.checkin');

            Route::get('events/{event}/checkin/{ulid}', [CheckinController::class, 'scan'])
                ->name('events.checkin.scan');

            Route::post('events/{event}/checkin/{registration}', [CheckinController::class, 'store'])
                ->name('events.checkin.store');
        });

        /*
        | CRM
        |
        | `crm.view` reads, `crm.manage` writes, `crm.assign` takes a contact
        | off a colleague's list. Reading is deliberately NOT owner-scoped: a
        | contact only one person can see is a contact only one person follows
        | up.
        */
        Route::prefix('crm')->name('crm.')->group(function (): void {
            Route::middleware('can:crm.view')->group(function (): void {
                Route::get('contacts', [CrmContactController::class, 'index'])->name('contacts.index');
                Route::get('contacts/{contact}', [CrmContactController::class, 'show'])->name('contacts.show');
                Route::get('pipeline', CrmPipelineController::class)->name('pipeline');
                Route::get('tasks', [CrmTaskController::class, 'index'])->name('tasks.index');
                Route::get('tags', [CrmTagController::class, 'index'])->name('tags.index');
            });

            Route::middleware('can:crm.manage')->group(function (): void {
                Route::post('contacts', [CrmContactController::class, 'store'])->name('contacts.store');
                Route::put('contacts/{contact}', [CrmContactController::class, 'update'])->name('contacts.update');

                // The stage moves through PipelineService so the change is
                // recorded on the timeline, never as a bare column update.
                Route::post('contacts/{contact}/stage', [CrmContactController::class, 'move'])->name('contacts.move');

                Route::post('contacts/{contact}/link', [CrmContactController::class, 'link'])->name('contacts.link');
                Route::delete('contacts/{contact}/link', [CrmContactController::class, 'unlink'])->name('contacts.unlink');

                // Activities. `system` is never accepted from a request.
                Route::post('contacts/{contact}/activities', [CrmActivityController::class, 'storeForContact'])
                    ->name('contacts.activities.store');

                Route::post('tags', [CrmTagController::class, 'store'])->name('tags.store');
                Route::put('tags/{tag}', [CrmTagController::class, 'update'])->name('tags.update');
                Route::delete('tags/{tag}', [CrmTagController::class, 'destroy'])->name('tags.destroy');
                Route::post('contacts/{contact}/tags', [CrmTagController::class, 'toggle'])->name('contacts.tags.toggle');

                Route::post('tasks', [CrmTaskController::class, 'store'])->name('tasks.store');
            });

            // The assignee works their own task without holding crm.manage;
            // CrmTaskPolicy is what narrows it.
            Route::middleware('can:crm.view')->group(function (): void {
                Route::put('tasks/{task}', [CrmTaskController::class, 'update'])->name('tasks.update');
                Route::delete('tasks/{task}', [CrmTaskController::class, 'destroy'])->name('tasks.destroy');
            });

            Route::middleware('can:crm.assign')->group(function (): void {
                Route::post('contacts/{contact}/owner', [CrmContactController::class, 'assign'])->name('contacts.assign');
            });

            Route::middleware('can:crm.delete')->group(function (): void {
                Route::delete('contacts/{contact}', [CrmContactController::class, 'destroy'])->name('contacts.destroy');
            });
        });

        // A member gets called and emailed like anyone else, so the timeline
        // is written against them directly rather than through a shadow
        // contact row.
        Route::middleware('can:crm.manage')->group(function (): void {
            Route::post('members/{member}/activities', [CrmActivityController::class, 'storeForMember'])
                ->name('members.activities.store');
        });

        /*
        | Money
        |
        | One ledger. Fees, registrations, donations and sponsorships all
        | resolve through `payments.payable`, so no two reports can disagree
        | about income.
        |
        | There is no route to EDIT or DELETE a payment, deliberately: a
        | mistake is corrected by refunding and re-recording, and both are
        | audited. See docs/09-payments.md section 4.
        */
        Route::middleware('can:payments.view')->group(function (): void {
            Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
            Route::get('payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');
            Route::get('fees', [MembershipFeeController::class, 'index'])->name('fees.index');
        });

        Route::middleware('can:payments.create')->group(function (): void {
            Route::post('payments', [PaymentController::class, 'store'])->name('payments.store');
            Route::post('fees/generate', [MembershipFeeController::class, 'generate'])->name('fees.generate');
            Route::post('fees/{fee}/pay', [MembershipFeeController::class, 'pay'])->name('fees.pay');
            // Waiving is NOT paying: no ledger row, no receipt.
            Route::post('fees/{fee}/waive', [MembershipFeeController::class, 'waive'])->name('fees.waive');
        });

        Route::middleware('can:payments.refund')->group(function (): void {
            Route::post('payments/{payment}/refund', [PaymentController::class, 'refund'])->name('payments.refund');
        });

        /*
        | Donations
        */
        Route::middleware('can:donations.view')->group(function (): void {
            Route::get('donations', [DonationController::class, 'index'])->name('donations.index');
        });

        Route::middleware('can:donations.manage')->group(function (): void {
            Route::post('donations', [DonationController::class, 'store'])->name('donations.store');
            Route::post('donations/{donation}/receive', [DonationController::class, 'receive'])
                ->name('donations.receive');
        });

        /*
        | Sponsors
        */
        Route::middleware('can:sponsors.view')->group(function (): void {
            Route::get('sponsors', [SponsorController::class, 'index'])->name('sponsors.index');
        });

        Route::middleware('can:sponsors.manage')->group(function (): void {
            Route::post('sponsors', [SponsorController::class, 'store'])->name('sponsors.store');
            Route::put('sponsors/{sponsor}', [SponsorController::class, 'update'])->name('sponsors.update');
            Route::post('sponsors/{sponsor}/invoice', [SponsorController::class, 'invoice'])->name('sponsors.invoice');
            Route::post('sponsors/{sponsor}/payment', [SponsorController::class, 'record'])->name('sponsors.record');
        });

        /*
        | Volunteers
        */
        Route::middleware('can:volunteers.view')->group(function (): void {
            Route::get('volunteers', [VolunteerController::class, 'index'])->name('volunteers.index');
        });

        Route::middleware('can:volunteers.manage')->group(function (): void {
            Route::post('volunteers', [VolunteerController::class, 'store'])->name('volunteers.store');
            Route::put('volunteers/{volunteer}', [VolunteerController::class, 'update'])->name('volunteers.update');
            Route::post('volunteers/{volunteer}/assignments', [VolunteerController::class, 'assign'])
                ->name('volunteers.assign');
            Route::put('assignments/{assignment}', [VolunteerController::class, 'updateAssignment'])
                ->name('volunteers.assignments.update');
        });

        /*
        | Committees
        */
        Route::middleware('can:committees.view')->group(function (): void {
            Route::get('committees', [CommitteeController::class, 'index'])->name('committees.index');
        });

        Route::middleware('can:committees.manage')->group(function (): void {
            Route::post('committees', [CommitteeController::class, 'store'])->name('committees.store');
            Route::put('committees/{committee}', [CommitteeController::class, 'update'])->name('committees.update');
            Route::post('committees/{committee}/members', [CommitteeController::class, 'addMember'])
                ->name('committees.members.store');
            // Standing somebody down keeps the row: a committee's history is
            // part of the association's record.
            Route::delete('committees/{committee}/members/{committeeMember}', [CommitteeController::class, 'removeMember'])
                ->name('committees.members.destroy');
        });

        /*
        | Community moderation
        |
        | Two screens on purpose: the posts list is everything, for a moderator
        | who has been told about something; the reports queue is what members
        | flagged, which is the actual work.
        */
        Route::middleware('can:community.moderate')->prefix('community')->name('community.')->group(function (): void {
            Route::get('posts', [CommunityController::class, 'posts'])->name('posts');
            Route::get('reports', [CommunityController::class, 'reports'])->name('reports');
            Route::put('posts/{post:ulid}', [CommunityController::class, 'updatePost'])->name('posts.update');
            Route::put('comments/{comment}', [CommunityController::class, 'updateComment'])->name('comments.update');
            Route::put('reports/{report}', [CommunityController::class, 'resolveReport'])->name('reports.resolve');
        });

        /*
        | Roles & permissions
        |
        | Exists so the committee can re-cut roles without a deployment, which
        | is why application code checks permissions rather than role names.
        */
        Route::middleware('can:roles.manage')->group(function (): void {
            Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
            Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        });
    });
