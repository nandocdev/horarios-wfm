<?php

declare(strict_types=1);

use App\Modules\OrganizationModule\Livewire\CreateDepartment;
use App\Modules\OrganizationModule\Livewire\CreateDirectorate;
use App\Modules\OrganizationModule\Livewire\CreatePosition;
use App\Modules\OrganizationModule\Livewire\EditDepartment;
use App\Modules\OrganizationModule\Livewire\EditDirectorate;
use App\Modules\OrganizationModule\Livewire\EditPosition;
use App\Modules\OrganizationModule\Livewire\ListDepartments;
use App\Modules\OrganizationModule\Livewire\ListDirectorates;
use App\Modules\OrganizationModule\Livewire\ListPositions;
use App\Modules\OrganizationModule\Livewire\ShowDepartment;
use App\Modules\OrganizationModule\Livewire\ShowDirectorate;
use App\Modules\OrganizationModule\Livewire\ShowPosition;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'organization', 'as' => 'organization.'], function () {
    Route::get('directorates', ListDirectorates::class)->name('directorates.index');
    Route::get('directorates/create', CreateDirectorate::class)->name('directorates.create');
    Route::get('directorates/{directorate}', ShowDirectorate::class)->name('directorates.show');
    Route::get('directorates/{directorate}/edit', EditDirectorate::class)->name('directorates.edit');

    Route::get('departments', ListDepartments::class)->name('departments.index');
    Route::get('departments/create', CreateDepartment::class)->name('departments.create');
    Route::get('departments/{department}', ShowDepartment::class)->name('departments.show');
    Route::get('departments/{department}/edit', EditDepartment::class)->name('departments.edit');

    Route::get('positions', ListPositions::class)->name('positions.index');
    Route::get('positions/create', CreatePosition::class)->name('positions.create');
    Route::get('positions/{position}', ShowPosition::class)->name('positions.show');
    Route::get('positions/{position}/edit', EditPosition::class)->name('positions.edit');
});
