<?php

declare(strict_types=1);

use App\Modules\AuditModule\Http\Controllers\AuditExportController;
use App\Modules\AuditModule\Livewire\ListAuditLogs;
use App\Modules\AuditModule\Models\AuditLog;

Route::get('/', ListAuditLogs::class)
    ->name('index')
    ->can('viewAny', AuditLog::class);

Route::get('/export', [AuditExportController::class, 'export'])
    ->name('export')
    ->can('export', AuditLog::class);
