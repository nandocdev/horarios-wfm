<!-- CODEGRAPH_START -->
## CodeGraph

In repositories indexed by CodeGraph (a `.codegraph/` directory exists at the repo root), reach for it BEFORE grep/find or reading files when you need to understand or locate code:

- **MCP tool** (when available): `codegraph_explore` answers most code questions in one call — the relevant symbols' verbatim source plus the call paths between them, including dynamic-dispatch hops grep can't follow. Name a file or symbol in the query to read its current line-numbered source. If it's listed but deferred, load it by name via tool search.
- **Shell** (always works): `codegraph explore "<symbol names or question>"` prints the same output.

If there is no `.codegraph/` directory, skip CodeGraph entirely — indexing is the user's decision.
<!-- CODEGRAPH_END -->

---

## Project Overview

**WFM (Workforce Management) system** for the Contact Center of the Caja de Seguro Social de Panamá. A Laravel Modular Monolith.

### Architecture

- **13 modules** under `app/Modules/` — each is an autonomous business unit: Actions, DTOs, Events, Livewire, Models, Policies, Observers, Providers, Routes, Resources/Views.
- Modules are loaded via `AppServiceProvider::register()` which iterates `config('modules.enabled')`. Order respects dependencies: CoreModule → Organization/Geo/Personnel → Operations/Connect → Communications/Audit/WFM → Support modules.
- **No direct cross-module imports.** Communication between modules is via Events + DTOs + Contracts (`app/Shared/Contracts/`).
- Routes are registered per-module in their `ModuleServiceProvider` (`loadRoutesFrom`, `loadViewsFrom`).
- Livewire components registered manually in each `ModuleServiceProvider` using `Livewire::component()`.
- Auth handled by **Laravel Fortify** — email verification + 2FA enabled, registration disabled.
- RBAC via **Spatie Laravel Permission** — admin role super-bypass in `AppServiceProvider::boot()` via `Gate::before()`.
- All models extend a **shared BaseModel** (uses ULID primary keys).
- `declare(strict_types=1)` required in every PHP file (enforced by Pint preset).

### Key Packages

| Package              | Version | Purpose               |
| -------------------- | ------- | --------------------- |
| Laravel Framework    | 13      | Core                  |
| Livewire             | 4       | Reactive UI           |
| Flux UI              | 2       | Component library     |
| TailwindCSS          | 4       | Styling (Vite plugin) |
| Fortify              | 1       | Auth backend          |
| Horizon              | 5       | Queue dashboard       |
| Pulse                | 1       | App monitoring        |
| Reverb               | 1       | WebSocket server      |
| Pest                 | 4       | Testing               |
| Spatie Permission    | 7       | RBAC                  |
| Spatie Media Library | 11      | File uploads          |
| Spatie Data          | 4       | DTOs                  |
| Laravel Echo         | 2       | WebSocket client      |

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

- Pest 4 with `pestphp/pest-plugin-laravel`.
- SQLite in-memory for tests (see `phpunit.xml` — `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`).
- Tests live in `tests/Feature/` and `tests/Unit/`. Module tests go under `tests/Feature/Modules/{Module}/`.
- Use factories for model creation — check existing factories for custom states before manual setup.
- Write feature tests unless the test truly has no DB/Laravel dependency.

### Gotchas

- **FluxUI requires auth credentials** during install: `composer config http-basic.composer.fluxui.dev "$FLUX_USERNAME" "$FLUX_LICENSE_KEY"` (see CI workflows).
- **`docs/technical/` referenced in `.github/instructions/main.instructions.md` does not exist.** Real docs are in `docs/`.
- **Vite manifest error** (`Unable to locate file in Vite manifest`) → run `npm run build`.
- **`config/livewire.php`** has explicit `component_namespaces` mapping — new components may need entries there too.
- **Session uses database driver** by default (`SESSION_DRIVER=database`). Cache uses `database` too.
- **Horizon config** uses Redis queue — see `config/horizon.php` for supervisor blocks and balancing.

### External Integration

- **Cisco UCCX/Finesse** for CTI telemetry — configured in `config/contact-center.php`.
- **Cisco CUIC** for historical reports — reports are mapped by UUID in `config/contact-center.php`.
- **Webex** for notifications — `app/Services/WebexService.php`.
- Shell scripts at repo root: `start-cuic-sync.sh`, `start-cisco-sync.sh`, `worker-cron.sh` manage long-running sync loops on production.

### Existing Instruction Files

| File                                        | Content                                                                           |
| ------------------------------------------- | --------------------------------------------------------------------------------- |
| `CLAUDE.md`                                 | Laravel Boost guidelines (also injected by system prompt)                         |
| `GEMINI.md`                                 | CodeGraph + Design System (same as AGENTS.md)                                     |
| `.github/copilot-instructions.md`           | Detailed modular architecture, naming conventions, patterns, policies (682 lines) |
| `.github/instructions/main.instructions.md` | 5-phase workflow for feature development                                          |
| `opencode.jsonc`                            | MCP server config for CodeGraph + Laravel Boost                                   |

### Skills (domain-specific, activate by name)

Available across `.agents/skills/`, `.claude/skills/`, and `.ai/skills/`:
```
fluxui-development    livewire-development    pest-testing
fortify-development   laravel-best-practices  tailwindcss-development
configure-horizon     pulse-development       echo-development
medialibrary-development  laravel-permission-development  deploying-laravel-cloud
```
