<?php

declare(strict_types=1);

use App\Modules\CoreModule\Models\User;
use App\Modules\PersonnelModule\Models\Team;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->user->givePermissionTo('employees.view');

    Team::create(['name' => 'Team Alpha', 'description' => 'Equipo principal', 'is_active' => true]);
    Team::create(['name' => 'Team Beta', 'description' => 'Equipo secundario', 'is_active' => false]);
    Team::create(['name' => 'Team Gamma', 'description' => 'Equipo de prueba', 'is_active' => true]);
    Team::create(['name' => 'Alpha Team', 'description' => 'Otro alpha', 'is_active' => false]);
});

test('search por nombre filtra correctamente', function () {
    $response = $this->actingAs($this->user)->get(route('organization.teams.index', ['search' => 'Alpha']));
    $response->assertOk();
    $response->assertSee('Team Alpha');
    $response->assertSee('Alpha Team');
    $response->assertDontSee('Team Beta');
    $response->assertDontSee('Team Gamma');
});

test('search por descripción filtra correctamente', function () {
    $response = $this->actingAs($this->user)->get(route('organization.teams.index', ['search' => 'principal']));
    $response->assertOk();
    $response->assertSee('Team Alpha');
    $response->assertDontSee('Team Beta');
    $response->assertDontSee('Team Gamma');
    $response->assertDontSee('Alpha Team');
});

test('activeFilter=true muestra solo equipos activos', function () {
    $response = $this->actingAs($this->user)->get(route('organization.teams.index', ['activeFilter' => '1']));
    $response->assertOk();
    $response->assertSee('Team Alpha');
    $response->assertSee('Team Gamma');
    $response->assertDontSee('Team Beta');
    $response->assertDontSee('Alpha Team');
});

test('activeFilter=false muestra solo equipos inactivos', function () {
    $response = $this->actingAs($this->user)->get(route('organization.teams.index', ['activeFilter' => '0']));
    $response->assertOk();
    $response->assertSee('Team Beta');
    $response->assertSee('Alpha Team');
    $response->assertDontSee('Team Alpha');
    $response->assertDontSee('Team Gamma');
});

test('search + activeFilter=true combinados funcionan correctamente', function () {
    // Busca "Alpha" Y active=true -> solo "Team Alpha"
    $response = $this->actingAs($this->user)->get(route('organization.teams.index', [
        'search' => 'Alpha',
        'activeFilter' => '1',
    ]));
    $response->assertOk();
    $response->assertSee('Team Alpha');
    $response->assertDontSee('Alpha Team'); // inactivo
    $response->assertDontSee('Team Beta');
    $response->assertDontSee('Team Gamma');
});

test('search + activeFilter=false combinados funcionan correctamente', function () {
    // Busca "Alpha" Y active=false -> solo "Alpha Team"
    $response = $this->actingAs($this->user)->get(route('organization.teams.index', [
        'search' => 'Alpha',
        'activeFilter' => '0',
    ]));
    $response->assertOk();
    $response->assertSee('Alpha Team');
    $response->assertDontSee('Team Alpha'); // activo
    $response->assertDontSee('Team Beta');
    $response->assertDontSee('Team Gamma');
});

test('search sin resultados retorna vacío', function () {
    $response = $this->actingAs($this->user)->get(route('organization.teams.index', ['search' => 'NoExiste']));
    $response->assertOk();
    $response->assertDontSee('Team Alpha');
    $response->assertDontSee('Team Beta');
    $response->assertDontSee('Team Gamma');
    $response->assertDontSee('Alpha Team');
});
