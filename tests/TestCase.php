<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // En entornos de test de feature, aseguramos que los permisos existan 
        // para evitar que el layout (sidebar) falle al renderizar.
        if (! $this instanceof \Tests\Unit\TestCase) {
            $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        }
    }

    protected function skipUnlessFortifyFeature(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
