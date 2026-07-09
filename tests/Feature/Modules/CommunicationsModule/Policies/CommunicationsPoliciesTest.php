<?php

declare(strict_types=1);

use App\Modules\CommunicationsModule\Database\Seeders\CommunicationsPermissionSeeder;
use App\Modules\CommunicationsModule\Models\News;
use App\Modules\CommunicationsModule\Policies\ContentModerationPolicy;
use App\Modules\CommunicationsModule\Policies\NewsPolicy;
use App\Modules\CoreModule\Models\User;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(CommunicationsPermissionSeeder::class);

    $this->newsPolicy = app(NewsPolicy::class);
    $this->moderationPolicy = app(ContentModerationPolicy::class);
    $this->author = User::withoutEvents(fn () => User::factory()->create());
    $this->news = News::create([
        'title' => 'Test News',
        'slug' => 'test-news',
        'content' => 'Content',
        'author_id' => $this->author->id,
        'status' => 'draft',
    ]);
});

// ────────────────────────────────────────────
// NewsPolicy — Matriz permisos
// ────────────────────────────────────────────

it('concede acceso segun permiso granulares en NewsPolicy', function (
    string $ability,
    string $permission,
    bool $expected,
    ?callable $setup = null,
) {
    $user = User::withoutEvents(fn () => User::factory()->create());
    if ($setup) {
        $setup($user);
    } else {
        $user->givePermissionTo($permission);
    }

    $result = match ($ability) {
        'viewAny' => $this->newsPolicy->viewAny($user),
        'view' => $this->newsPolicy->view($user, $this->news),
        'create' => $this->newsPolicy->create($user),
        'update' => $this->newsPolicy->update($user, $this->news),
        'delete' => $this->newsPolicy->delete($user, $this->news),
        'viewPending' => $this->newsPolicy->viewPending($user),
        'moderateContent' => $this->newsPolicy->moderateContent($user, $this->news),
        default => throw new InvalidArgumentException("Unknown ability: {$ability}"),
    };

    expect($result)->toBe($expected);
})->with([
    ['viewAny',          'news.view',            true,   null],
    ['view',             'news.view',            true,   null],
    ['create',           'news.create',          true,   null],
    ['update',           'news.edit',            true,   null],
    ['delete',           'news.delete',          true,   null],
    ['viewPending',      'communications.view_pending', true, null],
    ['moderateContent',  'communications.moderate',     true, null],
    // Sin permisos
    ['viewAny',          '',                     false,  fn ($u) => null],
    ['create',           '',                     false,  fn ($u) => null],
    ['viewPending',      '',                     false,  fn ($u) => null],
]);

it('autor al actualizar/eliminar su propia noticia sin permiso explicito', function (string $ability) {
    $result = match ($ability) {
        'update' => $this->newsPolicy->update($this->author, $this->news),
        'delete' => $this->newsPolicy->delete($this->author, $this->news),
        default => false,
    };

    expect($result)->toBeTrue();
})->with(['update', 'delete']);

it('usuario sin news.edit no puede editar noticia ajena', function () {
    $other = User::withoutEvents(fn () => User::factory()->create());
    expect($this->newsPolicy->update($other, $this->news))->toBeFalse();
});

it('usuario sin news.delete no puede eliminar noticia ajena', function () {
    $other = User::withoutEvents(fn () => User::factory()->create());
    expect($this->newsPolicy->delete($other, $this->news))->toBeFalse();
});

// ────────────────────────────────────────────
// ContentModerationPolicy
// ────────────────────────────────────────────

it('concede moderacion segun permisos en ContentModerationPolicy', function (string $ability, string $permission) {
    $user = User::withoutEvents(fn () => User::factory()->create());
    $user->givePermissionTo($permission);

    $result = match ($ability) {
        'moderate' => $this->moderationPolicy->moderate($user),
        'approve' => $this->moderationPolicy->approve($user),
        'reject' => $this->moderationPolicy->reject($user),
        'archive' => $this->moderationPolicy->archive($user),
        'viewPending' => $this->moderationPolicy->viewPending($user),
        default => false,
    };

    expect($result)->toBeTrue();
})->with([
    ['moderate',    'communications.moderate'],
    ['approve',     'communications.approve'],
    ['reject',      'communications.reject'],
    ['archive',     'communications.archive'],
    ['viewPending', 'communications.view_pending'],
]);

/*
 * [BUG?] ContentModerationPolicy->moderateContent() usa
 * $user->hasRole(['admin', 'moderator', 'owner']) en lugar de
 * permisos de Spatie. Es el unico metodo en todo el modulo que
 * usa roles directamente. Si el rol 'moderator' no existe o no
 * esta asignado, usuarios con permiso communications.moderate
 * no podrian moderar contenido especifico.
 */
it('moderateContent usa roles admin/moderator/owner no permisos Spatie', function () {
    $user = User::withoutEvents(fn () => User::factory()->create());
    $user->givePermissionTo('communications.moderate');
    $user->givePermissionTo('communications.approve');

    // Tiene permisos, pero no tiene roles admin/moderator/owner
    expect($this->moderationPolicy->moderateContent($user, $this->news))->toBeFalse();
});

it('moderateContent permite acceso a usuario con rol admin', function () {
    $admin = User::withoutEvents(fn () => User::factory()->create());
    $admin->assignRole('admin');

    expect($this->moderationPolicy->moderateContent($admin, $this->news))->toBeTrue();
});

// ────────────────────────────────────────────
// before() — no existe en policies del modulo
// ────────────────────────────────────────────

it('NewsPolicy no tiene metodo before() — admin no bypassea automaticamente', function () {
    $reflection = new ReflectionClass($this->newsPolicy);
    expect($reflection->hasMethod('before'))->toBeFalse();
});
