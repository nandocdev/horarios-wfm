# Permisos del Sistema — WFM Call Center CSS

## Convenciones

### Nomenclatura
{modulo}.{recurso}.{accion}

### Acciones estándar
| Acción       | Descripción                                      |
|--------------|--------------------------------------------------|
| `viewAny`    | Listar / ver índice del recurso                  |
| `view`       | Ver detalle de un registro                       |
| `create`     | Crear nuevo registro                             |
| `update`     | Editar registro existente                        |
| `delete`     | Eliminar / desactivar registro (soft delete)     |
| `export`     | Exportar datos (CSV / Excel)                     |
| `forceApply` | Ejecutar acción fuera del flujo estándar         |
| `approve`    | Aprobar solicitud dentro del flujo               |
| `reject`     | Rechazar solicitud dentro del flujo              |

### Roles del sistema
| Código       | Nombre                  | `hierarchy_level` |
|--------------|-------------------------|-------------------|
| `operator`   | Operador                | 1                 |
| `supervisor` | Supervisor (Operador II)| 2                 |
| `coordinator`| Coordinador             | 3                 |
| `chief`      | Jefe                    | 4                 |
| `wfm`        | Analista Workforce      | 5 (transversal)   |
| `director`   | Director                | 6                 |
| `admin`      | Administrador           | 99                |

---

## Matriz de Permisos

### Módulo: Auth & Profile

| Permiso                        | operator | supervisor | coordinator | chief | wfm | director | admin |
|-------------------------------|:--------:|:----------:|:-----------:|:-----:|:---:|:--------:|:-----:|
| `profile.view`                | ✅       | ✅         | ✅          | ✅    | ✅  | ✅       | ✅    |
| `profile.update`              | ✅       | ✅         | ✅          | ✅    | ✅  | ✅       | ✅    |
| `notifications.viewAny`       | ✅       | ✅         | ✅          | ✅    | ✅  | ✅       | ✅    |

---

### Módulo: Security & Users

| Permiso                        | operator | supervisor | coordinator | chief | wfm | director | admin |
|-------------------------------|:--------:|:----------:|:-----------:|:-----:|:---:|:--------:|:-----:|
| `users.viewAny`               | ❌       | ❌         | ❌          | ❌    | ❌  | ❌       | ✅    |
| `users.view`                  | ❌       | ❌         | ❌          | ❌    | ❌  | ❌       | ✅    |
| `users.create`                | ❌       | ❌         | ❌          | ❌    | ❌  | ❌       | ✅    |
| `users.update`                | ❌       | ❌         | ❌          | ❌    | ❌  | ❌       | ✅    |
| `users.delete`                | ❌       | ❌         | ❌          | ❌    | ❌  | ❌       | ✅    |
| `roles.viewAny`               | ❌       | ❌         | ❌          | ❌    | ❌  | ❌       | ✅    |
| `roles.assign`                | ❌       | ❌         | ❌          | ❌    | ❌  | ❌       | ✅    |
| `permissions.viewAny`         | ❌       | ❌         | ❌          | ❌    | ❌  | ❌       | ✅    |
| `permissions.assign`          | ❌       | ❌         | ❌          | ❌    | ❌  | ❌       | ✅    |

---

### Módulo: Organization & Corporate

| Permiso                        | operator | supervisor | coordinator | chief | wfm | director | admin |
|-------------------------------|:--------:|:----------:|:-----------:|:-----:|:---:|:--------:|:-----:|
| `directorates.viewAny`        | ❌       | ❌         | ❌          | ✅    | ✅  | ✅       | ✅    |
| `directorates.create`         | ❌       | ❌         | ❌          | ❌    | ❌  | ❌       | ✅    |
| `directorates.update`         | ❌       | ❌         | ❌          | ❌    | ❌  | ❌       | ✅    |
| `departments.viewAny`         | ❌       | ❌         | ❌          | ✅    | ✅  | ✅       | ✅    |
| `departments.create`          | ❌       | ❌         | ❌          | ❌    | ❌  | ❌       | ✅    |
| `departments.update`          | ❌       | ❌         | ❌          | ❌    | ❌  | ❌       | ✅    |
| `positions.viewAny`           | ❌       | ❌         | ❌          | ✅    | ✅  | ✅       | ✅    |
| `positions.create`            | ❌       | ❌         | ❌          | ❌    | ❌  | ❌       | ✅    |
| `positions.update`            | ❌       | ❌         | ❌          | ❌    | ❌  | ❌       | ✅    |
| `hierarchy.viewAny`           | ❌       | ❌         | ❌          | ✅    | ✅  | ✅       | ✅    |

