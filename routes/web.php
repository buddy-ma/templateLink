<?php

use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::middleware(['auth'])->post('impersonate/stop', [ImpersonationController::class, 'stop'])
    ->name('impersonate.stop');

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

// Locale switching
Route::post('locale/{locale}', [LocaleController::class, 'switch'])
    ->name('locale.switch')
    ->middleware('throttle:30,1');

// Zoho OAuth
Route::get('auth/zoho', [SocialiteController::class, 'redirect'])->name('auth.zoho');
Route::get('auth/zoho/callback', [SocialiteController::class, 'callback'])->name('auth.zoho.callback');

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
