<?php

declare(strict_types=1);

use App\Modules\ConnectModule\Http\Controllers\CallRecordController;
use App\Modules\ConnectModule\Http\Controllers\CiscoFinesseController;
use App\Modules\ConnectModule\Livewire\AgentDashboard;
use App\Modules\ConnectModule\Livewire\CreateCallRecord;
use App\Modules\ConnectModule\Livewire\EditCallRecord;
use App\Modules\ConnectModule\Livewire\GeneralDashboard;
use App\Modules\ConnectModule\Livewire\ListCallQueues;
use App\Modules\ConnectModule\Livewire\ListCallRecords;
use App\Modules\ConnectModule\Livewire\ListCaseSubtypes;
use App\Modules\ConnectModule\Livewire\ListChannels;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->group(function () {
    Route::post('/api/contact-center/calls/start', [CallRecordController::class, 'start'])
        ->withoutMiddleware('auth:sanctum')
        ->name('contact-center.call-start');

    Route::put('/api/contact-center/calls/{id}/complete', [CallRecordController::class, 'complete'])
        ->middleware(['auth'])
        ->name('contact-center.call-complete');

    Route::put('/api/contact-center/calls/{id}/close', [CallRecordController::class, 'close'])
        ->withoutMiddleware('auth:sanctum')
        ->name('contact-center.call-close');

    Route::get('/api/contact-center/subtypes', [CallRecordController::class, 'subtypes'])
        ->middleware(['auth'])
        ->name('contact-center.subtypes.index');

    Route::middleware(['auth'])->group(function () {
        Route::get('/api/contact-center/cisco/agent-snapshot', [CiscoFinesseController::class, 'agentSnapshot'])
            ->name('contact-center.cisco.agent-snapshot');

        Route::get('/contact-center/my-dashboard', AgentDashboard::class)
            ->name('contact-center.agent-dashboard');

        Route::get('/contact-center/general-dashboard', GeneralDashboard::class)
            ->name('contact-center.general-dashboard');

        Route::get('/contact-center/calls', ListCallRecords::class)
            ->name('contact-center.calls.index');

        Route::get('/contact-center/calls/create', CreateCallRecord::class)
            ->name('contact-center.calls.create');

        Route::get('/contact-center/calls/{callRecord}/edit', EditCallRecord::class)
            ->name('contact-center.calls.edit');

        Route::get('/contact-center/catalogs/queues', ListCallQueues::class)
            ->name('contact-center.admin.queues.index');

        Route::get('/contact-center/catalogs/channels', ListChannels::class)
            ->name('contact-center.admin.channels.index');

        Route::get('/contact-center/catalogs/subtypes', ListCaseSubtypes::class)
            ->name('contact-center.admin.subtypes.index');

    });
});
