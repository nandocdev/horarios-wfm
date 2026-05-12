<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Database\Seeders;

use App\Modules\CoreModule\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

/**
 * Seeder para los permisos institucionales del módulo de comunicaciones.
 */
class CommunicationsPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Permisos existentes de noticias
            'news.viewAny',
            'news.view',
            'news.create',
            'news.edit',
            'news.delete',
            'shoutouts.manage',
            'react_to_shoutouts',
            'comment_on_news',
            'polls.manage',

            // Permisos de gestión de comunicaciones (categorías, tags)
            'communications.manage',

            // Permisos de moderación de contenido
            'communications.moderate',
            'communications.approve',
            'communications.reject',
            'communications.archive',
            'communications.view_pending',

            // Permisos de comentarios en noticias
            'comments.view',
            'comments.create',
            'comments.edit',
            'comments.delete',
            'comments.restore',
            'comments.force_delete',

            // Permisos de reacciones en shoutouts
            'reactions.view',
            'reactions.create',
            'reactions.edit',
            'reactions.delete',
            'reactions.restore',
            'reactions.force_delete',

            // Permisos de menciones
            'mentions.view',
            'mentions.create',
            'mentions.edit',
            'mentions.delete',
            'mentions.restore',
            'mentions.force_delete',

            // Permisos de notificaciones
            'notifications.view',
            'notifications.create',
            'notifications.edit',
            'notifications.delete',
            'notifications.restore',
            'notifications.force_delete',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // Asignar al rol Admin si existe
        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo($permissions);
        }

        // Asignar permisos sociales a roles específicos
        $rolesPermissions = [
            'supervisor' => ['comments.view', 'comments.create', 'reactions.view', 'reactions.create', 'mentions.view', 'notifications.view'],
            'coordinator' => ['comments.view', 'comments.create', 'comments.edit', 'reactions.view', 'reactions.create', 'mentions.view', 'notifications.view'],
            'chief' => ['comments.view', 'comments.create', 'comments.edit', 'comments.delete', 'reactions.view', 'reactions.create', 'reactions.edit', 'mentions.view', 'mentions.edit', 'notifications.view', 'notifications.edit'],
            'wfm' => array_merge($permissions, ['comments.view', 'comments.create', 'comments.edit', 'comments.delete', 'reactions.view', 'reactions.create', 'reactions.edit', 'reactions.delete', 'mentions.view', 'mentions.edit', 'mentions.delete', 'notifications.view', 'notifications.edit', 'notifications.delete']),
            'director' => array_merge($permissions, ['comments.restore', 'comments.force_delete', 'reactions.restore', 'reactions.force_delete', 'mentions.restore', 'mentions.force_delete', 'notifications.restore', 'notifications.force_delete']),
            'admin' => $permissions,
        ];

        foreach ($rolesPermissions as $roleName => $rolePermissions) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo($rolePermissions);
            }
        }
    }
}
