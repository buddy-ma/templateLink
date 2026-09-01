<?php

use App\Http\Controllers\Demand\BrandController;
use App\Http\Controllers\Demand\DemandController;
use App\Http\Controllers\Demand\DemandValidationController;
use App\Http\Controllers\Demand\MaterialNatureController;
use App\Http\Controllers\Demand\UserManagerController;
use App\Http\Controllers\Demand\ValidationPipelineController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'permission:demands.access'])
    ->prefix('demands')
    ->name('demands.')
    ->group(function () {
        Route::get('/', [DemandController::class, 'index'])->name('index');
        Route::get('/create', [DemandController::class, 'create'])
            ->middleware('permission:demands.create')
            ->name('create');
        Route::post('/', [DemandController::class, 'store'])
            ->middleware('permission:demands.create')
            ->name('store');

        Route::get('/material-natures/search', [MaterialNatureController::class, 'search'])
            ->name('material-natures.search');
        Route::post('/material-natures', [MaterialNatureController::class, 'store'])
            ->name('material-natures.store');

        Route::middleware('permission:demands.manage_catalog')->group(function () {
            Route::get('/brands', [BrandController::class, 'index'])->name('brands.index');
            Route::post('/brands', [BrandController::class, 'store'])->name('brands.store');
            Route::put('/brands/{brand}', [BrandController::class, 'update'])->name('brands.update');
            Route::delete('/brands/{brand}', [BrandController::class, 'destroy'])->name('brands.destroy');

            Route::get('/material-natures', [MaterialNatureController::class, 'index'])->name('material-natures.index');
            Route::delete('/material-natures/{materialNature}', [MaterialNatureController::class, 'destroy'])
                ->name('material-natures.destroy');
        });

        Route::middleware('permission:demands.manage_pipeline')->group(function () {
            Route::get('/pipeline', [ValidationPipelineController::class, 'index'])->name('pipeline.index');
            Route::put('/pipeline/{pipeline}', [ValidationPipelineController::class, 'update'])->name('pipeline.update');
        });

        Route::middleware('permission:demands.view_all')->group(function () {
            Route::get('/team', [UserManagerController::class, 'index'])->name('team.index');
            Route::put('/team/{user}', [UserManagerController::class, 'update'])->name('team.update');
        });

        Route::get('/{demand}', [DemandController::class, 'show'])->name('show');
        Route::get('/{demand}/edit', [DemandController::class, 'edit'])
            ->middleware('permission:demands.create')
            ->name('edit');
        Route::post('/{demand}', [DemandController::class, 'update'])
            ->middleware('permission:demands.create')
            ->name('update');
        Route::get('/{demand}/attachments/{attachment}', [DemandController::class, 'download'])
            ->name('attachments.download');

        Route::post('/{demand}/approve', [DemandValidationController::class, 'approve'])->name('approve');
        Route::post('/{demand}/refuse', [DemandValidationController::class, 'refuse'])->name('refuse');
        Route::post('/{demand}/business-approve', [DemandValidationController::class, 'businessApprove'])
            ->name('business-approve');
        Route::post('/{demand}/close', [DemandValidationController::class, 'close'])->name('close');
    });