---

### Módulo: Employee & Welfare

| Permiso                        | operator | supervisor | coordinator | chief | wfm | director | admin |
|-------------------------------|:--------:|:----------:|:-----------:|:-----:|:---:|:--------:|:-----:|
| `employees.viewAny`           | ❌       | ❌         | ✅          | ✅    | ✅  | ✅       | ✅    |
| `employees.view`              | ❌       | ❌         | ✅          | ✅    | ✅  | ✅       | ✅    |
| `employees.create`            | ❌       | ❌         | ❌          | ❌    | ❌  | ❌       | ✅    |
| `employees.update`            | ❌       | ❌         | ❌          | ❌    | ❌  | ❌       | ✅    |
| `employees.import`            | ❌       | ❌         | ❌          | ❌    | ❌  | ❌       | ✅    |
| `employees.welfare.view`      | ❌       | ❌         | ✅          | ✅    | ✅  | ❌       | ✅    |
| `employees.welfare.manage`    | ❌       | ❌         | ❌          | ❌    | ❌  | ❌       | ✅    |
| `employees.own.view`          | ✅       | ✅         | ✅          | ✅    | ✅  | ✅       | ✅    |
| `employees.own.welfare.view`  | ✅       | ✅         | ✅          | ✅    | ✅  | ✅       | ✅    |

---

### Módulo: Team Management

| Permiso                        | operator | supervisor | coordinator | chief | wfm | director | admin |
|-------------------------------|:--------:|:----------:|:-----------:|:-----:|:---:|:--------:|:-----:|
| `teams.viewAny`               | ❌       | ❌         | ✅          | ✅    | ✅  | ✅       | ✅    |
| `teams.create`                | ❌       | ❌         | ❌          | ❌    | ❌  | ❌       | ✅    |
| `teams.update`                | ❌       | ❌         | ❌          | ❌    | ❌  | ❌       | ✅    |
| `teams.members.viewAny`       | ❌       | ✅         | ✅          | ✅    | ✅  | ✅       | ✅    |
| `teams.members.manage`        | ❌       | ❌         | ❌          | ❌    | ❌  | ❌       | ✅    |

---

### Módulo: Schedule Engine

| Permiso                        | operator | supervisor | coordinator | chief | wfm | director | admin |
|-------------------------------|:--------:|:----------:|:-----------:|:-----:|:---:|:--------:|:-----:|
| `schedules.viewAny`           | ❌       | ❌         | ✅          | ✅    | ✅  | ✅       | ✅    |
| `schedules.create`            | ❌       | ❌         | ❌          | ❌    | ✅  | ❌       | ✅    |
| `schedules.update`            | ❌       | ❌         | ❌          | ❌    | ✅  | ❌       | ✅    |
| `break_templates.viewAny`     | ❌       | ❌         | ✅          | ✅    | ✅  | ❌       | ✅    |
| `break_templates.create`      | ❌       | ❌         | ❌          | ❌    | ✅  | ❌       | ✅    |
| `break_templates.update`      | ❌       | ❌         | ❌          | ❌    | ✅  | ❌       | ✅    |
| `wfm.config.manage`           | ❌       | ❌         | ❌          | ❌    | ✅  | ❌       | ✅    |

---

### Módulo: Planning (Semanal e Intradía)

| Permiso                              | operator | supervisor | coordinator | chief | wfm | director | admin |
|-------------------------------------|:--------:|:----------:|:-----------:|:-----:|:---:|:--------:|:-----:|
| `weekly_schedules.viewAny`          | ❌       | ❌         | ✅          | ✅    | ✅  | ✅       | ✅    |
| `weekly_schedules.create`           | ❌       | ❌         | ❌          | ❌    | ✅  | ❌       | ❌    |
| `weekly_schedules.update`           | ❌       | ❌         | ❌          | ❌    | ✅  | ❌       | ❌    |
| `weekly_schedules.publish`          | ❌       | ❌         | ❌          | ❌    | ✅  | ❌       | ❌    |
| `weekly_schedules.team.view`        | ❌       | ✅         | ✅          | ✅    | ✅  | ✅       | ✅    |
| `weekly_schedules.own.view`         | ✅       | ✅         | ✅          | ✅    | ✅  | ✅       | ✅    |
| `intraday_activities.viewAny`       | ❌       | ✅         | ✅          | ✅    | ✅  | ✅       | ✅    |
| `intraday_activities.create`        | ❌       | ❌         | ❌          | ❌    | ✅  | ❌       | ❌    |
| `intraday_activities.update`        | ❌       | ❌         | ❌          | ❌    | ✅  | ❌       | ❌    |
| `intraday_activities.assign`        | ❌       | ❌         | ❌          | ❌    | ✅  | ❌       | ❌    |
| `intraday_activities.own.view`      | ✅       | ✅         | ✅          | ✅    | ✅  | ✅       | ✅    |
| `break_overrides.create`            | ❌       | ❌         | ✅          | ❌    | ✅  | ❌       | ❌    |

