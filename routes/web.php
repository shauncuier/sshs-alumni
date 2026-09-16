<?php

use App\Http\Controllers\Public\LocaleController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

// Language switch. The locale is validated against the allow-list inside the
// controller rather than trusted from the URL.
Route::get('locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
