<?php

declare(strict_types=1);

use App\Src\HumanResources\Presentation\Livewire\ManageMedicalRecords;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('admin/hr')->group(function () {
    Route::get('/employees/{employee}/medical', ManageMedicalRecords::class)
        ->name('hr.employees.medical')
        ->can('employees.view');
});