---

### Módulo: Attendance & Incidents

| Permiso                              | operator | supervisor | coordinator | chief | wfm | director | admin |
|-------------------------------------|:--------:|:----------:|:-----------:|:-----:|:---:|:--------:|:-----:|
| `attendance_incidents.viewAny`      | ❌       | ❌         | ✅          | ✅    | ✅  | ✅       | ✅    |
| `attendance_incidents.create`       | ❌       | ❌         | ✅          | ❌    | ✅  | ❌       | ✅    |
| `attendance_incidents.update`       | ❌       | ❌         | ✅          | ❌    | ✅  | ❌       | ✅    |
| `attendance_incidents.own.view`     | ✅       | ✅         | ✅          | ✅    | ✅  | ✅       | ✅    |
| `incidents.escalate`                | ❌       | ✅         | ❌          | ❌    | ❌  | ❌       | ❌    |

---

### Módulo: Workflow — Permisos y Excepciones

| Permiso                              | operator | supervisor | coordinator | chief | wfm | director | admin |
|-------------------------------------|:--------:|:----------:|:-----------:|:-----:|:---:|:--------:|:-----:|
| `leave_requests.create`             | ✅       | ✅         | ✅          | ✅    | ✅  | ✅       | ❌    |
| `leave_requests.view.own`           | ✅       | ✅         | ✅          | ✅    | ✅  | ✅       | ❌    |
| `leave_requests.viewAny.team`       | ❌       | ❌         | ✅          | ✅    | ✅  | ✅       | ✅    |
| `leave_requests.approve`            | ❌       | ❌         | ✅          | ✅    | ✅  | ✅       | ❌    |
| `leave_requests.reject`             | ❌       | ❌         | ✅          | ✅    | ✅  | ✅       | ❌    |
| `leave_requests.forceApply`         | ❌       | ❌         | ❌          | ❌    | ✅  | ❌       | ❌    |
| `exceptions.create.direct`          | ❌       | ❌         | ✅          | ✅    | ✅  | ❌       | ❌    |
| `exceptions.create.bulk`            | ❌       | ❌         | ❌          | ❌    | ✅  | ❌       | ❌    |
| `exceptions.forceApprove`           | ❌       | ❌         | ❌          | ❌    | ✅  | ❌       | ❌    |
| `exceptions.authorizeSpecial`       | ❌       | ❌         | ❌          | ✅    | ❌  | ✅       | ❌    |

---

### Módulo: Workflow — Cambios de Turno

| Permiso                              | operator | supervisor | coordinator | chief | wfm | director | admin |
|-------------------------------------|:--------:|:----------:|:-----------:|:-----:|:---:|:--------:|:-----:|
| `shift_swaps.create`                | ✅       | ✅         | ✅          | ❌    | ❌  | ❌       | ❌    |
| `shift_swaps.respond`               | ✅       | ✅         | ✅          | ❌    | ❌  | ❌       | ❌    |
| `shift_swaps.viewAny.team`          | ❌       | ❌         | ✅          | ✅    | ✅  | ✅       | ✅    |
| `shift_swaps.approve`               | ❌       | ❌         | ✅          | ❌    | ✅  | ❌       | ❌    |
| `shift_swaps.reject`                | ❌       | ❌         | ✅          | ❌    | ✅  | ❌       | ❌    |

---

### Módulo: Analytics & Reporting

