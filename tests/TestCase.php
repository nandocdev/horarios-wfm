<?php

declare(strict_types=1);

namespace Tests;

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    private static bool $migrated = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (! self::$migrated) {
            $this->artisan('migrate:fresh', [
                '--seeder' => RolesAndPermissionsSeeder::class,
                '--seed' => true,
            ]);

            self::$migrated = true;
        }

        $this->startTransaction();
    }

    protected function tearDown(): void
    {
        try {
            $this->endTransaction();
        } catch (\Throwable) {
        }

        parent::tearDown();
    }

    private function startTransaction(): void
    {
        foreach ($this->connectionsToTransact() as $name) {
            $connection = $this->app->make('db')->connection($name);
            $dispatcher = $connection->getEventDispatcher();

            $connection->unsetEventDispatcher();
            $connection->beginTransaction();
            $connection->setEventDispatcher($dispatcher);
        }
    }

    private function endTransaction(): void
    {
        foreach ($this->connectionsToTransact() as $name) {
            $connection = $this->app->make('db')->connection($name);

            if ($connection->getPdo()) {
                while ($connection->getPdo()->inTransaction()) {
                    $connection->rollBack();
                }
            }

            $connection->disconnect();
        }
    }

    private function connectionsToTransact(): array
    {
        return [config('database.default')];
    }

    protected function skipUnlessFortifyFeature(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
