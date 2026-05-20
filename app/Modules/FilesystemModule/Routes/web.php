<?php

declare(strict_types=1);

use App\Modules\FilesystemModule\Livewire\FileBrowser;
use App\Modules\FilesystemModule\Livewire\QuotaManager;
use Illuminate\Support\Facades\Route;

Route::get('/filesystem', FileBrowser::class)->name('filesystem.index');
Route::get('/filesystem/quotas', QuotaManager::class)->name('filesystem.quotas');
