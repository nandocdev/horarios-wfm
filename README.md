<p align="center">
  <img src="public/img/logo_full.png" alt="HorariosWFM Logo" width="480"/>
</p>

<p align="center">
  <a href="https://laravel.com"><img src="https://img.shields.io/badge/Laravel-13.x-FF2D20?style=for-the-badge&logo=laravel" alt="Laravel 13"/></a>
  <a href="https://livewire.laravel.com"><img src="https://img.shields.io/badge/Livewire-4.x-FB70A9?style=for-the-badge&logo=livewire" alt="Livewire 4"/></a>
  <a href="https://fluxui.dev"><img src="https://img.shields.io/badge/Flux_UI-2.x-3B82F6?style=for-the-badge" alt="Flux UI 2"/></a>
  <a href="https://tailwindcss.com"><img src="https://img.shields.io/badge/TailwindCSS-4.x-38B2AC?style=for-the-badge&logo=tailwind-css" alt="TailwindCSS 4"/></a>
  <a href="https://postgresql.org"><img src="https://img.shields.io/badge/PostgreSQL-16-336791?style=for-the-badge&logo=postgresql" alt="PostgreSQL 16"/></a>
  <a href="https://redis.io"><img src="https://img.shields.io/badge/Redis-7.x-DC382D?style=for-the-badge&logo=redis" alt="Redis"/></a>
</p>

<h3 align="center">
  Workforce Management System — Call Center de la Caja de Seguro Social de Panamá
</h3>

<p align="center">
  Planificación de horarios, telemetría en tiempo real, adherencia, calidad y autogestión en un Monolito Modular.
</p>

---

## Stack Tecnológico

| Componente         | Tecnología                                |
| ------------------ | ----------------------------------------- |
| Backend            | PHP 8.3+, Laravel 13                      |
| Frontend           | Livewire 4, Flux UI 2, TailwindCSS 4      |
| Base de datos      | PostgreSQL 16                             |
| Cache              | Redis (vía predis/predis)                 |
| Colas              | Redis (Laravel Horizon 5)                 |
| WebSockets         | Laravel Reverb 1 + Laravel Echo 2         |
| Auth               | Laravel Fortify 1 (2FA, email verification) |
| Testing            | Pest 4 + PHPUnit (SQLite in-memory)       |
| Assets             | Vite 8                                    |
| Monitoreo          | Laravel Pulse 1                           |

---

## Arquitectura

**Monolito Modular** con 15 módulos en `app/Modules/`. La comunicación entre módulos es exclusivamente vía eventos de dominio (Shared Events), DTOs y contratos (Shared Contracts). No se permiten dependencias directas entre módulos.

```
app/Modules/{Module}/
├── Actions/           → Lógica de negocio (un archivo = un caso de uso)
├── Database/Migrations/
├── DTOs/              → Objetos de transferencia inmutables
├── Enums/
├── Events/            → Eventos de dominio
├── Listeners/         → Manejadores de eventos
├── Livewire/          → Componentes UI (sin lógica de negocio)
│   └── Forms/         → Livewire Form Objects
├── Models/            → Eloquent models (heredan de BaseModel con ULID)
├── Policies/          → Autorización (Spatie Permission)
├── Providers/
│   └── ModuleServiceProvider.php
├── Resources/Views/
└── Routes/
```

### Módulos

| Módulo                | Responsabilidad                                        | Clasificación  |
| --------------------- | ------------------------------------------------------ | -------------- |
| CoreModule            | Auth, RBAC, configuración global                       | —              |
| OrganizationModule    | Direcciones, departamentos, puestos                    | Supporting     |
| GeoModule             | Catálogo geográfico de Panamá                          | Supporting     |
| PersonnelModule       | Empleados, equipos, importación masiva                 | Supporting     |
| WfmModule             | Planificación de horarios, swaps, permisos, intradía   | Core (DDD)     |
| ConnectModule         | Integración Cisco UCCX/Finesse/CUIC                    | Core (DDD)     |
| OperationsModule      | KPIs, adherencia, dashboards, scorecards               | Core (DDD)     |
| CommunicationsModule  | Noticias, encuestas, shoutouts, reacciones             | Supporting     |
| QualityModule         | Evaluación de calidad, rúbricas, feedback              | Supporting     |
| AuditModule           | Trazabilidad y auditoría de cambios                    | Supporting     |
| WorkflowsModule       | Motor de aprobaciones multinivel                       | Supporting     |
| HelpdeskModule        | Tickets de soporte interno                             | Supporting     |
| KnowledgeModule       | Base de conocimiento operativo                         | Supporting     |
| DocumentationModule   | Wiki del sistema                                       | Supporting     |
| FilesystemModule      | Gestión de archivos y cuotas                           | Supporting     |

