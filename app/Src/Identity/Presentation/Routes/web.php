<?php

declare(strict_types=1);

use App\Src\Identity\Presentation\Livewire\ManageUsers;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified'])->prefix('admin')->group(function () {
    Route::get('/users', ManageUsers::class)->name('identity.users.index')->can('users.view');
    Route::get('/users/create', ManageUsers::class)->name('identity.users.create')->can('users.create');
    Route::get('/users/{user}/edit', ManageUsers::class)->name('identity.users.edit')->can('users.edit');
});
