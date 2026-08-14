<?php

declare(strict_types=1);

use App\Helpers\MenuHelper;
use App\Modules\CoreModule\Models\Role;
use App\Modules\CoreModule\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\Request;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole(Role::findByName('admin', 'web'));
});

function menuHelperBuildItems(): array
{
    $method = new ReflectionMethod(MenuHelper::class, 'buildItems');
    $method->setAccessible(true);

    return $method->invoke(null);
}

function menuHelperFlattenRoutes(array $items, array &$flat = []): array
{
    foreach ($items as $item) {
        if (isset($item['route'])) {
            $flat[] = $item['route'];
        }
        if (isset($item['submenu'])) {
            menuHelperFlattenRoutes($item['submenu'], $flat);
        }
    }

    return $flat;
}

it('no tiene rutas duplicadas en el árbol del menú', function () {
    $routes = menuHelperFlattenRoutes(menuHelperBuildItems());

    expect(count($routes))->toBe(count(array_unique($routes)));
});

it('cada ruta del menú está registrada', function () {
    $routes = menuHelperFlattenRoutes(menuHelperBuildItems());

    foreach ($routes as $route) {
        expect(Route::has($route))->toBeTrue("La ruta del menú [{$route}] no existe.");
    }
});

it('el administrador ve todos los grupos del menú', function () {
    $items = MenuHelper::getSidebarItems($this->admin);

    $labels = collect($items)->pluck('label')->all();

    expect($labels)->toContain(
        'Dashboard',
        'Mi Trabajo',
        'Mi Equipo',
        'Planificación',
        'Operación',
        'Analítica',
        'Calidad',
        'Centro de Contacto',
        'Comunicaciones',
        'Reportes',
        'Administración',
    );
});

it('un usuario sin permisos ve solo los grupos globales', function () {
    $user = User::factory()->create();

    $items = MenuHelper::getSidebarItems($user);

    $labels = collect($items)->pluck('label')->all();

    expect($labels)->toContain('Dashboard', 'Mi Trabajo', 'Documentación', 'Archivos');
    expect($labels)->not->toContain('Mi Equipo', 'Planificación', 'Operación', 'Analítica', 'Calidad', 'Centro de Contacto', 'Comunicaciones', 'Reportes', 'Administración');
});

it('filtra con array de permisos en modo OR', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('news.create');

    $items = MenuHelper::getSidebarItems($user);

    $labels = collect($items)->pluck('label')->all();

    expect($labels)->toContain('Comunicaciones');
});

it('oculta items sin el permiso requerido dentro de un grupo visible', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('schedules.view_team');

    $items = MenuHelper::getSidebarItems($user);

    $teamGroup = collect($items)->firstWhere('label', 'Mi Equipo');
    expect($teamGroup)->not->toBeNull();

    $subLabels = collect($teamGroup['submenu'])->pluck('label')->all();
    expect($subLabels)->toContain('Dashboard del Equipo', 'Miembros', 'Excepciones');
    expect($subLabels)->not->toContain('Aprobar Permisos');
});

it('marca como activo el elemento de la ruta actual', function () {
    $request = Request::create('/schedules/my-day', 'GET');
    $route = new RoutingRoute(['GET'], '/schedules/my-day', ['as' => 'schedules.my-day']);
    $request->setRouteResolver(fn () => $route);
    Illuminate\Support\Facades\Request::swap($request);

    $method = new ReflectionMethod(MenuHelper::class, 'markActive');
    $method->setAccessible(true);
    $items = $method->invoke(null, menuHelperBuildItems());

    $miTrabajo = collect($items)->firstWhere('label', 'Mi Trabajo');
    $activeSub = collect($miTrabajo['submenu'])->firstWhere('is_active', true);

    expect($activeSub['label'])->toBe('Mi Jornada');
    expect($miTrabajo['is_active'])->toBeTrue();
});

it('renderiza la sidebar sin errores y muestra los subgrupos anidados para admin', function () {
    $this->actingAs($this->admin);

    $response = $this->get(route('dashboard'));

    $response->assertOk();
    $response->assertSee('Planificación');
    $response->assertSee('Catálogos y Sistema');
    $response->assertSee('Personas y Organización');
});

it('el grupo de comunicaciones ya no contiene la entrada Inicio', function () {
    $items = menuHelperBuildItems();

    $comunicaciones = collect($items)->firstWhere('label', 'Comunicaciones');

    expect($comunicaciones)->not->toBeNull();
    expect(collect($comunicaciones['submenu'])->pluck('label'))->not->toContain('Inicio');
});

it('el administrador ve las nuevas entradas del menú', function () {
    $items = MenuHelper::getSidebarItems($this->admin);

    $flatLabels = [];
    $collectLabels = function (iterable $items) use (&$collectLabels, &$flatLabels): void {
        foreach ($items as $item) {
            $flatLabels[] = $item['label'];
            if (isset($item['submenu'])) {
                $collectLabels($item['submenu']);
            }
        }
    };
    $collectLabels($items);

    expect($flatLabels)->toContain('Aprobaciones Pendientes');
    expect($flatLabels)->toContain('Administrar Base de Conocimiento');
    expect($flatLabels)->toContain('Asignaciones de Equipos');
});

it('un supervisor ve Mi Equipo con Aprobaciones Pendientes pero no Asignaciones de Equipos', function () {
    $supervisor = User::factory()->create();
    $supervisor->assignRole(Role::findByName('supervisor', 'web'));

    $items = MenuHelper::getSidebarItems($supervisor);

    $teamGroup = collect($items)->firstWhere('label', 'Mi Equipo');
    expect($teamGroup)->not->toBeNull();

    $teamSubLabels = collect($teamGroup['submenu'])->pluck('label')->all();
    expect($teamSubLabels)->toContain('Aprobaciones Pendientes');
    expect($teamSubLabels)->toContain('Aprobar Permisos');

    $adminGroup = collect($items)->firstWhere('label', 'Administración');
    expect($adminGroup)->toBeNull();
});
