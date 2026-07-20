<?php

declare(strict_types=1);

use App\Modules\OrganizationModule\Actions\CreateDirectorateAction;
use App\Modules\OrganizationModule\Actions\ToggleDirectorateStatusAction;
use App\Modules\OrganizationModule\Actions\UpdateDirectorateAction;
use App\Modules\OrganizationModule\DTOs\DirectorateDTO;
use App\Modules\OrganizationModule\Models\Directorate;
use Illuminate\Database\QueryException;

test('crea una direccion exitosamente', function () {
    $directorate = (new CreateDirectorateAction)->execute(
        DirectorateDTO::fromArray([
            'name' => 'Dirección de Prueba',
            'description' => 'Descripción de prueba',
            'is_active' => true,
        ])
    );

    expect($directorate)->toBeInstanceOf(Directorate::class);
    expect($directorate->name)->toBe('Dirección de Prueba');
    expect($directorate->is_active)->toBeTrue();
    expect($directorate->id)->not->toBeNull();
});

test('crea una direccion con valores por defecto', function () {
    $directorate = (new CreateDirectorateAction)->execute(
        DirectorateDTO::fromArray([
            'name' => 'Dirección Default',
        ])
    );

    expect($directorate->description)->toBeNull();
    expect($directorate->is_active)->toBeTrue();
});

test('actualiza una direccion exitosamente', function () {
    $directorate = (new CreateDirectorateAction)->execute(
        DirectorateDTO::fromArray(['name' => 'Original'])
    );

    $updated = (new UpdateDirectorateAction)->execute(
        $directorate,
        DirectorateDTO::fromArray([
            'name' => 'Actualizada',
            'description' => 'Nueva descripción',
            'is_active' => false,
        ])
    );

    expect($updated->name)->toBe('Actualizada');
    expect($updated->description)->toBe('Nueva descripción');
    expect($updated->is_active)->toBeFalse();
});

test('cambia el estado de una direccion', function () {
    $directorate = (new CreateDirectorateAction)->execute(
        DirectorateDTO::fromArray(['name' => 'Toggle Test'])
    );

    expect($directorate->is_active)->toBeTrue();

    $toggled = (new ToggleDirectorateStatusAction)->execute($directorate);
    expect($toggled->is_active)->toBeFalse();

    $toggledAgain = (new ToggleDirectorateStatusAction)->execute($toggled);
    expect($toggledAgain->is_active)->toBeTrue();
});

test('no permite nombres duplicados en direcciones', function () {
    (new CreateDirectorateAction)->execute(
        DirectorateDTO::fromArray(['name' => 'Única'])
    );

    expect(fn () => (new CreateDirectorateAction)->execute(
        DirectorateDTO::fromArray(['name' => 'Única'])
    ))->toThrow(QueryException::class);
});

test('permite soft delete en direcciones', function () {
    $directorate = (new CreateDirectorateAction)->execute(
        DirectorateDTO::fromArray(['name' => 'Eliminable'])
    );

    $directorate->delete();

    expect(Directorate::find($directorate->id))->toBeNull();
    expect(Directorate::withTrashed()->find($directorate->id))->not->toBeNull();
});

test('permite restaurar una direccion eliminada', function () {
    $directorate = (new CreateDirectorateAction)->execute(
        DirectorateDTO::fromArray(['name' => 'Restaurable'])
    );

    $directorate->delete();
    $directorate->restore();

    expect(Directorate::find($directorate->id))->not->toBeNull();
});
