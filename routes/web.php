<?php

declare(strict_types=1);

use App\Modules\CommunicationsModule\Livewire\Home;
use App\Modules\OperationsModule\Livewire\Dashboard;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return app()->call(app(Home::class));
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', Dashboard::class)->name('dashboard');
});

require __DIR__.'/settings.php';
