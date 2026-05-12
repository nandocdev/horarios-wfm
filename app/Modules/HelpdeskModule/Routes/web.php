<?php

declare(strict_types=1);

use App\Modules\HelpdeskModule\Livewire\ManageTickets;
use App\Modules\HelpdeskModule\Livewire\MyTickets;
use App\Modules\HelpdeskModule\Livewire\TicketDetail;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('helpdesk')->name('helpdesk.')->group(function () {
    // Autogestión (Operador)
    Route::get('/my-tickets', MyTickets::class)->name('my-tickets');

    // Gestión (Soporte/WFM/Admin)
    Route::get('/manage', ManageTickets::class)->name('manage');

    // Compartido (Detalle interactivo)
    Route::get('/ticket/{ticket}', TicketDetail::class)->name('ticket.detail');
});
