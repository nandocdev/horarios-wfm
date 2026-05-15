<?php

declare(strict_types=1);

use App\Modules\PersonnelModule\Models\District;
use App\Modules\PersonnelModule\Models\Province;
use App\Modules\PersonnelModule\Models\Township;

it('muestra catalogo de ubicaciones con provincias, distritos y corregimientos', function () {
    $admin = \App\Modules\CoreModule\Models\User::factory()->create();
    $admin->assignRole('admin');
    
    $province = Province::create(['name' => 'Panamá']);
    $district = District::create(['province_id' => $province->id, 'name' => 'Panamá Centro']);
    $township = Township::create(['district_id' => $district->id, 'name' => 'Calidonia']);

    $response = $this->actingAs($admin)->get('/location');

    $response->assertOk();
    $response->assertViewIs('personnel::location_index');
    $response->assertViewHas('provinces');

    $loadedProvinces = $response->viewData('provinces');
    expect($loadedProvinces->first()->name)->toBe('Panamá');
    expect($loadedProvinces->first()->districts->first()->name)->toBe('Panamá Centro');
    expect($loadedProvinces->first()->districts->first()->townships->first()->name)->toBe('Calidonia');
});

it('devuelve provincias en JSON', function () {
    $admin = \App\Modules\CoreModule\Models\User::factory()->create();
    $admin->assignRole('admin');
    
    Province::create(['name' => 'Chiriquí']);

    $response = $this->actingAs($admin)->getJson('/location/provinces');

    $response->assertOk();
    $response->assertJsonFragment(['name' => 'Chiriquí']);
});

it('devuelve distritos de una provincia en JSON', function () {
    $admin = \App\Modules\CoreModule\Models\User::factory()->create();
    $admin->assignRole('admin');
    
    $province = Province::create(['name' => 'Coclé']);
    District::create(['province_id' => $province->id, 'name' => 'Antón']);

    $response = $this->actingAs($admin)->getJson('/location/districts/'.$province->id);

    $response->assertOk();
    $response->assertJsonCount(1);
    $response->assertJsonFragment(['name' => 'Antón']);
});

it('devuelve corregimientos de un distrito en JSON', function () {
    $admin = \App\Modules\CoreModule\Models\User::factory()->create();
    $admin->assignRole('admin');
    
    $province = Province::create(['name' => 'Veraguas']);
    $district = District::create(['province_id' => $province->id, 'name' => 'Santiago']);
    Township::create(['district_id' => $district->id, 'name' => 'La Peña']);

    $response = $this->actingAs($admin)->getJson('/location/townships/'.$district->id);

    $response->assertOk();
    $response->assertJsonFragment(['name' => 'La Peña']);
});
