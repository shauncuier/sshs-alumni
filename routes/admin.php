<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\RoleController;
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
