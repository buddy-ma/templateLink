<?php

use App\Http\Controllers\Drive\DriveController;
use App\Http\Controllers\Drive\DriveFileController;
use App\Http\Controllers\Drive\DriveFolderController;
use App\Http\Controllers\Drive\DriveQuotaController;
use App\Http\Controllers\Drive\DriveShareController;
use App\Http\Controllers\Drive\DriveShareLinkController;
use Illuminate\Support\Facades\Route;

Route::middleware(['throttle:30,1'])->prefix('drive/s')->name('drive.shared.')->group(function () {
    Route::get('/{token}', [DriveShareLinkController::class, 'show'])->name('show');
    Route::post('/{token}/unlock', [DriveShareLinkController::class, 'unlock'])->name('unlock');
    Route::get('/{token}/files/{file}', [DriveShareLinkController::class, 'download'])->name('download');
});

Route::middleware(['auth', 'verified', 'permission:drive.access'])
    ->prefix('drive')
    ->name('drive.')
    ->group(function () {
        Route::get('/', [DriveController::class, 'index'])->name('index');
        Route::get('/trash', [DriveController::class, 'trash'])->name('trash');

        Route::middleware('permission:drive.upload')->group(function () {
            Route::post('/folders', [DriveFolderController::class, 'store'])->name('folders.store');
            Route::post('/files', [DriveFileController::class, 'store'])->name('files.store');
        });

        Route::put('/folders/{folder}', [DriveFolderController::class, 'update'])->name('folders.update');
        Route::delete('/folders/{folder}', [DriveFolderController::class, 'destroy'])->name('folders.destroy');
        Route::post('/folders/{folder}/restore', [DriveFolderController::class, 'restore'])->name('folders.restore');
        Route::delete('/folders/{folder}/force', [DriveFolderController::class, 'forceDestroy'])->name('folders.force-destroy');

        Route::put('/files/{file}', [DriveFileController::class, 'update'])->name('files.update');
        Route::delete('/files/{file}', [DriveFileController::class, 'destroy'])->name('files.destroy');
        Route::post('/files/{file}/restore', [DriveFileController::class, 'restore'])->name('files.restore');
        Route::delete('/files/{file}/force', [DriveFileController::class, 'forceDestroy'])->name('files.force-destroy');
        Route::get('/files/{file}/download', [DriveFileController::class, 'download'])->name('files.download');

        Route::middleware('permission:drive.share')->group(function () {
            Route::post('/folders/{folder}/shares', [DriveShareController::class, 'storeFolder'])->name('folders.shares.store');
            Route::post('/files/{file}/shares', [DriveShareController::class, 'storeFile'])->name('files.shares.store');
            Route::delete('/shares/{share}', [DriveShareController::class, 'destroy'])->name('shares.destroy');

            Route::post('/folders/{folder}/links', [DriveShareLinkController::class, 'storeFolder'])->name('folders.links.store');
            Route::post('/files/{file}/links', [DriveShareLinkController::class, 'storeFile'])->name('files.links.store');
            Route::delete('/links/{shareLink}', [DriveShareLinkController::class, 'destroy'])->name('links.destroy');
        });

        Route::put('/quota', [DriveQuotaController::class, 'update'])
            ->middleware('permission:drive.manage_quota')
            ->name('quota.update');
    });
