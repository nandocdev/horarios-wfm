<?php

declare(strict_types=1);

use App\Modules\ReportingModule\Livewire\ReportGenerator;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('/reportes', ReportGenerator::class)->name('reports.index');
    Route::get('/reportes/{category}/{subReport}', ReportGenerator::class)->name('reports.show');
});
