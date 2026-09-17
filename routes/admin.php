<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\BatchController;
use App\Http\Controllers\Admin\CheckinController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\TicketTypeController;
use App\Http\Controllers\Admin\VerificationController;
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