| Permiso                              | operator | supervisor | coordinator | chief | wfm | director | admin |
|-------------------------------------|:--------:|:----------:|:-----------:|:-----:|:---:|:--------:|:-----:|
| `reports.team.view`                 | ❌       | ❌         | ✅          | ✅    | ✅  | ✅       | ✅    |
| `reports.global.view`               | ❌       | ❌         | ❌          | ✅    | ✅  | ✅       | ✅    |
| `reports.export`                    | ❌       | ❌         | ❌          | ✅    | ✅  | ✅       | ✅    |
| `dashboard.kpi.view`                | ❌       | ❌         | ❌          | ✅    | ✅  | ✅       | ✅    |

---

### Módulo: Administration & Audit

| Permiso                              | operator | supervisor | coordinator | chief | wfm | director | admin |
|-------------------------------------|:--------:|:----------:|:-----------:|:-----:|:---:|:--------:|:-----:|
| `audit_logs.viewAny`                | ❌       | ❌         | ❌          | ❌    | ❌  | ❌       | ✅    |
| `system.reprocess`                  | ❌       | ❌         | ❌          | ❌    | ❌  | ❌       | ✅    |

---

## Seeder de referencia
```php
<?php
// database/seeders/RolesAndPermissionsSeeder.php
declare(strict_types=1);

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

$permissions = [

    // Auth & Profile
    'profile.view',
    'profile.update',
    'notifications.viewAny',

    // Security & Users
    'users.viewAny', 'users.view', 'users.create', 'users.update', 'users.delete',
    'roles.viewAny', 'roles.assign',
    'permissions.viewAny', 'permissions.assign',

    // Organization
    'directorates.viewAny', 'directorates.create', 'directorates.update',
    'departments.viewAny',  'departments.create',  'departments.update',
    'positions.viewAny',    'positions.create',    'positions.update',
    'hierarchy.viewAny',

    // Employee & Welfare
    'employees.viewAny', 'employees.view', 'employees.create',
    'employees.update',  'employees.import',
    'employees.welfare.view', 'employees.welfare.manage',
    'employees.own.view', 'employees.own.welfare.view',

    // Team Management
    'teams.viewAny', 'teams.create', 'teams.update',
    'teams.members.viewAny', 'teams.members.manage',

    // Schedule Engine
    'schedules.viewAny', 'schedules.create', 'schedules.update',
    'break_templates.viewAny', 'break_templates.create', 'break_templates.update',
    'wfm.config.manage',

    // Planning
    'weekly_schedules.viewAny', 'weekly_schedules.create',
    'weekly_schedules.update',  'weekly_schedules.publish',
    'weekly_schedules.team.view', 'weekly_schedules.own.view',
    'intraday_activities.viewAny', 'intraday_activities.create',
    'intraday_activities.update',  'intraday_activities.assign',
    'intraday_activities.own.view',
    'break_overrides.create',

    // Attendance
    'attendance_incidents.viewAny', 'attendance_incidents.create',
    'attendance_incidents.update',  'attendance_incidents.own.view',
    'incidents.escalate',

    // Workflow — Permisos
    'leave_requests.create',       'leave_requests.view.own',
    'leave_requests.viewAny.team', 'leave_requests.approve',
    'leave_requests.reject',       'leave_requests.forceApply',
    'exceptions.create.direct',    'exceptions.create.bulk',
    'exceptions.forceApprove',     'exceptions.authorizeSpecial',

    // Workflow — Cambios de Turno
    'shift_swaps.create',        'shift_swaps.respond',
    'shift_swaps.viewAny.team',  'shift_swaps.approve',
    'shift_swaps.reject',

    // Analytics
    'reports.team.view', 'reports.global.view',
    'reports.export',    'dashboard.kpi.view',

    // Administration
    'audit_logs.viewAny', 'system.reprocess',
];

foreach ($permissions as $permission) {
    Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
}

$matrix = [
    'operator' => [
        'profile.view', 'profile.update', 'notifications.viewAny',
        'employees.own.view', 'employees.own.welfare.view',
        'weekly_schedules.own.view', 'intraday_activities.own.view',
        'attendance_incidents.own.view',
        'leave_requests.create', 'leave_requests.view.own',
        'shift_swaps.create', 'shift_swaps.respond',
    ],
    'supervisor' => [
        // Hereda operator +
        'teams.members.viewAny',
        'weekly_schedules.team.view', 'intraday_activities.viewAny',
        'incidents.escalate',
    ],
    'coordinator' => [
        // Hereda supervisor +
        'employees.viewAny', 'employees.view',
        'teams.viewAny',
        'schedules.viewAny', 'break_templates.viewAny',
        'weekly_schedules.viewAny', 'break_overrides.create',
        'attendance_incidents.viewAny', 'attendance_incidents.create',
        'attendance_incidents.update',
        'leave_requests.viewAny.team', 'leave_requests.approve',
        'leave_requests.reject',       'exceptions.create.direct',
        'shift_swaps.viewAny.team',    'shift_swaps.approve',
        'shift_swaps.reject',
        'reports.team.view',
        'hierarchy.viewAny',
        'directorates.viewAny', 'departments.viewAny', 'positions.viewAny',
    ],
    'chief' => [
        // Hereda coordinator +
        'directorates.viewAny', 'departments.viewAny', 'positions.viewAny',
        'leave_requests.approve', 'leave_requests.reject',
        'exceptions.create.direct', 'exceptions.authorizeSpecial',
        'reports.global.view', 'reports.export', 'dashboard.kpi.view',
    ],
    'wfm' => [
        // Transversal — no hereda jerarquía
        'profile.view', 'profile.update', 'notifications.viewAny',
        'employees.viewAny', 'employees.view',
        'employees.own.view', 'employees.own.welfare.view',
        'employees.welfare.view',
        'teams.viewAny', 'teams.members.viewAny',
        'hierarchy.viewAny',
        'directorates.viewAny', 'departments.viewAny', 'positions.viewAny',
        'schedules.viewAny',   'schedules.create',   'schedules.update',
        'break_templates.viewAny', 'break_templates.create', 'break_templates.update',
        'wfm.config.manage',
        'weekly_schedules.viewAny', 'weekly_schedules.create',
        'weekly_schedules.update',  'weekly_schedules.publish',
        'weekly_schedules.team.view', 'weekly_schedules.own.view',
        'intraday_activities.viewAny', 'intraday_activities.create',
        'intraday_activities.update',  'intraday_activities.assign',
        'intraday_activities.own.view',
        'break_overrides.create',
        'attendance_incidents.viewAny', 'attendance_incidents.create',
        'attendance_incidents.update',  'attendance_incidents.own.view',
        'leave_requests.viewAny.team',  'leave_requests.approve',
        'leave_requests.reject',        'leave_requests.forceApply',
        'exceptions.create.direct',     'exceptions.create.bulk',
        'exceptions.forceApprove',
        'shift_swaps.viewAny.team', 'shift_swaps.approve', 'shift_swaps.reject',
        'reports.team.view', 'reports.global.view',
        'reports.export',    'dashboard.kpi.view',
    ],
    'director' => [
        // Hereda chief +
        'leave_requests.approve', 'leave_requests.reject',
        'exceptions.authorizeSpecial',
        'reports.global.view', 'reports.export', 'dashboard.kpi.view',
    ],
    'admin' => [
        // Todos los permisos
        // Se asigna con syncPermissions(Permission::all())
    ],
];

foreach ($matrix as $roleName => $perms) {
    $role = Role::firstOrCreate([
        'name'       => $roleName,
        'guard_name' => 'web',
    ]);

    if ($roleName === 'admin') {
        $role->syncPermissions(Permission::all());
    } else {
        $role->syncPermissions($perms);
    }
}
```

---

## Notas de implementación

**Herencia de roles.** Spatie no hereda permisos automáticamente entre roles.
El seeder replica los permisos de niveles inferiores explícitamente en cada rol
superior. Esto es intencional: hace la configuración auditable y evita efectos
colaterales al cambiar permisos de un rol base.

**Permisos `.own` vs. sin sufijo.** Los permisos sin sufijo aplican sobre
cualquier registro del recurso (alcance de equipo o global). Los permisos
`.own` aplican solo sobre el registro del propio usuario autenticado. La
distinción se resuelve en la `Policy` correspondiente, no en el middleware.

**Permisos `.team`** Se resuelven en Policy verificando que
`$employee->team_id === $resource->team_id`. El permiso solo habilita
la capacidad; el alcance lo restringe la Policy.

**Rol WFM (transversal).** No sigue la jerarquía organizacional.
Sus permisos se definen de forma independiente y no deben heredarse
ni modificarse al ajustar permisos de roles jerárquicos.

**Caché de permisos.** Después de ejecutar el seeder, limpiar con:
```bash
php artisan permission:cache-reset
```
