<?php

declare(strict_types=1);

use App\Src\Wfm\Presentation\Livewire\MyDay;
use App\Src\Wfm\Presentation\Livewire\WeeklyPlanning;
use Illuminate\Support\Facades\Route;

Route::prefix('wfm-src')->middleware(['auth'])->group(function () {
    Route::get('/my-day', MyDay::class)->name('wfm-src.my-day');
    Route::get('/planning', WeeklyPlanning::class)->name('wfm-src.planning');
});
