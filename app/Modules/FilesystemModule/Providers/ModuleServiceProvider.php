<?php

declare(strict_types=1);

namespace App\Modules\FilesystemModule\Providers;

use App\Modules\FilesystemModule\Livewire\DownloadCenter;
use App\Modules\FilesystemModule\Livewire\FileBrowser;
use App\Modules\FilesystemModule\Livewire\QuotaManager;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class ModuleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Registro de componentes Livewire
        Livewire::component('filesystem.browser', FileBrowser::class);
        Livewire::component('filesystem.quota-manager', QuotaManager::class);
        Livewire::component('filesystem.download-center', DownloadCenter::class);

        // 2. Rutas
        if (file_exists(__DIR__.'/../Routes/web.php')) {
            Route::middleware('web')->group(__DIR__.'/../Routes/web.php');
        }

        // 3. Vistas
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'filesystem');
    }
}
