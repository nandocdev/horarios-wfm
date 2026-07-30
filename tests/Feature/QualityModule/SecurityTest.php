<?php

declare(strict_types=1);

use App\Modules\CoreModule\Models\User;
use Spatie\Permission\Models\Permission;

it('redirects guests from quality module routes', function () {
    $response = $this->get('/quality/evaluaciones');

    $response->assertStatus(302);
    $response->assertRedirect('/login');
});

it('allows authenticated users to access quality routes', function () {
    // Needs permission 'quality.evaluations.view'
    Permission::firstOrCreate(['name' => 'quality.evaluations.view']);

    $user = User::factory()->create();
    $user->givePermissionTo('quality.evaluations.view');
    $user->email_verified_at = now();
    $user->save();

    $response = $this->actingAs($user)->get('/quality/evaluaciones');

    expect($response->status())->toBeIn([200]);
});
