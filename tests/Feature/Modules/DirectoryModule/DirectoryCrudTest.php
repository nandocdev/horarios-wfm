<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\DirectoryModule;

use App\Modules\CoreModule\Models\User;
use App\Modules\DirectoryModule\Livewire\ManageDirectoryUnits;
use App\Modules\DirectoryModule\Livewire\UpsertDirectoryUnit;
use App\Modules\DirectoryModule\Models\Building;
use App\Modules\DirectoryModule\Models\Unit;
use App\Modules\KnowledgeModule\Models\KnowledgeArticle;
use App\Modules\KnowledgeModule\Models\KnowledgeCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (['directory.manage', 'knowledge.viewAny', 'knowledge.manage'] as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }

    $this->supervisor = User::factory()->create();
    $this->supervisor->givePermissionTo(['directory.manage', 'knowledge.manage']);

    $this->operator = User::factory()->create();
    $this->operator->givePermissionTo('knowledge.viewAny');
});

test('supervisor crea una unidad completa con edificio, admin, sector, piso y servicios con contacto', function () {
    $this->actingAs($this->supervisor);

    Livewire::test(UpsertDirectoryUnit::class)
        ->set('form.new_building', 'Hospital Pediátrico')
        ->set('form.director_name', 'Dra. María López')
        ->set('form.subdirector_name', 'Dr. Carlos Ruiz')
        ->set('form.administrator_name', 'Lic. Juan Pérez')
        ->set('form.sector', 'Nodo Norte')
        ->set('form.new_level', 'Piso 2')
        ->set('form.services', [
            [
                'name' => 'Cardiología',
                'door_id' => 'C-201',
                'attention_hours' => '07:00 - 15:00',
                'results_hours' => '16:00 - 17:00',
                'contact_role' => 'Citas',
                'contact_extension' => '4210',
                'contact_email' => 'citas-pediatria@css.gob.pa',
            ],
            [
                'name' => 'Radiología',
                'door_id' => 'C-205',
                'attention_hours' => '08:00 - 14:00',
                'results_hours' => null,
                'contact_role' => 'Recepción',
                'contact_extension' => '4220',
                'contact_email' => null,
            ],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('directory_buildings', [
        'name' => 'Hospital Pediátrico',
        'director_name' => 'Dra. María López',
        'administrator_name' => 'Lic. Juan Pérez',
    ]);

    $building = Building::where('name', 'Hospital Pediátrico')->first();
    $unit = Unit::where('building_id', $building->id)->first();

    expect($unit)->not->toBeNull();
    expect($unit->sector)->toBe('Nodo Norte');
    expect($unit->level)->toBe('Piso 2');

    expect($unit->services()->count())->toBe(2);

    $cardiology = $unit->services()->where('name', 'Cardiología')->first();
    expect($cardiology->door_id)->toBe('C-201');
    expect($cardiology->attention_hours)->toBe('07:00 - 15:00');
    expect($cardiology->contact_role)->toBe('Citas');
    expect($cardiology->contact_extension)->toBe('4210');
    expect($cardiology->contact_email)->toBe('citas-pediatria@css.gob.pa');
});

test('la jerarquía administrativa se recopila una sola vez por edificio', function () {
    $this->actingAs($this->supervisor);

    $building = Building::create([
        'name' => 'Instituto Cardiovascular',
        'director_name' => 'Dra. Ana Torres',
        'administrator_name' => 'Lic. Pedro Gómez',
        'is_active' => true,
    ]);

    Livewire::test(UpsertDirectoryUnit::class)
        ->set('form.building_id', $building->id)
        ->set('form.sector', 'Nodo Sur')
        ->set('form.new_level', 'Piso 1')
        ->set('form.services', [
            [
                'name' => 'Laboratorio',
                'door_id' => 'A-101',
                'attention_hours' => '07:00 - 15:00',
                'results_hours' => null,
                'contact_role' => 'Recepción',
                'contact_extension' => '3301',
                'contact_email' => null,
            ],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseCount('directory_buildings', 1);
    $this->assertDatabaseHas('directory_units', [
        'building_id' => $building->id,
        'level' => 'Piso 1',
    ]);

    $unit = Unit::where('building_id', $building->id)->where('level', 'Piso 1')->first();
    expect($unit->building->director_name)->toBe('Dra. Ana Torres');
});

test('al seleccionar un piso existente se cargan sus servicios y contactos', function () {
    $this->actingAs($this->supervisor);

    $building = Building::create([
        'name' => 'Complejo Hospitalario',
        'director_name' => 'Dra. Test',
        'administrator_name' => 'Lic. Test',
        'is_active' => true,
    ]);

    $unit = Unit::create([
        'building_id' => $building->id,
        'sector' => 'Nodo Este',
        'level' => 'Piso 3',
        'is_active' => true,
    ]);
    $unit->services()->create([
        'name' => 'Radiología',
        'door_id' => 'B-301',
        'attention_hours' => '08:00 - 14:00',
        'results_hours' => null,
        'contact_role' => 'Recepción',
        'contact_extension' => '2100',
        'contact_email' => 'radiologia@css.gob.pa',
    ]);

    Livewire::test(UpsertDirectoryUnit::class)
        ->set('form.building_id', $building->id)
        ->set('form.sector', 'Nodo Este')
        ->set('form.level', 'Piso 3')
        ->call('onLevelChange')
        ->assertSet('form.services.0.name', 'Radiología')
        ->assertSet('form.services.0.contact_role', 'Recepción')
        ->assertSet('form.services.0.contact_extension', '2100');
});

test('rechaza un formato de horario de atención inválido', function () {
    $this->actingAs($this->supervisor);

    Livewire::test(UpsertDirectoryUnit::class)
        ->set('form.new_building', 'Centro de Salud Los Andes')
        ->set('form.director_name', 'Dr. Test')
        ->set('form.administrator_name', 'Lic. Test')
        ->set('form.new_level', 'Piso 1')
        ->set('form.services', [
            [
                'name' => 'Laboratorio',
                'door_id' => null,
                'attention_hours' => '7am-3pm',
                'results_hours' => null,
                'contact_role' => 'Recepción',
                'contact_extension' => '1100',
                'contact_email' => null,
            ],
        ])
        ->call('save')
        ->assertHasErrors('form.services.0.attention_hours');

    $this->assertDatabaseCount('directory_buildings', 0);
});

test('rechaza una extensión telefónica no numérica en el servicio', function () {
    $this->actingAs($this->supervisor);

    Livewire::test(UpsertDirectoryUnit::class)
        ->set('form.new_building', 'Policlínica San Miguel')
        ->set('form.director_name', 'Dr. Test')
        ->set('form.administrator_name', 'Lic. Test')
        ->set('form.new_level', 'Piso 1')
        ->set('form.services', [
            [
                'name' => 'Citas',
                'door_id' => null,
                'attention_hours' => '07:00 - 15:00',
                'results_hours' => null,
                'contact_role' => 'Citas',
                'contact_extension' => 'abc123',
                'contact_email' => null,
            ],
        ])
        ->call('save')
        ->assertHasErrors('form.services.0.contact_extension');
});

test('rechaza un piso duplicado en el mismo edificio y sector', function () {
    $this->actingAs($this->supervisor);

    $building = Building::create([
        'name' => 'Complejo Hospitalario',
        'director_name' => 'Dra. Test',
        'administrator_name' => 'Lic. Test',
        'is_active' => true,
    ]);

    $existing = Unit::create([
        'building_id' => $building->id,
        'sector' => 'Nodo Este',
        'level' => 'Piso 3',
        'is_active' => true,
    ]);

    Livewire::test(UpsertDirectoryUnit::class)
        ->set('form.building_id', $building->id)
        ->set('form.sector', 'Nodo Este')
        ->set('form.new_level', 'Piso 3')
        ->set('form.services', [
            [
                'name' => 'Recepción',
                'door_id' => null,
                'attention_hours' => '07:00 - 15:00',
                'results_hours' => null,
                'contact_role' => 'Recepción',
                'contact_extension' => '2200',
                'contact_email' => null,
            ],
        ])
        ->call('save')
        ->assertHasErrors('form.level');

    $this->assertDatabaseCount('directory_units', 1);
    expect($existing->exists())->toBeTrue();
});

test('sugiere valores existentes del sector para homogenizar', function () {
    $this->actingAs($this->supervisor);

    $building = Building::create([
        'name' => 'Hospital del Niño',
        'director_name' => 'Dra. Test',
        'administrator_name' => 'Lic. Test',
        'is_active' => true,
    ]);

    Unit::create(['building_id' => $building->id, 'sector' => 'Nodo Norte', 'level' => 'Piso 1']);
    Unit::create(['building_id' => $building->id, 'sector' => 'Nodo Sur', 'level' => 'Piso 2']);

    Livewire::test(UpsertDirectoryUnit::class)
        ->set('form.building_id', $building->id)
        ->assertSee('Nodo Norte')
        ->assertSee('Nodo Sur');
});

test('al agregar un servicio, el nuevo card aparece encima del anterior', function () {
    $this->actingAs($this->supervisor);

    Livewire::test(UpsertDirectoryUnit::class)
        ->call('addService')
        ->set('form.services.0.name', 'Primero')
        ->call('addService')
        ->set('form.services.0.name', 'Segundo')
        ->assertCount('form.services', 2)
        ->assertSet('form.services.0.name', 'Segundo')
        ->assertSet('form.services.1.name', 'Primero');
});

test('el listado busca por nombre de servicio', function () {
    $this->actingAs($this->supervisor);

    $buildingA = Building::create([
        'name' => 'Hospital A',
        'director_name' => 'Dra. Test',
        'administrator_name' => 'Lic. Test',
        'is_active' => true,
    ]);
    $unitA = Unit::create([
        'building_id' => $buildingA->id,
        'sector' => 'Nodo Norte',
        'level' => 'Piso 1',
        'is_active' => true,
    ]);
    $unitA->services()->create([
        'name' => 'Cardiología',
        'door_id' => 'C-101',
        'attention_hours' => '07:00 - 15:00',
        'results_hours' => null,
        'contact_role' => 'Citas',
        'contact_extension' => '1111',
        'contact_email' => null,
    ]);

    $buildingB = Building::create([
        'name' => 'Hospital B',
        'director_name' => 'Dra. Test',
        'administrator_name' => 'Lic. Test',
        'is_active' => true,
    ]);
    Unit::create([
        'building_id' => $buildingB->id,
        'sector' => 'Nodo Sur',
        'level' => 'Piso 2',
        'is_active' => true,
    ]);

    Livewire::test(ManageDirectoryUnits::class)
        ->set('search', 'Cardiología')
        ->assertSee('Hospital A')
        ->assertDontSee('Hospital B');
});

test('un operador sin directory.manage no accede a las rutas del directorio', function () {
    $this->actingAs($this->operator);

    $this->get(route('directory.index'))->assertForbidden();
    $this->get(route('directory.create'))->assertForbidden();
});

test('un artículo con unidad muestra la ficha de contacto en el detalle', function () {
    $this->actingAs($this->supervisor);

    $category = KnowledgeCategory::create(['name' => 'Procedimientos']);

    $building = Building::create([
        'name' => 'Policlínica Betania',
        'director_name' => 'Dra. Carla Vega',
        'administrator_name' => 'Lic. Marcos Díaz',
        'is_active' => true,
    ]);

    $unit = Unit::create([
        'building_id' => $building->id,
        'sector' => 'Nodo Oeste',
        'level' => 'Piso 1',
        'is_active' => true,
    ]);

    $unit->services()->create([
        'name' => 'Citas de Policlínica',
        'door_id' => 'D-104',
        'attention_hours' => '07:00 - 15:00',
        'results_hours' => null,
        'contact_role' => 'Citas',
        'contact_extension' => '5123',
        'contact_email' => 'citas-betania@css.gob.pa',
    ]);

    $article = KnowledgeArticle::create([
        'title' => 'Cancelación de citas en Policlínica Betania',
        'slug' => 'cancelacion-betania',
        'content' => '<p>Procedimiento de cancelación.</p>',
        'category_id' => $category->id,
        'directory_unit_id' => $unit->id,
        'status' => 'published',
        'published_at' => now(),
        'created_by' => $this->supervisor->id,
    ]);

    $response = $this->get(route('knowledge.show', $article->slug));

    $response->assertOk();
    $response->assertSee('Ficha de Contacto');
    $response->assertSee('SERVICIO: Citas de Policlínica');
    $response->assertSee('Policlínica Betania');
    $response->assertSee('Dra. Carla Vega');
    $response->assertSee('D-104');
    $response->assertSee('citas-betania@css.gob.pa');
    $response->assertSee('5123');
});

test('un artículo sin unidad no muestra la ficha de contacto', function () {
    $this->actingAs($this->operator);

    $article = KnowledgeArticle::create([
        'title' => 'Artículo sin ficha',
        'slug' => 'articulo-sin-ficha',
        'content' => '<p>Contenido.</p>',
        'status' => 'published',
        'published_at' => now(),
        'created_by' => $this->supervisor->id,
    ]);

    $this->get(route('knowledge.show', $article->slug))
        ->assertOk()
        ->assertDontSee('Ficha de Contacto');
});
