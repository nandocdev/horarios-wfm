<?php

declare(strict_types=1);

use App\Modules\WorkflowsModule\Livewire\PendingApprovals;
use App\Modules\WorkflowsModule\Models\WorkflowRequest;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::get('/workflows/pending', PendingApprovals::class)
        ->name('workflows.pending')
        ->can('viewAny', WorkflowRequest::class);
});
