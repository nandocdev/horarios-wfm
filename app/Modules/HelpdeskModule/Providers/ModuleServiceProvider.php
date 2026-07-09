<?php

declare(strict_types=1);

namespace App\Modules\HelpdeskModule\Providers;

use App\Modules\HelpdeskModule\Livewire\ManageTickets;
use App\Modules\HelpdeskModule\Livewire\MyTickets;
use App\Modules\HelpdeskModule\Livewire\TicketDetail;
use App\Modules\HelpdeskModule\Models\HelpdeskTicket;
use App\Modules\HelpdeskModule\Policies\HelpdeskTicketPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider;
use Livewire\Livewire;

class ModuleServiceProvider extends AuthServiceProvider
{
    protected $policies = [
        HelpdeskTicket::class => HelpdeskTicketPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        if (file_exists(__DIR__.'/../Routes/web.php')) {
            $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        }

        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'helpdesk');

        Livewire::component('helpdesk.my-tickets', MyTickets::class);
        Livewire::component('helpdesk.manage-tickets', ManageTickets::class);
        Livewire::component('helpdesk.ticket-detail', TicketDetail::class);
    }

    public function register(): void
    {
        //
    }
}
