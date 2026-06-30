<?php

declare(strict_types=1);

use App\Modules\AuditModule\Models\AuditLog;
use App\Modules\CoreModule\Models\User;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    // Crear admin sin disparar Auditable
    $this->admin = User::withoutEvents(fn () => User::factory()->create());
    $this->admin->assignRole('admin');
});

// ────────────────────────────────────────────
// Autenticacion y autorizacion
// ────────────────────────────────────────────

it('redirige a login si el usuario no esta autenticado', function () {
    $this->get(route('audit.export'))
        ->assertRedirect(route('login'));
});

it('retorna 403 si el usuario no tiene permiso audit.export', function () {
    $user = User::withoutEvents(fn () => User::factory()->create());
    $user->givePermissionTo('audit.view');

    $this->actingAs($user)
        ->get(route('audit.export'))
        ->assertForbidden();
});

it('retorna 200 y CSV si el usuario tiene audit.export', function () {
    AuditLog::factory()->create(['user_id' => $this->admin->id]);

    $user = User::withoutEvents(fn () => User::factory()->create());
    $user->givePermissionTo('audit.export');

    $this->actingAs($user)
        ->get(route('audit.export'))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=utf-8');
});

it('retorna 200 y CSV si el usuario es admin', function () {
    AuditLog::factory()->create(['user_id' => $this->admin->id]);

    $this->actingAs($this->admin)
        ->get(route('audit.export'))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=utf-8');
});

it('retorna 200 y JSON cuando format=json', function () {
    AuditLog::factory()->create(['action' => 'updated', 'user_id' => $this->admin->id]);

    $this->actingAs($this->admin)
        ->get(route('audit.export', ['format' => 'json']))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/json')
        ->assertJsonStructure([['id', 'action', 'entity_type']]);
});

// ────────────────────────────────────────────
// Formato y contenido del CSV
// ────────────────────────────────────────────

it('CSV incluye header row y datos de los logs', function () {
    $user = User::withoutEvents(fn () => User::factory()->create(['name' => 'Juan Perez']));
    AuditLog::factory()->create([
        'action' => 'created',
        'entity_type' => 'App\Models\Team',
        'entity_id' => 42,
        'user_id' => $user->id,
        'ip_address' => '10.0.0.1',
    ]);

    $response = $this->actingAs($this->admin)
        ->get(route('audit.export', ['format' => 'csv']))
        ->assertOk();

    $content = $response->streamedContent();

    expect($content)->toContain('id,entity_type,entity_id,action,before,after,user,ip_address,created_at')
        ->and($content)->toContain('Juan Perez')
        ->and($content)->toContain('10.0.0.1');
});

it('CSV con 0 resultados incluye solo el header row', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('audit.export', ['action' => 'nonexistent']))
        ->assertOk();

    $content = $response->streamedContent();
    $lines = array_filter(explode("\n", trim($content)));

    expect($lines)->toHaveCount(1);
});

it('CSV maneja before/after con JSON escapado correctamente', function () {
    AuditLog::factory()->create([
        'before' => null,
        'after' => ['name' => 'Test', 'items' => [1, 2, 3]],
        'user_id' => $this->admin->id,
    ]);

    $response = $this->actingAs($this->admin)
        ->get(route('audit.export', ['format' => 'csv']))
        ->assertOk();

    $content = $response->streamedContent();

    expect($content)->toContain('"Test"')
        ->and($content)->toContain('[1,2,3]');
});

// ────────────────────────────────────────────
// Filtros via query string
// ────────────────────────────────────────────

it('aplica filtro action via query string en CSV', function () {
    AuditLog::factory()->create(['action' => 'created', 'user_id' => $this->admin->id]);
    AuditLog::factory()->create(['action' => 'deleted', 'user_id' => $this->admin->id]);

    $response = $this->actingAs($this->admin)
        ->get(route('audit.export', ['action' => 'deleted']))
        ->assertOk();

    $content = $response->streamedContent();
    $lines = array_filter(explode("\n", trim($content)));

    expect($lines)->toHaveCount(2);
});

it('aplica filtro date_from/date_to via query string', function () {
    AuditLog::factory()->create(['created_at' => now()->subDays(10), 'user_id' => $this->admin->id]);
    AuditLog::factory()->create(['created_at' => now(), 'user_id' => $this->admin->id]);

    $response = $this->actingAs($this->admin)
        ->get(route('audit.export', ['dateFrom' => now()->subDays(5)->toDateString()]))
        ->assertOk();

    $content = $response->streamedContent();
    $lines = array_filter(explode("\n", trim($content)));

    expect($lines)->toHaveCount(2);
});

it('aplica filtro search via query string', function () {
    AuditLog::factory()->create(['ip_address' => '192.168.1.1', 'user_id' => $this->admin->id]);
    AuditLog::factory()->create(['ip_address' => '10.0.0.1', 'user_id' => $this->admin->id]);

    $response = $this->actingAs($this->admin)
        ->get(route('audit.export', ['search' => '192.168']))
        ->assertOk();

    $content = $response->streamedContent();
    $lines = array_filter(explode("\n", trim($content)));

    expect($lines)->toHaveCount(2);
});

// ────────────────────────────────────────────
// Ruta index
// ────────────────────────────────────────────

it('ruta audit.index exige autenticacion', function () {
    $this->get(route('audit.index'))
        ->assertRedirect(route('login'));
});

it('ruta audit.index exige permiso audit.view', function () {
    $user = User::withoutEvents(fn () => User::factory()->create());

    $this->actingAs($user)
        ->get(route('audit.index'))
        ->assertForbidden();
});
