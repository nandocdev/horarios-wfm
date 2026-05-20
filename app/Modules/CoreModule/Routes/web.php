<?php

declare(strict_types=1);

use App\Modules\CoreModule\Livewire\Roles\ListRoles;
use App\Modules\CoreModule\Livewire\SystemMaintenance;
use App\Modules\CoreModule\Livewire\Users\CreateUser;
use App\Modules\CoreModule\Livewire\Users\EditUser;
use App\Modules\CoreModule\Livewire\Users\ListUsers;
use Illuminate\Support\Facades\Route;

/**
 * Rutas administrativas del CoreModule (Gestión de Identidades).
 * Protegidas por autenticación y validación de permisos institucionales.
 */
Route::middleware(['auth', 'verified'])->group(function () {

    // Gestión de Usuarios
    Route::prefix('admin/users')->group(function () {
        Route::get('/', ListUsers::class)->name('users.index')->can('users.view');
        Route::get('/create', CreateUser::class)->name('users.create')->can('users.create');
        Route::get('/{user}/edit', EditUser::class)->name('users.edit')->can('users.edit');
    });

    // Gestión de Roles
    Route::get('admin/roles', ListRoles::class)->name('roles.index')->can('roles.view');

    // Configuración del Sistema
    Route::get('admin/system/maintenance', SystemMaintenance::class)
        ->name('admin.system.maintenance')
        ->can('admin.system');
});
