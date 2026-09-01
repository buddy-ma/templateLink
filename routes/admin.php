<?php

use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\TranslationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified', 'permission:impersonate_users'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('users', [ImpersonationController::class, 'index'])->name('users.index');
        Route::post('users/{user}/impersonate', [ImpersonationController::class, 'start'])
            ->name('users.impersonate');
    });

Route::middleware(['auth', 'verified', 'permission:access_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::redirect('/', '/admin/translations');

    Route::get('translations', [TranslationController::class, 'index'])->name('translations.index');
    Route::put('translations/{locale}', [TranslationController::class, 'update'])
        ->name('translations.update')
        ->where('locale', '[a-z]{2}');

    Route::get('design-guide', fn () => Inertia::render('admin/DesignGuide'))->name('design-guide');

    Route::middleware('permission:manage_roles')->group(function () {
        Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
        Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
        Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');

        Route::post('permissions', [PermissionController::class, 'store'])->name('permissions.store');
        Route::put('permissions/{permission}', [PermissionController::class, 'update'])->name('permissions.update');
        Route::delete('permissions/{permission}', [PermissionController::class, 'destroy'])->name('permissions.destroy');
    });
});
