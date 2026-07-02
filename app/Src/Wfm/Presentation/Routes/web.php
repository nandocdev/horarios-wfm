<?php

declare(strict_types=1);

use App\Src\Wfm\Presentation\Livewire\WeeklyPlanning;
use Illuminate\Support\Facades\Route;

Route::prefix('wfm')->group(function () {
    Route::get('/planning', WeeklyPlanning::class)->name('wfm.planning');
});
