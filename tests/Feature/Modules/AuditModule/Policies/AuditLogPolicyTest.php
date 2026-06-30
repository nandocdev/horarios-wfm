<?php

declare(strict_types=1);

use App\Modules\AuditModule\Models\AuditLog;
use App\Modules\AuditModule\Policies\AuditLogPolicy;
use App\Modules\CoreModule\Models\User;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->policy = app(AuditLogPolicy::class);
    $this->log = AuditLog::factory()->create();
});

// ────────────────────────────────────────────
// Matriz: rol × permiso × ability × resultado
// ────────────────────────────────────────────

it('admin puede viewAny, view y export', function (string $ability) {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $result = $ability === 'view'
        ? $this->policy->view($admin, $this->log)
        : $this->policy->{$ability}($admin);

    expect($result)->toBeTrue();
})->with(['viewAny', 'view', 'export']);

it('usuario con audit.view puede viewAny y view pero no export', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('audit.view');

    expect($this->policy->viewAny($user))->toBeTrue()
        ->and($this->policy->view($user, $this->log))->toBeTrue()
        ->and($this->policy->export($user))->toBeFalse();
});

it('usuario con audit.export puede export pero no view', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('audit.export');

    expect($this->policy->export($user))->toBeTrue()
        ->and($this->policy->viewAny($user))->toBeFalse();
});

it('usuario sin permisos ni rol admin no puede ninguna accion', function (string $ability) {
    $user = User::factory()->create();

    $result = $ability === 'view'
        ? $this->policy->view($user, $this->log)
        : $this->policy->{$ability}($user);

    expect($result)->toBeFalse();
})->with(['viewAny', 'view', 'export']);

it('usuario con audit.view no puede export aunque tenga otro permiso no relacionado', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('audit.view');
    $user->givePermissionTo('users.view'); // permiso no relacionado

    expect($this->policy->export($user))->toBeFalse();
});

/*
 * [BUG?] La policy no define un metodo `before(User)` para el bypass de admin.
 * El chequeo `$user->hasRole('admin')` se hace inline en cada ability.
 * Si en el futuro se agrega un `before()` en otra Policy que cortocircuite antes,
 * este comportamiento podria cambiar. Por ahora funciona porque Spatie registra
 * un Gate::before global via `register_permission_check_method`.
 */
it('admin bypass funciona via hasRole inline, no via before()', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    expect($this->policy->viewAny($admin))->toBeTrue();

    $reflection = new ReflectionClass($this->policy);
    expect($reflection->hasMethod('before'))->toBeFalse();
});
