<?php

declare(strict_types=1);

use App\Modules\AuditModule\Models\AuditLog;
use App\Modules\CoreModule\Models\User;

beforeEach(function () {
    $this->user = User::withoutEvents(fn () => User::factory()->create());
});

// ────────────────────────────────────────────
// NOTA: Algunos tests requieren PostgreSQL 16 (FK reales, ILIKE, pg_indexes).
// Se marcan con skip condicional. En CI con PostgreSQL se ejecutan completos.

it('ON DELETE SET NULL: eliminar usuario pone user_id en null', function () {
    $log = AuditLog::factory()->create(['user_id' => $this->user->id]);
    $this->user->delete();

    $fresh = DB::table('audit_logs')->where('id', $log->id)->first();

    expect($fresh)->not->toBeNull();
    // En PostgreSQL con FK real: SET NULL. En SQLite sin FK: conserva el valor.
    if (DB::connection()->getDriverName() === 'pgsql') {
        expect($fresh->user_id)->toBeNull();
    }
});

it('JSONB almacena y recupera objetos anidados correctamente', function () {
    $before = ['name' => 'John', 'team' => ['id' => 1, 'name' => 'Ventas']];
    $after = ['name' => 'Jane', 'team' => ['id' => 1, 'name' => 'Ventas']];

    $log = AuditLog::factory()->create([
        'before' => $before,
        'after' => $after,
        'user_id' => $this->user->id,
    ]);

    $fresh = AuditLog::find($log->id);

    expect($fresh->before)->toBe($before)
        ->and($fresh->after)->toBe($after);
});

it('JSONB acepta null como valor valido', function () {
    $log = AuditLog::factory()->create([
        'before' => null,
        'after' => null,
        'user_id' => $this->user->id,
    ]);

    $fresh = AuditLog::find($log->id);

    expect($fresh->before)->toBeNull()
        ->and($fresh->after)->toBeNull();
});

it('permite inserts duplicados en entity_type+entity_id (no hay unique constraint)', function () {
    AuditLog::factory()->create([
        'entity_type' => 'App\Models\Team',
        'entity_id' => 1,
        'user_id' => $this->user->id,
    ]);
    AuditLog::factory()->create([
        'entity_type' => 'App\Models\Team',
        'entity_id' => 1,
        'user_id' => $this->user->id,
    ]);

    expect(AuditLog::where('entity_type', 'App\Models\Team')->where('entity_id', 1)->count())->toBe(2);
});

it('action acepta cualquier string (no hay CHECK constraint ni ENUM)', function () {
    $log = AuditLog::factory()->create([
        'action' => 'weekly_schedule.published',
        'user_id' => $this->user->id,
    ]);

    expect($log->action)->toBe('weekly_schedule.published');
});

it('ip_address acepta NULL', function () {
    $log = AuditLog::factory()->create([
        'ip_address' => null,
        'user_id' => $this->user->id,
    ]);

    expect($log->ip_address)->toBeNull();
});

it('indice compuesto (entity_type, entity_id) existe en PostgreSQL', function () {
    $indexes = DB::select("
        SELECT indexname FROM pg_indexes
        WHERE tablename = 'audit_logs'
        AND indexdef LIKE '%entity_type%entity_id%'
    ");

    expect($indexes)->toHaveCount(1);
});
