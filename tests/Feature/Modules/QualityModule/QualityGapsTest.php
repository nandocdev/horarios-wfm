<?php

declare(strict_types=1);

use App\Modules\QualityModule\Livewire\Forms\CriteriaFormData;
use App\Modules\QualityModule\Livewire\Forms\QueueFormData;

test('TeamEvaluationSelector registrado en ModuleServiceProvider', function () {
    $contents = file_get_contents(app_path('Modules/QualityModule/Providers/ModuleServiceProvider.php'));
    expect($contents)->toContain('quality.team-evaluation-selector');
});

test('CriteriaVersionCreated event tiene listener registrado', function () {
    $contents = file_get_contents(app_path('Modules/QualityModule/Providers/ModuleServiceProvider.php'));
    expect($contents)->toContain('CriteriaVersionCreated::class');
    expect($contents)->toContain('UpdateQueueScoreAverages::class');
});

test('CriteriaFormData Form Object existe', function () {
    expect(class_exists(CriteriaFormData::class))->toBeTrue();
});

test('QueueFormData Form Object existe', function () {
    expect(class_exists(QueueFormData::class))->toBeTrue();
});

test('QueueList usa QueueFormData', function () {
    $contents = file_get_contents(app_path('Modules/QualityModule/Livewire/QueueList.php'));
    expect($contents)->toContain('QueueFormData');
    expect($contents)->toContain('$this->form->validate()');
});

test('CriteriaForm usa CriteriaFormData', function () {
    $contents = file_get_contents(app_path('Modules/QualityModule/Livewire/CriteriaForm.php'));
    expect($contents)->toContain('CriteriaFormData');
});
