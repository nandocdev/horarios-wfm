<?php

declare(strict_types=1);

use App\Modules\QualityModule\Livewire\CalibrationForm;
use App\Modules\QualityModule\Livewire\CriteriaForm;
use App\Modules\QualityModule\Livewire\CriteriaList;
use App\Modules\QualityModule\Livewire\EvaluationDetail;
use App\Modules\QualityModule\Livewire\EvaluationForm;
use App\Modules\QualityModule\Livewire\EvaluationIndex;
use App\Modules\QualityModule\Livewire\FeedbackForm;
use App\Modules\QualityModule\Livewire\QueueList;
use Illuminate\Support\Facades\Route;

Route::get('/evaluaciones', EvaluationIndex::class)
    ->middleware('can:quality.evaluations.view')
    ->name('evaluations.index');

Route::get('/evaluaciones/crear', EvaluationForm::class)
    ->middleware('can:quality.evaluations.create')
    ->name('evaluations.create');

Route::get('/evaluaciones/{evaluation}', EvaluationDetail::class)
    ->middleware('can:quality.evaluations.view')
    ->name('evaluations.show');

Route::get('/evaluaciones/{evaluation}/feedback', FeedbackForm::class)
    ->middleware('can:quality.feedback.create')
    ->name('feedback.create');

Route::get('/evaluaciones/{evaluation}/calibrar', CalibrationForm::class)
    ->middleware('can:quality.calibrations.create')
    ->name('calibrations.create');

Route::get('/criterios', CriteriaList::class)
    ->middleware('can:quality.criteria.view')
    ->name('criteria.index');

Route::get('/criterios/crear', CriteriaForm::class)
    ->middleware('can:quality.criteria.create')
    ->name('criteria.create');

Route::get('/criterios/{criteria}/editar', CriteriaForm::class)
    ->middleware('can:quality.criteria.update')
    ->name('criteria.edit');

Route::get('/colas', QueueList::class)
    ->middleware('can:quality.queues.manage')
    ->name('queues.index');
