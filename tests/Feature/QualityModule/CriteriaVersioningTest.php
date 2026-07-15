<?php

declare(strict_types=1);

use App\Modules\QualityModule\Actions\CreateCriteriaVersionAction;
use App\Modules\QualityModule\Models\Criteria;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a new criteria version and inactivates the previous one', function () {
    $criteria = Criteria::create(['code' => 'V01']);

    $action = app(CreateCriteriaVersionAction::class);

    $version1 = $action->execute($criteria->id, [
        'criterio_text' => 'Version 1',
        'puntaje' => 10,
    ]);

    expect($version1->version)->toBe(1)
        ->and($version1->valid_to)->toBeNull();

    $version2 = $action->execute($criteria->id, [
        'criterio_text' => 'Version 2',
        'puntaje' => 15,
    ]);

    $version1->refresh();

    expect($version2->version)->toBe(2)
        ->and($version2->valid_to)->toBeNull()
        ->and($version1->valid_to)->not->toBeNull();
});
