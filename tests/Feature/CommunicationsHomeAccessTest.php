<?php

declare(strict_types=1);

use App\Modules\CoreModule\Models\User;

it('renders communications home in guest read only mode', function () {
    $response = $this->get(route('home'));

    $response->assertOk()
        ->assertSee('Noticias Internas')
        ->assertSee('Oficial de Comunicaciones')
        ->assertSee('Comunidad');
});

it('renders communications home for authenticated users without guest call to action', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('home'));

    $response->assertOk()
        ->assertSee('Noticias Internas')
        ->assertSee('Comunidad');
});
