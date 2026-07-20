<!-- CODEGRAPH_START -->
## CodeGraph

In repositories indexed by CodeGraph (a `.codegraph/` directory exists at the repo root), reach for it BEFORE grep/find or reading files when you need to understand or locate code:

- **MCP tool** (when available): `codegraph_explore` answers most code questions in one call — the relevant symbols' verbatim source plus the call paths between them, including dynamic-dispatch hops grep can't follow. Name a file or symbol in the query to read its current line-numbered source. If it's listed but deferred, load it by name via tool search.
- **Shell** (always works): `codegraph explore "<symbol names or question>"` prints the same output.

If there is no `.codegraph/` directory, skip CodeGraph entirely — indexing is the user's decision.
<!-- CODEGRAPH_END -->

---

<!-- START_WORKFLOW -->
# Flujo General de Trabajo — horarios-wfm

Para cada solicitud, sigue este flujo sin omitir pasos.

---

## 1. Analizar el requerimiento

- Objetivo funcional y técnico, riesgos, dependencias, alcance.
- Si toca alcance de negocio, verificar contra `docs/PRD.md` §11 (Fuera de Alcance) antes de proceder.
- Preguntar solo si la ambigüedad impide implementar correctamente.

## 2. Analizar el proyecto

- Identificar el módulo responsable (de los 15 en `docs/USE_CASES.md`).
- Si el módulo no tiene ADR de clasificación Core/Supporting, preguntar antes de asumir el patrón de Model — no tratar como anémico ni como Aggregate por defecto.
- Revisar `docs/ARCHITECTURE.md` y `docs/DATA_MODEL.md` para el contexto técnico.
- Localizar componentes/Actions reutilizables antes de crear nuevos.

## 3. Crear rama de trabajo

Desde `develop`, nunca sobre `develop` o `main` directamente.

```text
feature/{modulo}-descripcion
fix/{modulo}-descripcion
refactor/{modulo}-descripcion
docs/{modulo}-descripcion
test/{modulo}-descripcion
chore/{modulo}-descripcion
```

`{modulo}` = nombre corto del módulo (`wfm`, `connect`, `personnel`, `operations`, etc.), no el nombre de la feature.

## 4. Planificar

- Tareas, archivos afectados, impacto cross-módulo (vía Events/DTOs, nunca dependencia directa).
- La solución más simple que resuelve el problema — no la más extensible.

## 5. Implementar

Solo los cambios necesarios.

- Livewire → Form → Action → Model, sin lógica de negocio en Livewire.
- FluxUI antes que HTML plano; Postgres nativo (`jsonb`, no `json`).
- Sin duplicación, sin acoplamiento nuevo entre módulos, sin deuda técnica no señalada.

## 6. Validar

- Ejecutar `composer test` (incluye lint + suite completa) o `php artisan test --filter=` para el caso puntual.
- Verificar N+1, transacciones, Policy aplicada.
- Confirmar que cumple el requerimiento completo, sin regresiones.

## 7. Commits atómicos

Conventional Commits en español, un commit = una responsabilidad. Scope = módulo.

```text
feat(wfm): agregar validación de colisión de turnos
fix(connect): corregir timeout en getAllUsers
refactor(operations): simplificar cálculo de adherencia
docs(architecture): documentar clasificación Core/Supporting
test(personnel): agregar pruebas de importación masiva
chore(ci): actualizar workflow de GitHub Actions
```

Prohibido: `cambios`, `update`, `fixes`, `varios cambios`.

## 8. Integración

- Rebase/merge con `develop` antes de integrar; resolver conflictos.
- Merge a `develop` solo con validación del paso 6 completa.
- Confirmar que el proyecto sigue funcionando post-integración (`composer test`).

---

## Principios generales

- Simplicidad sobre complejidad; no implementar nada no solicitado.
- No romper Monolito Modular ni la clasificación Core/Supporting ya decidida para un módulo.
- Consistencia con `docs/*.md` y con los skills de rol (`wfm-software-architect`, `wfm-laravel-developer`, `wfm-ui-engineer`, `wfm-product-owner`).
- Mantenibilidad y calidad por encima de velocidad de entrega.
- 
<!-- END_WORKFLOW -->

---

## Project Overview

