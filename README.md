# WFM — Sistema de Gestión de Horarios · Call Center CSS

> Workforce Management para el Call Center de la Caja de Seguro Social de Panamá.
> Monolito modular Laravel que cubre planificación semanal, control de asistencia, permisos y cambios de turno.

[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php&logoColor=white)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-4169E1?logo=postgresql&logoColor=white)](https://postgresql.org)
[![License](https://img.shields.io/badge/License-Proprietary-lightgrey)](#licencia)

---

## Tabla de contenidos

- [Contexto](#contexto)
- [Arquitectura](#arquitectura)
- [Módulos](#módulos)
- [Requisitos](#requisitos)
- [Instalación](#instalación)
- [Configuración](#configuración)
- [Base de datos](#base-de-datos)
- [Roles y permisos](#roles-y-permisos)
- [Pruebas](#pruebas)
- [Convenciones de código](#convenciones-de-código)
- [Flujos críticos](#flujos-críticos)

---

## Contexto

El Call Center de la CSS opera con turnos rotativos en múltiples equipos. Antes de este sistema, la planificación vivía en hojas de cálculo y los permisos se gestionaban por correo, sin trazabilidad ni control de conflictos.

**Qué resuelve este sistema:**

- Planificación semanal con ciclo `draft → published` controlado exclusivamente por el rol WFM
- Flujo de aprobación de un solo nivel (Coordinador) para permisos y cambios de turno
- Registro auditado de incidencias de asistencia
- Visibilidad operativa por rol: cada usuario ve exactamente lo que le corresponde
- Trazabilidad inmutable: `audit_logs` sin UPDATE ni DELETE

**Qué NO hace (v1.0):**

- Nómina o cálculo de salarios
- Control de acceso físico
- Integraciones con sistemas externos

---

## Arquitectura

Monolito modular por dominio sobre Laravel 12. Cada módulo es una unidad autónoma con sus propios modelos, actions, policies y rutas. La comunicación entre módulos ocurre exclusivamente mediante Events o Contracts en `app/Shared/`.

```
app/
├── Modules/
│   └── {Modulo}/
│       ├── Actions/          # Lógica de negocio (una operación por clase)
│       ├── DTOs/             # Objetos de transferencia (readonly)
│       ├── Events/
│       ├── Listeners/
│       ├── Models/
│       ├── Observers/        # Solo efectos secundarios: caché, audit
│       ├── Policies/         # Autorización por entidad
│       ├── Http/
│       │   ├── Controllers/  # Thin: valida → DTO → Action → response
│       │   └── Requests/
│       ├── Providers/
│       │   └── ModuleServiceProvider.php
│       ├── Resources/Views/
│       └── Routes/
│           └── web.php
│
└── Shared/
    ├── Contracts/            # Interfaces para comunicación entre módulos
    ├── Services/             # HierarchyService, ScheduleResolverService
    ├── Traits/               # Auditable, HasTeamScope
    └── Exceptions/
```

**Reglas de dependencia entre módulos (en orden de capas):**

```
Foundation  →  Core · Location · Audit
Organization → Organization
Workforce    → Employees
Operations   → Scheduling · Intraday · Leave · Incidents · ShiftSwap
```

Un módulo de capas superiores puede importar de capas inferiores. **Nunca al revés.** Si la dependencia sería inversa, se usa un Event.

---

## Módulos

| Módulo | Dominio | Tablas principales |
|--------|---------|-------------------|
| `Core` | Identity & Access | `users`, `roles`, `permissions` |
| `Organization` | Estructura corporativa | `directorates`, `departments`, `positions`, `teams` |
| `Location` | Geografía | `provinces`, `districts`, `townships` |
| `Employee` | Fuerza laboral | `employees`, `employee_positions`, `team_members` |
| `EmployeeWellness` | Bienestar | `employee_dependents`, `employee_diseases`, `employee_disabilities` |
| `Schedule` | Motor de horarios | `schedules`, `break_templates` |
| `WeeklyPlanning` | Planificación | `weekly_schedules`, `weekly_schedule_assignments`, `employee_break_overrides` |
| `IntradayPlanning` | Intradía | `intraday_activities`, `intraday_activity_assignments` |
| `LeaveRequest` | Permisos | `leave_requests`, `leave_request_approvals` |
| `Attendance` | Asistencia | `attendance_incidents`, `incident_types` |
| `ShiftSwap` | Cambios de turno | `shift_swap_requests`, `shift_swap_approvals` |
| `Audit` | Auditoría | `audit_logs` |
| `Reports` | Reportería | — (queries sobre módulos existentes) |

---

## Requisitos

| Componente | Versión mínima |
|------------|---------------|
| PHP | 8.3 |
| Laravel | 12.x |
| PostgreSQL | 16 |
| Nginx | 1.24+ |
| Composer | 2.x |
| Node.js | 20 LTS (assets) |

**Extensiones PHP requeridas:** `pdo_pgsql`, `bcmath`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`

---

## Instalación

```bash
# 1. Clonar el repositorio
git clone <repo-url> wfm-css
cd wfm-css

# 2. Instalar dependencias
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# 3. Configurar entorno
cp .env.example .env
php artisan key:generate

# 4. Configurar base de datos en .env
# DB_CONNECTION=pgsql
# DB_HOST=127.0.0.1
# DB_PORT=5432
# DB_DATABASE=wfm_css
# DB_USERNAME=...
# DB_PASSWORD=...

# 5. Ejecutar migraciones y seeders
php artisan migrate --force
php artisan db:seed --class=RolesAndPermissionsSeeder

# 6. Optimizar para producción
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan permission:cache-reset
```

---

## Configuración

### Variables de entorno críticas

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://wfm.css.gob.pa

# Base de datos
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=wfm_css

# Zona horaria — Panamá
APP_TIMEZONE=America/Panama

# Sesiones Sanctum
SANCTUM_STATEFUL_DOMAINS=wfm.css.gob.pa
SESSION_LIFETIME=480        # 8 horas (jornada laboral)
SESSION_DRIVER=database

# Correo (recuperación de contraseñas, notificaciones)
MAIL_MAILER=smtp
MAIL_FROM_ADDRESS=wfm@css.gob.pa
```

### Nginx (configuración mínima)

```nginx
server {
    listen 443 ssl http2;
    server_name wfm.css.gob.pa;
    root /var/www/wfm-css/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

---

## Base de datos

### Jerarquía organizacional

La cadena de mando se implementa con **adjacency list** sobre `employees.parent_id`. No existe tabla de unidades organizacionales separada.

```
Director
└── Jefe
    └── Coordinador
        ├── Operador
        └── Supervisor (Operador II)
```

Cada Coordinador gestiona un único equipo (`team_members`). El `parent_id` nunca apunta al propio registro (constraint `employees_parent_not_self`).

### Planificación semanal

```
weekly_schedules (draft|published)
    └── weekly_schedule_assignments
            ├── employee_id
            ├── schedule_id
            └── break_template_id (nullable)
```

Los operadores solo ven su turno cuando `weekly_schedules.status = 'published'`. Solo el rol `wfm` puede publicar.

### Importación masiva de empleados

```bash
# Descargar plantilla CSV desde el sistema (Admin → Empleados → Importar)
# Campos requeridos: employee_number, username, first_name, last_name,
#                    email, birth_date, position_code, team_id, hire_date

php artisan employees:import storage/app/imports/empleados.csv
```

---

## Roles y permisos

El sistema usa **Spatie Laravel Permission** con RBAC + validación de jerarquía por `team_id`.

| Rol | `hierarchy_level` | Responsabilidad principal |
|-----|:-----------------:|--------------------------|
| `operator` | 1 | Consulta su horario, solicita permisos y cambios de turno |
| `supervisor` | 2 | Igual que operador + visibilidad de lectura del equipo |
| `coordinator` | 3 | Aprueba permisos y cambios de turno, registra incidencias |
| `chief` | 4 | Aprueba excepciones especiales y vacaciones largas |
| `wfm` | 5 | Planificación semanal, intradía, operaciones masivas (transversal) |
| `director` | 6 | KPIs globales, aprueba permisos de jefes |
| `admin` | 99 | Gestión de usuarios, catálogos e importaciones |

```bash
# Recrear roles y permisos desde cero
php artisan db:seed --class=RolesAndPermissionsSeeder
php artisan permission:cache-reset
```

**Nota:** Spatie no hereda permisos automáticamente entre roles. El seeder replica permisos de niveles inferiores en cada rol superior de forma explícita e intencional.

---

## Pruebas

```bash
# Suite completa
php artisan test

# Solo un módulo
php artisan test --filter=LeaveRequest

# Con cobertura (requiere Xdebug o PCOV)
php artisan test --coverage --min=60
```

**Cobertura mínima requerida:** 60% en clases `Action*` y `Service*`.

**Estructura de tests por módulo:**

```
tests/
├── Unit/
│   └── Modules/
│       └── LeaveRequest/
│           └── Actions/
│               └── CreateLeaveRequestActionTest.php
└── Feature/
    └── Modules/
        └── LeaveRequest/
            └── LeaveRequestWorkflowTest.php
```

---

## Convenciones de código

El proyecto sigue **PSR-12** y las convenciones oficiales de Laravel.

```bash
# Formatear código
./vendor/bin/pint

# Análisis estático
./vendor/bin/phpstan analyse --level=6
```

### Flujo obligatorio Controller → Action

```php
// ✅ Controller solo orquesta
public function store(StoreLeaveRequestRequest $request, CreateLeaveRequestAction $action): RedirectResponse
{
    $dto    = CreateLeaveRequestDTO::fromRequest($request);
    $result = $action->execute($dto);

    return redirect()->route('leave-requests.show', $result);
}

// ❌ Lógica de negocio en Controller → rechazo en code review
```

### Comunicación entre módulos

```php
// ✅ Mediante Events
event(new LeaveRequestApproved($leaveRequest));

// ❌ Importar directamente clases internas de otro módulo
use App\Modules\Scheduling\Services\ScheduleConflictService; // PROHIBIDO
```

---

## Flujos críticos

### Solicitud y aprobación de permiso

```
Operador: POST /leave-requests
    → StoreLeaveRequestRequest (valida + autoriza)
    → CreateLeaveRequestDTO
    → CreateLeaveRequestAction (valida conflictos, persiste, dispara evento)
    → LeaveRequestCreated event
        → NotifyCoordinatorListener (notificación database + mail)

Coordinador: POST /leave-requests/{id}/approve
    → ApproveLeaveRequestRequest
    → ApproveLeaveRequestAction (step=1, único nivel)
    → LeaveRequestApproved event
        → NotifyRequesterListener
```

### Resolución del horario efectivo diario

El servicio `ScheduleResolverService::resolve(employee, date)` aplica esta precedencia:

```
1. Excepción activa (leave_request aprobado o attendance_incident justificado)
2. Actividades intradía del día
3. Asignación semanal publicada (weekly_schedule_assignments)
```

### Publicación de planificación semanal

Solo el rol `wfm` puede ejecutar este flujo. Los operadores ven `403` hasta que la semana esté publicada.

```bash
# No existe comando artisan para esto — se ejecuta desde la UI
# La acción valida que no existan asignaciones sin horario base antes de publicar
```

---

## Licencia

Software propietario desarrollado para la **Caja de Seguro Social de Panamá**. Todos los derechos reservados. Prohibida su distribución, modificación o uso fuera del ámbito institucional sin autorización expresa.
