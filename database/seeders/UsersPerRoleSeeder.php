<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\CoreModule\Models\Role;
use App\Modules\CoreModule\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

class UsersPerRoleSeeder extends Seeder
{
    /**
     * Seed one user per defined role.
     */
    public function run(): void
    {
        config()->set('permission.cache.store', 'array');
        app()->forgetInstance(PermissionRegistrar::class);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $definedRoles = [
            'operator' => 'Operador',
            'supervisor' => 'Supervisor',
            'coordinator' => 'Coordinador',
            'chief' => 'Jefe',
            'wfm' => 'Analista Workforce',
            'director' => 'Director',
            'admin' => 'Administrador',
        ];

        foreach ($definedRoles as $roleCode => $roleLabel) {
            $role = Role::query()->firstOrCreate([
                'name' => $roleCode,
                'guard_name' => 'web',
            ]);

            $email = sprintf('%s@wfm.local', $roleCode);

            $user = User::withTrashed()->where('email', $email)->first();

            if ($user === null) {
                $user = User::query()->create([
                    'name' => sprintf('%s Demo', $roleLabel),
                    'email' => $email,
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'is_active' => true,
                    'force_password_change' => true,
                ]);
            } else {
                if ($user->trashed()) {
                    $user->restore();
                }

                $user->fill([
                    'name' => sprintf('%s Demo', $roleLabel),
                    'password' => Hash::make('password'),
                    'email_verified_at' => $user->email_verified_at ?? now(),
                    'is_active' => true,
                    'force_password_change' => true,
                ]);

                $user->save();
            }

            $user->syncRoles([$role->name]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
