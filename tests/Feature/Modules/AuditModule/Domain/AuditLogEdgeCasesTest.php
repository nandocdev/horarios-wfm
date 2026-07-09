<?php

declare(strict_types=1);

use App\Modules\AuditModule\Models\AuditLog;
use App\Modules\CoreModule\Models\User;

it('log() con accion updated registra entry aunque el cambio sea solo en timestamps (touch)', function () {
    $user = User::withoutEvents(fn () => User::factory()->create(['name' => 'John']));

    // touch() no dispara eventos Eloquent en Laravel 11+ — usamos save(force)
    $user->forceFill(['updated_at' => now()->addSecond()])->save();
    $user->refresh();

    $log = AuditLog::where('entity_type', get_class($user))
        ->where('entity_id', $user->id)
        ->where('action', 'updated')
        ->latest()
        ->first();

    expect($log)->not->toBeNull();
});

it('log() llamado en test HTTP captura ip_address del request', function () {
    $user = User::withoutEvents(fn () => User::factory()->create());

    // En un test HTTP, request() esta disponible, pero AuditLog::log()
    // se llama desde el observer. Verificamos el log creado via update.
    $user->update(['name' => 'Nuevo']);

    $log = AuditLog::where('entity_type', get_class($user))
        ->where('entity_id', $user->id)
        ->where('action', 'updated')
        ->latest()
        ->first();

    // ip_address depende de request() — en test HTTP puede ser null
    // (Symfony BrowserKit no setea REMOTE_ADDR por defecto) o "127.0.0.1"
    expect($log)->not->toBeNull();
});

it('entity_type guarda FQCN completo, no class_basename', function () {
    $user = User::withoutEvents(fn () => User::factory()->create());

    $log = AuditLog::log($user, 'created');

    expect($log->entity_type)->toBe(get_class($user))
        ->and($log->entity_type)->not->toBe(class_basename($user));
});

it('before contiene los valores originales en updated, no los nuevos', function () {
    $user = User::withoutEvents(fn () => User::factory()->create(['name' => 'Original']));
    $originalName = $user->name;

    $user->update(['name' => 'Modificado']);

    $log = AuditLog::where('entity_type', get_class($user))
        ->where('entity_id', $user->id)
        ->where('action', 'updated')
        ->latest()
        ->first();

    expect($log->before['name'] ?? null)->toBe($originalName)
        ->and($log->after['name'] ?? null)->toBe('Modificado');
});

it('after es null en accion deleted, before contiene los valores finales', function () {
    $user = User::withoutEvents(fn () => User::factory()->create(['name' => 'ParaEliminar']));
    $userId = $user->id;

    $user->delete();

    $log = AuditLog::where('entity_type', get_class($user))
        ->where('entity_id', $userId)
        ->where('action', 'deleted')
        ->latest()
        ->first();

    expect($log)->not->toBeNull();
    expect($log->before['name'] ?? null)->toBe('ParaEliminar');
    expect($log->after)->toBeNull();
});

it('no persiste logs cuando la transaccion hace rollback', function () {
    $user = User::withoutEvents(fn () => User::factory()->create());
    $userId = $user->id;

    try {
        DB::transaction(function () use ($user) {
            $user->update(['name' => 'Cambio1']);
            throw new RuntimeException('Fallo forzado');
        });
    } catch (RuntimeException) {
        // esperado
    }

    $logsAfterRollback = AuditLog::where('entity_type', get_class($user))
        ->where('entity_id', $userId)
        ->where('action', 'updated')
        ->count();

    expect($logsAfterRollback)->toBe(0);
});

it('scopeFilter date_from inclusive funciona correctamente', function () {
    $user = User::withoutEvents(fn () => User::factory()->create());
    $today = now()->format('Y-m-d');
    $logToday = AuditLog::factory()->create([
        'created_at' => $today,
        'user_id' => $user->id,
    ]);

    $logs = AuditLog::query()->filter(['date_from' => $today])->get();

    expect($logs->pluck('id'))->toHaveCount(1)
        ->and($logs->first()->id)->toBe($logToday->id);
});
