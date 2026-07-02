<?php

declare(strict_types=1);

use App\Src\Quality\Presentation\Livewire\AgentEvaluationForm;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('quality')->group(function () {
    Route::get('/evaluate/{agentId}', AgentEvaluationForm::class)->name('quality.evaluate');
});
