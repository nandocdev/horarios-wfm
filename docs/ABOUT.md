# About — HorariosWFM

> Workforce Management System para el Call Center de la **Caja de Seguro Social de Panamá**.

---

## Qué es

**HorariosWFM** es una plataforma integral de **Workforce Management (WFM)** que digitaliza y profesionaliza el ciclo completo de gestión del capital humano de un call center: planificación de horarios basada en demanda, telemetría de agentes en tiempo real, adherencia y reconciliación de asistencia, evaluación de calidad de llamadas y autogestión del colaborador.

Reemplaza procesos manuales basados en hojas de cálculo, correos electrónicos y sistemas inconexos por un monolito modular que garantiza transparencia, trazabilidad y equidad para cada operador, supervisor y directivo.

---

## Problema que resuelve

| Problema | Solución |
| --- | --- |
| Horarios semanales en hojas de cálculo, sin visibilidad de conflictos | Planificación digital con validación de colisiones y publicación con notificación |
| Adherencia y tardanzas detectadas de forma reactiva | Seguimiento intra-día con datos en tiempo real del CTI (Cisco UCCX/Finesse) |
| Permisos, vacaciones e intercambios vía correo y autorización manual | Autogestión con flujos de aprobación multinivel |
| Datos de llamadas, planillas y personal en sistemas inconexos | Integración ETL con Cisco UCCX, Finesse y CUIC |
| Decisiones administrativas sin registro centralizado | Trazabilidad y auditoría de todos los cambios |

---

## Alcance

- **Institución:** Caja de Seguro Social de Panamá
- **Unidad:** Call Center (atención telefónica al asegurado)
- **Usuarios estimados:** ~500–1000 (operadores, supervisores, coordinadores, analistas WFM, analistas QA, RRHH, directores, administradores)

---

## Stack tecnológico

| Componente | Tecnología |
| --- | --- |
| Backend | PHP 8.3+, Laravel 13 |
| Frontend | Livewire 4, Flux UI 2, TailwindCSS 4, Alpine.js |
| Base de datos | PostgreSQL 16 |
| Cache y colas | Redis 7+ (predis), Laravel Horizon 5 |
| WebSockets | Laravel Reverb 1 + Laravel Echo 2 |
| Auth | Laravel Fortify 1 (2FA TOTP, verificación de email) |
| Autorización | Spatie Laravel Permission (RBAC granular) |
| Reportes | barryvdh/laravel-dompdf, ApexCharts |
| Media | spatie/laravel-medialibrary |
| Testing | Pest 4 + PHPUnit (SQLite in-memory) |
| Assets | Vite 8 |
| Monitoreo | Laravel Pulse 1 |
| Otros | spatie/laravel-data (DTOs), Laravel Sanctum 4 |

---

## Arquitectura

**Monolito Modular** con 18 módulos en `app/Modules/`. La comunicación entre módulos es exclusivamente vía **eventos de dominio** (Shared Events), **DTOs** y **contratos** (Shared Contracts). No se permiten dependencias directas entre módulos.

```
app/Modules/{Module}/
├── Actions/           → Lógica de negocio (una Action = un caso de uso)
├── Database/Migrations/
├── DTOs/              → Objetos de transferencia inmutables
├── Enums/
├── Events/            → Eventos de dominio
├── Listeners/         → Manejadores de eventos
├── Livewire/          → Componentes UI (sin lógica de negocio)
│   └── Forms/         → Livewire Form Objects
├── Models/            → Eloquent models (heredan de BaseModel con ULID)
├── Policies/          → Autorización (Spatie Permission)
├── Providers/ModuleServiceProvider.php
├── Resources/Views/
└── Routes/
```

### Módulos

| Módulo | Responsabilidad |
| --- | --- |
| CoreModule | Auth, RBAC, configuración global |
| OrganizationModule | Direcciones, departamentos, puestos |
| GeoModule | Catálogo geográfico de Panamá |
| PersonnelModule | Empleados, equipos, importación/exportación masiva |
| WfmModule | Planificación de horarios, swaps, permisos, intra-día *(core)* |
| ConnectModule | Integración Cisco UCCX / Finesse / CUIC *(core)* |
| OperationsModule | KPIs, adherencia, dashboards, scorecards *(core)* |
| CommunicationsModule | Noticias, encuestas, shoutouts, reacciones |
| QualityModule | Evaluación de calidad, rúbricas, feedback |
| AuditModule | Trazabilidad y auditoría de cambios |
| WorkflowsModule | Motor de aprobaciones multinivel |
| HelpdeskModule | Tickets de soporte interno |
| KnowledgeModule | Base de conocimiento operativo |
| DocumentationModule | Wiki del sistema |
| FilesystemModule | Gestión de archivos y cuotas |
| ReportingModule | Reportes operativos y gerenciales |
| AnalyticsModule | Analítica e indicadores |
| DirectoryModule | Directorio institucional |

### Integraciones externas

| Sistema | Propósito | Tipo |
| --- | --- | --- |
| Cisco UCCX | Base de datos de llamadas (CDRs) | Lectura SQL vía ODBC |
| Cisco Finesse | Estados de agente en tiempo real | API REST XML (polling 5s) |
| Cisco CUIC | Reportes históricos de colas y agentes | API REST con UUIDs |
| Webex | Notificaciones a equipos IT/operaciones | API REST (Markdown) |

### Flujo de datos en tiempo real

1. **Cisco Finesse** publica estados de agente (polling 5s).
2. **ConnectModule** ingiere los estados vía comandos de sincronización en loop.
3. **OperationsModule** compara estado real vs. estado esperado del WfmModule y calcula adherencia.
4. Los dashboards se actualizan en vivo vía **Laravel Reverb/Echo** sin recargar la página.

---

## Convenciones de ingeniería

- `declare(strict_types=1)` en todo archivo PHP.
- ULID como identidad lógica (via `BaseModel`).
- Una Action = un caso de uso con método `execute()`; escrituras en `DB::transaction()`.
- Políticas (Spatie Permission) para toda entidad; nada de `can()` ad-hoc.
- Livewire sin consultas a base de datos; delega a Actions.
- Navegación SPA con `wire:navigate` y componentes Flux UI.
- Soft deletes en entidades de históricos.
- Consultas N+1 prohibidas; ETL con colas pesadas en queue `wfm-heavy` vía Horizon.

---

## Operación y despliegue

- **Servidores:** Linux (Nginx, Redis, Supervisord).
- **Colas:** Horizon con colas `default` y `wfm-heavy` (ETL Cisco).
- **Sincronización en producción:**
  - `php artisan cisco:sync --loop --interval=5` → estados Finesse en vivo.
  - `php artisan cuic:sync --loop --interval=300` → ETL histórico CUIC.
  - `php artisan cuic:sync-realtime --loop --interval=15` → CSQ en tiempo real.
- **Ambiente de desarrollo:** `composer dev` (servidor + cola + schedule + logs + Vite concurrentes).

---

## Seguridad

- Autenticación Fortify con **2FA TOTP** y verificación de email.
- Registro público deshabilitado; cuentas creadas administrativamente.
- RBAC granular con Spatie Permission y super-admin controlado.
- Auditoría de trazabilidad en decisiones administrativas.
- Autorización por políticas Eloquent en todas las entidades.

---

## Licencia

Software propietario — **Caja de Seguro Social de Panamá**. Todos los derechos reservados.