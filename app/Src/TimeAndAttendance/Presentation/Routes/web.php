<?php

declare(strict_types=1);

use App\Src\TimeAndAttendance\Presentation\Livewire\ListIncidents;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('admin/attendance')->group(function () {
    Route::get('/incidents', ListIncidents::class)->name('ta.incidents.index');
});
