<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Location\Presentation\Http\Controllers\LocationController;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::group(['prefix' => 'location', 'as' => 'location.'], function (): void {
        Route::get('/', [LocationController::class, 'index'])->name('index');
        Route::get('/provinces', [LocationController::class, 'provinces'])->name('provinces');
        Route::get('/districts/{province}', [LocationController::class, 'districts'])->name('districts');
        Route::get('/townships/{district}', [LocationController::class, 'townships'])->name('townships');
    });
});
