<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Admin\RoleController;
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
| @see docs/03-routes.md section 3
*/

Route::middleware(['auth', 'verified'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {

        Route::get('/', DashboardController::class)
            ->middleware('can:admin.access')
            ->name('dashboard');

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
