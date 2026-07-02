<?php

declare(strict_types=1);

use App\Src\Connect\Presentation\Http\Controllers\CallRecordController;
use App\Src\Connect\Presentation\Http\Controllers\CiscoFinesseController;
use App\Src\Connect\Presentation\Livewire\AgentDashboard;
use App\Src\Connect\Presentation\Livewire\CreateCallRecord;
use App\Src\Connect\Presentation\Livewire\EditCallRecord;
use App\Src\Connect\Presentation\Livewire\GeneralDashboard;
use App\Src\Connect\Presentation\Livewire\ListCallQueues;
use App\Src\Connect\Presentation\Livewire\ListCallRecords;
use App\Src\Connect\Presentation\Livewire\ListCaseSubtypes;
use App\Src\Connect\Presentation\Livewire\ListChannels;
use App\Src\Connect\Infrastructure\WebSockets\TelephonyEventSubscriber;
use Illuminate\Support\Facades\Route;

// ── Webhook routes (no auth) ──────────────────────────────────────────
Route::post('/api/connect/cisco/webhook', function (TelephonyEventSubscriber $subscriber) {
    $payload = request()->all();
    $subscriber->handleCiscoWebhook($payload);
    return response()->json(['status' => 'ok']);
})->name('connect.cisco.webhook');

Route::post('/api/connect/avaya/webhook', function (TelephonyEventSubscriber $subscriber) {
    $payload = request()->all();
    $subscriber->handleAvayaWebhook($payload);
    return response()->json(['status' => 'ok']);
})->name('connect.avaya.webhook');

// ── API routes (auth) ────────────────────────────────────────────────
Route::middleware(['auth'])->prefix('api/contact-center')->group(function () {
    Route::post('/calls/start', [CallRecordController::class, 'store'])->name('connect.api.calls.store');
    Route::put('/calls/{record}/complete', [CallRecordController::class, 'complete'])->name('connect.api.calls.complete');
    Route::put('/calls/{record}/close', [CallRecordController::class, 'close'])->name('connect.api.calls.close');
    Route::get('/calls/{record}', [CallRecordController::class, 'show'])->name('connect.api.calls.show');
    Route::get('/calls', [CallRecordController::class, 'index'])->name('connect.api.calls.index');

    Route::get('/cisco/agent-snapshot', [CiscoFinesseController::class, 'agentSnapshot'])
        ->name('connect.api.cisco.agent-snapshot');

    Route::get('/my-dashboard', [CiscoFinesseController::class, 'myDashboard'])
        ->name('connect.api.my-dashboard');
});

// ── Web routes (auth + verified) ─────────────────────────────────────
Route::middleware(['web', 'auth', 'verified'])->prefix('admin/platform/connect')->group(function () {
    Route::get('/', AgentDashboard::class)->name('connect.dashboard');
    Route::get('/dashboard', GeneralDashboard::class)->name('connect.general-dashboard');
    Route::get('/call-records', ListCallRecords::class)->name('connect.call-records');
    Route::get('/call-records/create', CreateCallRecord::class)->name('connect.call-records.create');
    Route::get('/call-records/{record}/edit', EditCallRecord::class)->name('connect.call-records.edit');
    Route::get('/call-queues', ListCallQueues::class)->name('connect.call-queues');
    Route::get('/channels', ListChannels::class)->name('connect.channels');
    Route::get('/case-subtypes', ListCaseSubtypes::class)->name('connect.case-subtypes');
});
