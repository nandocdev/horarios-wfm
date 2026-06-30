<?php

declare(strict_types=1);

use App\Modules\AuditModule\Actions\ExportAuditLogsAction;
use App\Modules\AuditModule\DTOs\AuditLogExportDTO;
use App\Modules\AuditModule\Models\AuditLog;
use App\Modules\CoreModule\Models\User;

beforeEach(function () {
    $this->action = app(ExportAuditLogsAction::class);
    // Crear usuario sin disparar Auditable trait (que generaria AuditLog extra)
    $this->user = User::withoutEvents(fn () => User::factory()->create());
});

it('exporta todos los logs cuando no hay filtros', function () {
    AuditLog::factory()->count(3)->create(['user_id' => $this->user->id]);

    $result = $this->action->execute(new AuditLogExportDTO);

    expect($result)->toHaveCount(3);
});

it('aplica filtro search en entity_type con case-insensitive', function () {
    AuditLog::factory()->create(['entity_type' => 'App\Models\Team', 'user_id' => $this->user->id]);
    AuditLog::factory()->create(['entity_type' => 'App\Models\User', 'user_id' => $this->user->id]);

    $result = $this->action->execute(new AuditLogExportDTO(search: 'team'));

    expect($result)->toHaveCount(1)
        ->and($result->first()->entity_type)->toBe('App\Models\Team');
});

it('aplica filtro search case-insensitive en action', function () {
    AuditLog::factory()->create(['action' => 'created', 'user_id' => $this->user->id]);
    AuditLog::factory()->create(['action' => 'updated', 'user_id' => $this->user->id]);

    $result = $this->action->execute(new AuditLogExportDTO(search: 'CREATED'));

    expect($result)->toHaveCount(1);
});

it('aplica filtro search en ip_address', function () {
    AuditLog::factory()->create(['ip_address' => '192.168.1.1', 'user_id' => $this->user->id]);
    AuditLog::factory()->create(['ip_address' => '10.0.0.1', 'user_id' => $this->user->id]);

    $result = $this->action->execute(new AuditLogExportDTO(search: '192.168'));

    expect($result)->toHaveCount(1);
});

it('aplica filtro por accion exacta', function () {
    AuditLog::factory()->create(['action' => 'created', 'user_id' => $this->user->id]);
    AuditLog::factory()->create(['action' => 'deleted', 'user_id' => $this->user->id]);

    $result = $this->action->execute(new AuditLogExportDTO(action: 'deleted'));

    expect($result)->toHaveCount(1)
        ->and($result->first()->action)->toBe('deleted');
});

it('aplica filtro por entity_type exacto', function () {
    AuditLog::factory()->create(['entity_type' => 'App\Models\A', 'entity_id' => 1, 'user_id' => $this->user->id]);
    AuditLog::factory()->create(['entity_type' => 'App\Models\B', 'entity_id' => 2, 'user_id' => $this->user->id]);

    $result = $this->action->execute(new AuditLogExportDTO(entityType: 'App\Models\A'));

    expect($result)->toHaveCount(1);
});

it('aplica filtro por rango de fechas date_from', function () {
    $old = AuditLog::factory()->create(['created_at' => now()->subDays(10), 'user_id' => $this->user->id]);
    $new = AuditLog::factory()->create(['created_at' => now(), 'user_id' => $this->user->id]);

    $result = $this->action->execute(new AuditLogExportDTO(dateFrom: now()->subDays(5)->toDateString()));

    expect($result)->toHaveCount(1)
        ->and($result->first()->id)->toBe($new->id);
});

it('aplica filtro por rango de fechas date_to', function () {
    $old = AuditLog::factory()->create(['created_at' => now()->subDays(10), 'user_id' => $this->user->id]);
    AuditLog::factory()->create(['created_at' => now(), 'user_id' => $this->user->id]);

    $result = $this->action->execute(new AuditLogExportDTO(dateTo: now()->subDays(5)->toDateString()));

    expect($result)->toHaveCount(1)
        ->and($result->first()->id)->toBe($old->id);
});

it('retorna coleccion vacia cuando no hay resultados', function () {
    AuditLog::factory()->create(['action' => 'created', 'user_id' => $this->user->id]);

    $result = $this->action->execute(new AuditLogExportDTO(action: 'nonexistent'));

    expect($result)->toBeEmpty();
});

it('eager-loads la relacion user (anti N+1) — 2 queries como maximo', function () {
    $user = User::withoutEvents(fn () => User::factory()->create());
    AuditLog::factory()->create(['user_id' => $user->id]);

    DB::enableQueryLog();
    $result = $this->action->execute(new AuditLogExportDTO);
    $queries = DB::getQueryLog();

    $selectQueries = array_values(array_filter($queries, fn ($q) => str_starts_with(strtolower(trim($q['query'])), 'select')));
    $selectCount = count($selectQueries);

    expect($selectCount)->toBeLessThanOrEqual(2);
});

it('ordena los resultados por created_at descendente', function () {
    $old = AuditLog::factory()->create(['created_at' => now()->subDay(), 'user_id' => $this->user->id]);
    $mid = AuditLog::factory()->create(['created_at' => now()->subHour(), 'user_id' => $this->user->id]);
    $new = AuditLog::factory()->create(['created_at' => now(), 'user_id' => $this->user->id]);

    $result = $this->action->execute(new AuditLogExportDTO);

    expect($result->pluck('id')->toArray())->toBe([$new->id, $mid->id, $old->id]);
});
