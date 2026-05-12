<?php

declare(strict_types=1);

namespace App\Modules\HelpdeskModule\Providers;

use App\Modules\HelpdeskModule\Livewire\ManageTickets;
use App\Modules\HelpdeskModule\Livewire\MyTickets;
use App\Modules\HelpdeskModule\Livewire\TicketDetail;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Boot the module services.
     */
    public function boot(): void
    {
        // Rutas
        if (file_exists(__DIR__.'/../Routes/web.php')) {
            $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        }

        // Vistas
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'helpdesk');

        // Componentes Livewire
        Livewire::component('helpdesk.my-tickets', MyTickets::class);
        Livewire::component('helpdesk.manage-tickets', ManageTickets::class);
        Livewire::component('helpdesk.ticket-detail', TicketDetail::class);
    }

    /**
     * Register the module services.
     */
    public function register(): void
    {
        //
    }
}
