<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\CoreModule\Models\Permission;
use App\Modules\CoreModule\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeder institucional para la gestión de Roles y Permisos.
 * Centraliza la definición de capacidades por módulo.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        config()->set('permission.cache.store', 'array');
        app()->forgetInstance(PermissionRegistrar::class);

        // Limpiar caché de permisos antes de iniciar
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 1. Permisos definidos explícitamente en el código actual
        $permissions = [
            // Core Module
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'roles.view',
            'roles.create',
            'roles.edit',
            'admin.system',

            // Organization Module
            'directorates.viewAny',
            'directorates.create',
            'directorates.update',
            'directorates.delete',
            'departments.viewAny',
            'departments.create',
            'departments.update',
            'departments.delete',
            'teams.viewAny',
            'teams.create',
            'teams.update',
            'teams.delete',
            'teams.members.viewAny',
            'teams.members.manage',
            'positions.viewAny',
            'positions.create',
            'positions.update',
            'positions.delete',

            // (Horarios y Break Templates eliminados aquí, centralizados abajo)

            // Employees Module
            'employees.view',
            'employees.view.others',
            'employees.view.all',
            'employees.create',
            'employees.edit',
            'employees.edit.others',
            'employees.edit.all',
            'employees.delete',
            'employees.delete.others',
            'employees.delete.all',
            'employees.force_delete',
            'employees.force_delete.others',
            'employees.force_delete.all',
            'employees.manageTeamAssignments',
            'employees.export',
            'employees.import',

            // Communications Module - Contenido
            'news.viewAny',
            'news.view',
            'news.create',
            'news.edit',
            'news.delete',
            'shoutouts.manage',
            'react_to_shoutouts',
            'comment_on_news',
            'polls.manage',
            'news.moderate',

            // Communications Module - Gestión
            'communications.manage',
            'communications.moderate',
            'communications.approve',
            'communications.reject',
            'communications.archive',
            'communications.view_pending',

            // Communications Module - Comentarios
            'comments.view',
            'comments.create',
            'comments.edit',
            'comments.delete',
            'comments.restore',
            'comments.force_delete',

            // Contact Center
            'call_records.viewAny',
            'call_records.create',
            'call_records.update',
            'call_queues.manage',
            'channels.manage',
            'case_subtypes.viewAny',
            'case_subtypes.manage',

            // Communications Module - Reacciones
            'reactions.view',
            'reactions.create',
            'reactions.edit',
            'reactions.delete',
            'reactions.restore',
            'reactions.force_delete',

            // Communications Module - Menciones
            'mentions.view',
            'mentions.create',
            'mentions.edit',
            'mentions.delete',
            'mentions.restore',
            'mentions.force_delete',

            // Communications Module - Notificaciones
            'notifications.view',
            'notifications.create',
            'notifications.edit',
            'notifications.delete',
            'notifications.restore',
            'notifications.force_delete',
            // Notificaciones específicas de scheduling
            'notifications.send',
            'leave_requests.notify',
            'shift_swaps.notify',

            // Schedule Module (Refactorizado y Granular)
            'schedules.view_own',
            'schedules.view_team',
            'schedules.view_all',
            'schedules.manage',
            'schedules.swap_request',
            'schedules.leave_request',
            'schedules.justification_upload',
            'schedules.approve_requests',
            'operations.view',
            'operations.manage',
            'requests.view',
            'requests.create',
            'requests.manage',
            'analytics.view',
            'analytics.export',

            // Audit Module
            'audit.view',
            'audit.export',

            // Report Module
            'reports.view',
            'reports.adherence',
            'reports.coverage',
            'reports.sla',
            'reports.scorecard',
            'reports.attendance',
            'reports.compliance',
            'reports.staffing',
            'reports.requests',

            // Documentation Module
            'articles.viewAny',
            'articles.view',
            'articles.manage',

            // Navigation Menu Permissions
            'menu.admin',
            'menu.organization',
            'menu.employees',
            'menu.operations',
            'menu.planning',
            'menu.team',
            'menu.reports',
            'menu.wfm_config',
            'menu.contact_center',
            'menu.communications',

            // Granular WFM / Operation Permissions
            'wfm.realtime.view',
            'wfm.availability.view',
            'wfm.intraday.manage',
            'wfm.swaps.manage',
            'wfm.leaves.manage',
            'wfm.planning.manage',
            'wfm.exceptions.manage',
            'wfm.catalogs.shifts',
            'wfm.catalogs.activities',
            'wfm.catalogs.absences',
            'wfm.catalogs.agent_states',
            'wfm.catalogs.scheduled_defs',
            'wfm.settings.manage',
        ];

        // Registro de permisos
        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        // 2. Definición de Roles base
        $roles = [
            'operator' => ['name' => 'operator', 'code' => 'OP', 'level' => 1],
            'supervisor' => ['name' => 'supervisor', 'code' => 'SUP', 'level' => 2],
            'coordinator' => ['name' => 'coordinator', 'code' => 'COOR', 'level' => 3],
            'chief' => ['name' => 'chief', 'code' => 'JEF', 'level' => 4],
            'wfm' => ['name' => 'wfm', 'code' => 'WFM', 'level' => 5],
            'director' => ['name' => 'director', 'code' => 'DIR', 'level' => 6],
            'admin' => ['name' => 'admin', 'code' => 'ADM', 'level' => 99],
        ];

        foreach ($roles as $roleData) {
            Role::firstOrCreate(
                ['name' => $roleData['name'], 'guard_name' => 'web'],
                [
                    'code' => $roleData['code'],
                    'hierarchy_level' => $roleData['level'],
                ]
            );
        }

        $adminRole = Role::findByName('admin', 'web');
        $adminRole->syncPermissions(Permission::all());

        // 3. Asignación de permisos por rol (basado en definición de accesos 2026)

        // Operator / Supervisor (Autogestión)
        $operatorRole = Role::findByName('operator', 'web');
        $operatorRole->syncPermissions([
            'schedules.view_own',
            'schedules.swap_request',
            'schedules.leave_request',
            'schedules.justification_upload',
            'notifications.view',
            'helpdesk.view',
            'articles.viewAny',
        ]);

        $supervisorRole = Role::findByName('supervisor', 'web');
        $supervisorRole->syncPermissions([
            'schedules.view_own',
            'schedules.swap_request',
            'schedules.leave_request',
            'schedules.justification_upload',
            'notifications.view',
            'notifications.send',
            'helpdesk.view',
            'articles.viewAny',
        ]);

        // Coordinator - Gestión de equipo directo
        $coordinatorRole = Role::findByName('coordinator', 'web');
        $coordinatorRole->syncPermissions([
            'menu.team',
            'menu.operations',
            'menu.reports',
            'wfm.realtime.view',
            'wfm.availability.view',
            'wfm.planning.manage',
            'schedules.view_own',
            'schedules.view_team',
            'schedules.approve_requests',
            'operations.view',
            'operations.manage', // Para documentar incidencias
            'requests.view',
            'requests.manage',
            'reports.scorecard',
            'reports.adherence',
            'reports.coverage',
            'reports.sla',
            'analytics.view',
            'notifications.view',
            'notifications.send',
            'articles.viewAny',
        ]);

        // Chief - Gestión de múltiples equipos
        $chiefRole = Role::findByName('chief', 'web');
        $chiefRole->syncPermissions([
            'menu.team',
            'menu.operations',
            'menu.reports',
            'wfm.realtime.view',
            'wfm.availability.view',
            'schedules.view_own',
            'schedules.view_team',
            'operations.view',
            'requests.view',
            'reports.scorecard',
            'reports.adherence',
            'reports.coverage',
            'reports.sla',
            'reports.attendance',
            'reports.compliance',
            'analytics.view',
            'analytics.export',
            'notifications.view',
            'articles.viewAny',
        ]);

        // Director - Supervisión global
        $directorRole = Role::findByName('director', 'web');
        $directorRole->syncPermissions([
            'menu.team',
            'menu.operations',
            'menu.employees',
            'menu.communications',
            'menu.reports',
            'schedules.view_all',
            'operations.view',
            'reports.scorecard',
            'reports.adherence',
            'reports.coverage',
            'reports.sla',
            'reports.attendance',
            'reports.compliance',
            'analytics.view',
            'analytics.export',
            'news.viewAny',
            'articles.viewAny',
        ]);

        // WFM - Administración Total
        $wfmRole = Role::findByName('wfm', 'web');
        $wfmRole->syncPermissions(Permission::all());

        // Limpiar caché final
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
