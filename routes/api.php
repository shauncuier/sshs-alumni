<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BatchController;
use App\Http\Controllers\Api\V1\CheckinController;
use App\Http\Controllers\Api\V1\DirectoryController;
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\VerificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 Routes
|--------------------------------------------------------------------------
|
| Registered with prefix `api/v1` via bootstrap/app.php.
| Rate limited per token / IP according to docs/03-routes.md section 5.
|
| @see docs/10-api.md section 3
*/

Route::middleware('throttle:60,1')->group(function (): void {

    /*
    | Public Endpoints
    */
    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('api.auth.login');

    Route::get('verify/member/{ulid}', [VerificationController::class, 'member'])
        ->middleware('throttle:30,1')
        ->name('api.verify.member');

    Route::get('batches', [BatchController::class, 'index'])->name('api.batches.index');
    Route::get('batches/{batch:slug}', [BatchController::class, 'show'])->name('api.batches.show');

    Route::get('events', [EventController::class, 'index'])->name('api.events.index');
    Route::get('events/{event:slug}', [EventController::class, 'show'])->name('api.events.show');

    /*
    | Authenticated Endpoints (Sanctum Tokens)
    */
    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('api.auth.logout');
        Route::get('auth/me', [AuthController::class, 'me'])->name('api.auth.me');

        // Member Self-Service
        Route::prefix('me')->name('api.me.')->group(function (): void {
            Route::get('profile', [MeController::class, 'profile'])->name('profile');
            Route::patch('profile', [MeController::class, 'updateProfile'])->name('profile.update');
            Route::get('card', [MeController::class, 'card'])->name('card');
            Route::get('events', [MeController::class, 'events'])->name('events');
            Route::get('notifications', [MeController::class, 'notifications'])->name('notifications');
        });

        Route::post('events/{event:slug}/register', [EventController::class, 'register'])
            ->middleware('throttle:10,1')
            ->name('api.events.register');

        // Alumni Directory — Approved members or Super Admin only
        Route::middleware('member.approved')->group(function (): void {
            Route::get('directory', [DirectoryController::class, 'index'])->name('api.directory.index');
            Route::get('directory/{member:ulid}', [DirectoryController::class, 'show'])->name('api.directory.show');
        });

        // Event Gate Scanner — Volunteer checkpoint (120 scans/minute)
        Route::middleware('can:events.checkin')->group(function (): void {
            Route::get('events/{event:slug}/checkin/stats', [CheckinController::class, 'stats'])
                ->name('api.checkin.stats');

            Route::get('events/{event:slug}/scan/{ulid}', [CheckinController::class, 'scan'])
                ->name('api.checkin.scan');

            Route::post('events/{event:slug}/checkin', [CheckinController::class, 'store'])
                ->middleware('throttle:120,1')
                ->name('api.checkin.store');
        });
    });
});
