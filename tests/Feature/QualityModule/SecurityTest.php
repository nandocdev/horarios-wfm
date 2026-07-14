<?php

declare(strict_types=1);

use App\Modules\CoreModule\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects guests from quality module routes', function () {
    $response = $this->get('/quality/evaluaciones');
    
    $response->assertStatus(302);
    $response->assertRedirect('/login');
});

it('allows authenticated users to access quality routes', function () {
    // Needs permission 'quality.evaluations.view'
    \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'quality.evaluations.view']);
    
    $user = User::factory()->create();
    $user->givePermissionTo('quality.evaluations.view');
    $user->email_verified_at = now();
    $user->save();
    
    $response = $this->actingAs($user)->get('/quality/evaluaciones');
    
    expect($response->status())->toBeIn([200]); 
});
