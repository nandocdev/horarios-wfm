<?php

declare(strict_types=1);

use App\Modules\DirectoryModule\Livewire\ManageDirectoryUnits;
use App\Modules\DirectoryModule\Livewire\UpsertDirectoryUnit;
use Illuminate\Support\Facades\Route;

// Gestión administrativa del directorio de unidades operativas
Route::middleware(['auth', 'permission:directory.manage'])->group(function () {
    Route::get('/admin/directory', ManageDirectoryUnits::class)->name('directory.index');
    Route::get('/admin/directory/create', UpsertDirectoryUnit::class)->name('directory.create');
    Route::get('/admin/directory/{id}/edit', UpsertDirectoryUnit::class)->name('directory.edit');
});
