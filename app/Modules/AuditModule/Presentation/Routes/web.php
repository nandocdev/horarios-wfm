<?php

declare(strict_types=1);

use App\Modules\AuditModule\Infrastructure\Persistence\Eloquent\AuditLogModel;
use App\Modules\AuditModule\Presentation\Http\Controllers\AuditExportController;
use App\Modules\AuditModule\Presentation\Livewire\ListAuditLogs;

Route::get('/', ListAuditLogs::class)
    ->name('index')
    ->can('viewAny', AuditLogModel::class);

Route::get('/export', [AuditExportController::class, 'export'])
    ->name('export')
    ->can('export', AuditLogModel::class);
