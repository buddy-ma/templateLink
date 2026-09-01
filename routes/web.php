<?php

use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->post('impersonate/stop', [ImpersonationController::class, 'stop'])
    ->name('impersonate.stop');

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

// Locale switching
Route::post('locale/{locale}', [LocaleController::class, 'switch'])
    ->name('locale.switch')
    ->middleware('throttle:30,1');

// Zoho OAuth (custom Socialite provider — see ZohoServiceProvider)
Route::middleware('guest')->group(function () {
    Route::get('auth/zoho', [SocialiteController::class, 'redirect'])->name('auth.zoho');
    Route::get('auth/zoho/callback', [SocialiteController::class, 'callback'])->name('auth.zoho.callback');
});

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
require __DIR__.'/demands.php';
require __DIR__.'/drive.php';
require __DIR__.'/notifications.php';
