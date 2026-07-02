<?php

declare(strict_types=1);

use App\Src\Identity\Presentation\Http\Controllers\AuthController;
use App\Src\Identity\Presentation\Livewire\CreateUserForm;
use App\Src\Identity\Presentation\Livewire\EditUserForm;
use App\Src\Identity\Presentation\Livewire\ListRoles;
use App\Src\Identity\Presentation\Livewire\ListUsers;
use App\Src\Identity\Presentation\Livewire\SettingsAppearance;
use App\Src\Identity\Presentation\Livewire\SettingsProfile;
use App\Src\Identity\Presentation\Livewire\SettingsSecurity;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'create'])->name('login');
        Route::post('/login', [AuthController::class, 'store']);

        Route::get('/register', [AuthController::class, 'create'])->name('register');

        Route::get('/forgot-password', [AuthController::class, 'forgotPassword'])->name('password.request');
        Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');

        Route::get('/reset-password/{token}', [AuthController::class, 'resetPassword'])->name('password.reset');
        Route::post('/reset-password', [AuthController::class, 'updatePassword'])->name('password.update');

        Route::get('/verify-email', [AuthController::class, 'verifyEmail'])->name('verification.notice');

        Route::get('/two-factor-challenge', [AuthController::class, 'twoFactorChallenge'])->name('two-factor.login');
        Route::post('/two-factor-challenge', [AuthController::class, 'verifyTwoFactor'])->name('two-factor.verify');

        Route::get('/confirm-password', [AuthController::class, 'confirmPassword'])->name('password.confirm');
        Route::post('/confirm-password', [AuthController::class, 'verifyPassword']);
    });

    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

    Route::middleware(['auth', 'verified'])->prefix('admin')->name('identity.')->group(function () {
        Route::get('/users', ListUsers::class)->name('users.index');
        Route::get('/users/create', CreateUserForm::class)->name('users.create');
        Route::get('/users/{user}/edit', EditUserForm::class)->name('users.edit');
        Route::get('/roles', ListRoles::class)->name('roles.index');
        Route::get('/settings', SettingsProfile::class)->name('admin.settings');
    });

    Route::middleware('auth')->name('identity.')->group(function () {
        Route::get('/settings/profile', SettingsProfile::class)->name('settings.profile');
        Route::get('/settings/security', SettingsSecurity::class)->name('settings.security');
        Route::get('/settings/appearance', SettingsAppearance::class)->name('settings.appearance');
    });
});
