<?php

declare(strict_types=1);

use App\Src\Organization\Presentation\Http\OrganizationBridgeController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/organization')->group(function () {
    Route::get('structure', [OrganizationBridgeController::class, 'getFlatStructure']);
    Route::get('directorates', [OrganizationBridgeController::class, 'getDirectorates']);
    Route::get('departments/{directorateId}', [OrganizationBridgeController::class, 'getDepartments']);
    Route::get('teams', [OrganizationBridgeController::class, 'getTeams']);
});

Route::prefix('admin/organization')->group(function () {
    Route::get('/teams', \App\Src\Organization\Presentation\Livewire\ManageTeams::class)->name('org.teams.index');
});
