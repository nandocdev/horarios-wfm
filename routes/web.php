<?php

declare(strict_types=1);

use App\Modules\CommunicationsModule\Livewire\Home;
use Illuminate\Support\Facades\Route;

Route::get('/', Home::class)->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', \App\Modules\ConnectModule\Livewire\RealtimeOperationDashboard::class)->name('dashboard');
});

require __DIR__.'/settings.php';