**horarios-wfm** — Workforce Management system for the Contact Center of the Caja de Seguro Social de Panamá. Laravel Modular Monolith. No multi-tenancy — single institution.

### Architecture

- **15 modules** under `app/Modules/`: CoreModule, OrganizationModule, GeoModule, PersonnelModule, WfmModule, ConnectModule, OperationsModule, CommunicationsModule, QualityModule, AuditModule, WorkflowsModule, HelpdeskModule, KnowledgeModule, DocumentationModule, FilesystemModule.
- Load order respects dependencies via `config('modules.enabled')` in `AppServiceProvider::register()`: Core → Organization/Geo/Personnel → Operations/Connect → Communications/Audit/WFM → Support modules (Quality, Workflows, Helpdesk, Knowledge, Documentation, Filesystem).
- **No direct cross-module imports.** Communication via Events + DTOs + Contracts (`app/Shared/Contracts/`).
- Routes and views registered per-module in `ModuleServiceProvider` (`loadRoutesFrom`, `loadViewsFrom`).
- Livewire components registered manually per `ModuleServiceProvider` via `Livewire::component()`.
- Auth: **Laravel Fortify** (email verification + 2FA, registration disabled).
- RBAC: **Spatie Laravel Permission** — admin super-bypass in `AppServiceProvider::boot()` via `Gate::before()`.
- All models extend shared **BaseModel** (ULID primary keys).
- `declare(strict_types=1)` required in every PHP file (Pint-enforced).

### Módulos Core vs. Supporting (DDD Parcial)

La mayoría de los 15 módulos usa transaction script + Eloquent anémico. Un subconjunto de módulos Core opera bajo tactical DDD (Eloquent enriquecido, invariantes propias en el modelo). **La clasificación exacta módulo-por-módulo aún no tiene un ADR que la documente formalmente** — existe la decisión de fondo pero no el mapeo escrito contra los 15 módulos reales. No asumas que un módulo es Core o Supporting sin verificar; si vas a modificar un Model y no hay ADR que lo clasifique, pregunta antes de tratarlo como anémico o como Aggregate. Ver `docs/ARCHITECTURE.md` y el skill `wfm-software-architect`.

### Estructura canónica de módulo

```
app/Modules/{Modulo}/
├── Actions/            ├── Http/Controllers/     ├── Notifications/
├── Console/Commands/   ├── Http/Requests/         ├── Observers/
├── Database/Migrations/├── Jobs/                  ├── Policies/
├── DTOs/               ├── Listeners/             ├── Providers/
├── Emails/             ├── Livewire/              ├── Repositories/  (no usar por defecto)
├── Enums/              ├── Livewire/Forms/        ├── Resources/Views/
├── Events/             ├── Mail/                  ├── Routes/
├── Models/                                        └── Services/ (solo si hay reutilización real)
```

### Key Packages

| Package              | Version | Purpose                         |
| -------------------- | ------- | ------------------------------- |
| Laravel Framework    | 13      | Core                            |
| Livewire             | 4       | Reactive UI                     |
| Flux UI              | 2       | Component library               |
| TailwindCSS          | 4       | Styling (Vite plugin)           |
| Fortify              | 1       | Auth backend                    |
| Horizon              | 5       | Queue dashboard                 |
| Pulse                | 1       | App monitoring                  |
| Reverb               | 1       | WebSocket server                |
| Pest                 | 4       | Testing                         |
| Spatie Permission    | 7       | RBAC                            |
| Spatie Media Library | 11      | File uploads (FilesystemModule) |
| Spatie Data          | 4       | DTOs                            |
| Laravel Echo         | 2       | WebSocket client                |

> **⚠️ Discrepancia sin resolver:** `Spatie Data` implica DTOs como clases `extends Data`. El patrón mostrado en `rules-schedule.md` y en el skill `wfm-laravel-developer` usa `readonly class` de PHP puro sin el paquete. Antes de generar un DTO nuevo, verifica un DTO existente real del módulo para saber cuál patrón está vigente — no asumas ninguno de los dos.

### Commands

