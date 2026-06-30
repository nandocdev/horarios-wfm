<?php

declare(strict_types=1);

use App\Modules\AuditModule\Models\AuditLog;
use App\Modules\CoreModule\Models\User;

beforeEach(function () {
    $this->user = User::withoutEvents(fn () => User::factory()->create());
});

it('--dry-run no elimina registros', function () {
    AuditLog::factory()->create(['created_at' => now()->subDays(200), 'user_id' => $this->user->id]);
    AuditLog::factory()->create(['created_at' => now(), 'user_id' => $this->user->id]);

    $this->artisan('audit:prune', ['--days' => 90, '--dry-run' => true])
        ->expectsOutputToContain('DRY-RUN')
        ->assertSuccessful();

    expect(AuditLog::count())->toBe(2);
});

it('elimina logs anteriores a --days', function () {
    AuditLog::factory()->create(['created_at' => now()->subDays(200), 'user_id' => $this->user->id]);
    $keep = AuditLog::factory()->create(['created_at' => now(), 'user_id' => $this->user->id]);

    $this->artisan('audit:prune', ['--days' => 90])->assertSuccessful();

    expect(AuditLog::count())->toBe(1)
        ->and(AuditLog::first()->id)->toBe($keep->id);
});

it('no elimina logs dentro del rango de retencion', function () {
    AuditLog::factory()->create(['created_at' => now()->subDays(30), 'user_id' => $this->user->id]);
    AuditLog::factory()->create(['created_at' => now(), 'user_id' => $this->user->id]);

    $this->artisan('audit:prune', ['--days' => 90])->assertSuccessful();

    expect(AuditLog::count())->toBe(2);
});

it('usa --days=1 para eliminar logs de ayer', function () {
    $old = AuditLog::factory()->create(['created_at' => now()->subDays(2), 'user_id' => $this->user->id]);
    $keep = AuditLog::factory()->create(['created_at' => now(), 'user_id' => $this->user->id]);

    $this->artisan('audit:prune', ['--days' => 1])->assertSuccessful();

    expect(AuditLog::count())->toBe(1)
        ->and(AuditLog::first()->id)->toBe($keep->id);
});

it('retorna SUCCESS cuando no hay logs para eliminar', function () {
    $this->artisan('audit:prune', ['--days' => 30])
        ->expectsOutputToContain('No hay registros')
        ->assertSuccessful();
});

it('usa chunk para eliminar en lotes', function () {
    AuditLog::factory()->count(5)->create(['created_at' => now()->subDays(200), 'user_id' => $this->user->id]);

    DB::enableQueryLog();
    $this->artisan('audit:prune', ['--days' => 90, '--chunk' => 2])->assertSuccessful();

    $queries = DB::getQueryLog();
    $deleteQueries = array_values(array_filter($queries, fn ($q) => str_starts_with(strtolower(trim($q['query'])), 'delete')));

    expect(count($deleteQueries))->toBeGreaterThanOrEqual(3);
});

it('respeta el parametro --chunk en chunkById', function () {
    AuditLog::factory()->count(7)->create(['created_at' => now()->subDays(200), 'user_id' => $this->user->id]);

    $this->artisan('audit:prune', ['--days' => 90, '--chunk' => 3])->assertSuccessful();

    expect(AuditLog::count())->toBe(0);
});