### Integraciones Externas

| Sistema          | Propósito                               | Tipo                        |
| ---------------- | --------------------------------------- | --------------------------- |
| Cisco UCCX       | Base de datos de llamadas (CDRs)        | Lectura SQL vía ODBC        |
| Cisco Finesse    | Estados de agente en tiempo real        | API REST XML (5s polling)   |
| Cisco CUIC       | Reportes históricos de colas y agentes  | API REST con UUIDs          |
| Webex            | Notificaciones a equipos IT/operaciones | API REST (Markdown messages)|

---

## Requisitos

- PHP 8.3+
- Composer 2.x
- Node.js 20+
- NPM 10+
- PostgreSQL 16
- Redis 7+
- Extensiones PHP: `pdo_pgsql`, `redis`, `xml`, `bcmath`, `intl`

---

## Instalación

```bash
# 1. Clonar el repositorio
git clone <repo-url> horarios-wfm
cd horarios-wfm

# 2. Instalar dependencias PHP
composer install

# 3. Instalar dependencias frontend
npm install

# 4. Configurar variables de entorno
cp .env.example .env
php artisan key:generate

# 5. Configurar .env (base de datos, redis, cisco, etc.)
#    DB_CONNECTION=pgsql
#    DB_HOST=127.0.0.1
#    DB_PORT=5432
#    DB_DATABASE=horarios_wfm
#    DB_USERNAME=postgres
#    DB_PASSWORD=

# 6. Ejecutar migraciones y seeders
php artisan migrate --seed

# 7. Compilar assets frontend
npm run build

# 8. Iniciar servidor de desarrollo
composer dev
```

### Credenciales por defecto

| Email                          | Rol    |
| ------------------------------ | ------ |
| `ferncastillo@css.gob.pa`      | admin  |

El seeder asigna el rol `admin` a este usuario forzosamente al final del seeding.

---

## Comandos de Desarrollo

| Comando                    | Descripción                                               |
| -------------------------- | --------------------------------------------------------- |
| `composer dev`             | Servidor + cola + logs + Vite (concurrente)                |
| `composer dev:uploads`     | Igual con límite de 20MB en uploads                        |
| `composer test`            | `config:clear` → `lint:check` → `php artisan test`         |
| `composer lint`            | Pinta formato automático                                   |
| `composer lint:check`      | Solo verificar formato                                     |
| `npm run build`            | Compilar assets frontend                                   |
| `npm run dev`              | Servidor Vite HMR                                          |

### Sincronización Cisco (producción)

| Comando                                              | Propósito                 |
| ---------------------------------------------------- | ------------------------- |
| `php artisan cisco:sync --loop --interval=5`         | Estados Finesse en vivo   |
| `php artisan cuic:sync --loop --interval=300`        | ETL histórico CUIC        |
| `php artisan cuic:sync-realtime --loop --interval=15`| CSQ en tiempo real        |

---

## Testing

```bash
# Suite completa
composer test

# Test específico
php artisan test --compact --filter=NombreTest
```

- **Framework:** Pest 4 + `pestphp/pest-plugin-laravel`
- **Base de datos:** SQLite in-memory
- **Colas:** `QUEUE_CONNECTION=sync`
- **Caché:** `CACHE_STORE=array`

---

## Convenciones del Proyecto

- `declare(strict_types=1)` requerido en todo archivo PHP
- ULID como identidad lógica en todos los modelos (via `BaseModel`)
- Una Action = un caso de uso, método `execute()`, operaciones de escritura en `DB::transaction()`
- Políticas (Spatie Permission) para toda entidad
- Livewire sin lógica de base de datos; delega a Actions
- Navegación SPA con `wire:navigate`
- Componentes Flux UI preferidos sobre HTML plano

---

## Licencia

Software propietario — **Caja de Seguro Social de Panamá**. Todos los derechos reservados.