| Command                                        | What it does                                                    |
| ---------------------------------------------- | --------------------------------------------------------------- |
| `composer dev`                                 | Dev server + queue + logs + Vite (concurrently)                 |
| `composer dev:uploads`                         | Same with 20MB upload limits on artisan serve                   |
| `composer test`                                | `config:clear` → `lint:check` → `php artisan test` (full suite) |
| `composer lint`                                | `pint --parallel` (fixes formatting)                            |
| `composer lint:check`                          | `pint --parallel --test` (check only)                           |
| `php artisan test --compact --filter=TestName` | Run single test                                                 |
| `vendor/bin/pint --format agent`               | Fix PHP formatting (run before finalizing)                      |
| `npm run build`                                | Build frontend assets                                           |
| `npm run dev`                                  | Vite dev server                                                 |

### Testing

- Pest 4 (`pestphp/pest-plugin-laravel`), SQLite in-memory (`phpunit.xml`).
- `tests/Feature/` and `tests/Unit/`. Module tests: `tests/Feature/Modules/{Module}/`.
- Use factories; check existing factories for custom states before manual setup.
- Feature tests by default unless the test truly has no DB/Laravel dependency.

### Gotchas

- **FluxUI requires auth credentials**: `composer config http-basic.composer.fluxui.dev "$FLUX_USERNAME" "$FLUX_LICENSE_KEY"`.
- **`docs/technical/`** referenced in `.github/instructions/main.instructions.md` does not exist. Real docs live at repo root `docs/`: `ARCHITECTURE.md`, `DATA_MODEL.md`, `PRD.md`, `USE_CASES.md`.
- **Vite manifest error** → `npm run build`.
- **`config/livewire.php`** has explicit `component_namespaces` — new components may need entries there.
- **Session and cache use the `database` driver** by default.
- **Horizon** uses Redis queues — see `config/horizon.php` for supervisor/balancing.

### External Integration

- **Cisco UCCX/Finesse** (CTI telemetry) — `config/contact-center.php`.
- **Cisco CUIC** (historical reports, mapped by UUID) — `config/contact-center.php`.
- **Webex** (notifications) — `app/Services/WebexService.php`.
- Production sync loops: `start-cuic-sync.sh`, `start-cisco-sync.sh`, `worker-cron.sh`.

---

## Precedencia de instrucciones

Orden de autoridad cuando dos fuentes entran en conflicto:

1. Políticas de plataforma.
2. `.github/instructions/main.instructions.md` — flujo conversacional / workflow de 5 fases.
3. `rules-schedule.md` (`trigger: always_on`) — convenciones de arquitectura, stack y calidad de código, siempre activo en el editor.
4. Este `AGENTS.md` — overview operativo del repo (comandos, gotchas, estructura real).
5. Skills (`wfm-software-architect`, `wfm-laravel-developer`, `wfm-ui-engineer`, `wfm-product-owner`, y los skills de dominio de paquete abajo) — se activan por nombre/contexto para decisiones específicas.
6. `docs/*.md` (PRD, ARCHITECTURE, DATA_MODEL, USE_CASES) — fuente de verdad de negocio y arquitectura. **Si algo en los niveles 3–5 contradice `docs/`, `docs/` gana** — esos archivos son resúmenes operativos, no la fuente original.

### Instruction Files

| File                                        | Content                                                                    |
| ------------------------------------------- | -------------------------------------------------------------------------- |
| `rules-schedule.md`                         | Convenciones always-on de arquitectura/stack para autocompletado de editor |
| `CLAUDE.md`                                 | Laravel Boost guidelines (también inyectado por system prompt)             |
| `GEMINI.md`                                 | CodeGraph + Design System (mismo contenido que este AGENTS.md)             |
| `.github/copilot-instructions.md`           | Arquitectura modular detallada, naming, patrones, policies (682 líneas)    |
| `.github/instructions/main.instructions.md` | Workflow de 5 fases para desarrollo de features                            |
| `opencode.jsonc`                            | Config de servidores MCP (CodeGraph + Laravel Boost)                       |

### Skills (activan por nombre/contexto)

Paquete-específicos, en `.agents/skills/`, `.claude/skills/`, `.ai/skills/`:
```
fluxui-development           livewire-development            pest-testing
fortify-development          laravel-best-practices           tailwindcss-development
configure-horizon            pulse-development                echo-development
medialibrary-development     laravel-permission-development   deploying-laravel-cloud
```

Skills de rol (dominio del proyecto):
```
wfm-software-architect   wfm-laravel-developer   wfm-ui-engineer   wfm-product-owner
```

