<?php

declare(strict_types=1);

use App\Modules\PersonnelModule\Actions\CreateTeamAction;
use App\Modules\PersonnelModule\Actions\ToggleTeamStatusAction;
use App\Modules\PersonnelModule\Actions\UpdateTeamAction;
use App\Modules\PersonnelModule\DTOs\TeamDTO;
use App\Modules\PersonnelModule\Models\Team;
use Illuminate\Database\QueryException;

test('crea un equipo exitosamente', function () {
    $team = (new CreateTeamAction)->execute(
        TeamDTO::fromArray([
            'name' => 'Equipo Alpha',
            'description' => 'Equipo de pruebas',
            'is_active' => true,
        ])
    );

    expect($team)->toBeInstanceOf(Team::class);
    expect($team->name)->toBe('Equipo Alpha');
    expect($team->is_active)->toBeTrue();
});

test('actualiza un equipo exitosamente', function () {
    $team = (new CreateTeamAction)->execute(
        TeamDTO::fromArray(['name' => 'Equipo Original'])
    );

    $updated = (new UpdateTeamAction)->execute(
        $team,
        TeamDTO::fromArray([
            'name' => 'Equipo Actualizado',
            'description' => 'Nueva descripción',
            'is_active' => false,
        ])
    );

    expect($updated->name)->toBe('Equipo Actualizado');
    expect($updated->is_active)->toBeFalse();
});

test('cambia el estado de un equipo', function () {
    $team = (new CreateTeamAction)->execute(
        TeamDTO::fromArray(['name' => 'Equipo Toggle'])
    );

    expect($team->is_active)->toBeTrue();

    $toggled = (new ToggleTeamStatusAction)->execute($team);
    expect($toggled->is_active)->toBeFalse();
});

test('no permite nombres de equipo duplicados', function () {
    (new CreateTeamAction)->execute(
        TeamDTO::fromArray(['name' => 'Único'])
    );

    expect(fn () => (new CreateTeamAction)->execute(
        TeamDTO::fromArray(['name' => 'Único'])
    ))->toThrow(QueryException::class);
});
