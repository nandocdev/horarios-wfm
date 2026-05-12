<?php

declare(strict_types=1);

use App\Modules\CoreModule\Models\User;

it('redirects forced change password users to security settings before accessing the app', function () {
    $user = User::factory()->create([
        'force_password_change' => true,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('security.edit'));
});

it('allows users who have completed required password change to access dashboard', function () {
    $user = User::factory()->create([
        'force_password_change' => false,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});
