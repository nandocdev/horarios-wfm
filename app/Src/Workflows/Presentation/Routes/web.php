<?php

declare(strict_types=1);

use App\Src\Workflows\Presentation\Livewire\ApprovalInbox;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('admin/workflows')->group(function () {
    Route::get('/approvals', ApprovalInbox::class)->name('workflows.approvals');
});
