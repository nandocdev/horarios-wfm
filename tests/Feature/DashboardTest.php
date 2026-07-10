<?php

declare(strict_types=1);

use App\Modules\CoreModule\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('authenticated users see the operator dashboard sections', function () {
    $user = User::factory()->create(['name' => 'Fernando López']);
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk()
        ->assertSee('Cobertura durante el día')
        ->assertSee('Colas')
        ->assertSee('Alertas Operativas');
});
