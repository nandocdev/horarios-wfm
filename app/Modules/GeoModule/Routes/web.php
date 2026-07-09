<?php

declare(strict_types=1);

use App\Modules\GeoModule\Http\Controllers\LocationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::group(['prefix' => 'location', 'as' => 'location.'], function () {
        Route::get('/', [LocationController::class, 'index'])->name('index');
        Route::get('/provinces', [LocationController::class, 'provinces'])->name('provinces');
        Route::get('/districts/{province}', [LocationController::class, 'districts'])->name('districts');
        Route::get('/townships/{district}', [LocationController::class, 'townships'])->name('townships');
    });
});
