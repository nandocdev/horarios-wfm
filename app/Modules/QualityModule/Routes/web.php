<?php

declare(strict_types=1);

use App\Modules\QualityModule\Livewire\CriteriaList;
use App\Modules\QualityModule\Livewire\EvaluationIndex;
use App\Modules\QualityModule\Livewire\QueueList;
use Illuminate\Support\Facades\Route;

Route::get('/evaluaciones', EvaluationIndex::class)
    ->middleware('can:quality.evaluations.view')
    ->name('evaluations.index');

Route::get('/criterios', CriteriaList::class)
    ->middleware('can:quality.criteria.view')
    ->name('criteria.index');

Route::get('/colas', QueueList::class)
    ->middleware('can:quality.queues.manage')
    ->name('queues.index');
