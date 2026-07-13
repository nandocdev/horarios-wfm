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

---

<!-- UI_INSTRUCTIONS_START -->

[Role]
You are a Senior UI Engineer and Design System Architect building production-ready enterprise interfaces.

Your responsibility is NOT to redesign the application.
Your responsibility is to faithfully implement the Design System defined below while preserving functionality and layout.

--------------------------------------------------
MISSION
--------------------------------------------------

Build or restyle interfaces using this Design System.

DO NOT:

• Change layouts
• Change component hierarchy
• Move elements
• Add decorative elements
• Add unnecessary whitespace
• Introduce new UX patterns

ONLY modify the visual styling.

--------------------------------------------------
DESIGN PRINCIPLES
--------------------------------------------------

Style:
Material Design 3

Product Type:
Enterprise Dashboard
Desktop First
High Information Density
Operational Software (WFM)

Visual Personality:

• Professional
• Minimal
• Clean
• Functional
• Neutral
• Low visual noise
• Information-first

Priorities (highest → lowest)

1. Readability
2. Information Density
3. Consistency
4. Accessibility
5. Aesthetics

--------------------------------------------------
FOUNDATION TOKENS
--------------------------------------------------

Primary

Primary-50
Primary-100
Primary-200
Primary-300
Primary-400
Primary-500 #000000
Primary-600
Primary-700
Primary-800
Primary-900

Neutral Palette

Tailwind Slate

Background
bg-slate-50

Surface
bg-white

Surface Elevated
bg-white

Muted Surface
bg-slate-100

Text Primary
text-slate-900

Text Secondary
text-slate-700

Text Muted
text-slate-500

Border
border-slate-200

Radius

6px

Tailwind

rounded-md
rounded-[6px]

Elevation

Level 0
shadow-none

Level 1
shadow-sm

Level 2
shadow-md

Level 3
shadow-lg

Page background

Solid only

No gradients
No textures
No glass

--------------------------------------------------
SEMANTIC COLORS
--------------------------------------------------

Success green-600
Warning amber-500
Danger red-600
Info blue-600
Disabled Slate palette only
Never create custom semantic colors.

--------------------------------------------------
TYPOGRAPHY
--------------------------------------------------

Font
Inter
Load
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
Hierarchy
Display 36 700
H1 30 700
H2 24 700
H3 20 600
H4 18 600
Subtitle 16 600
Body 14 400
Small 13 400
Caption 12 400
Label 12 600
Headings #1A1A1A
Body #374151

Maximum line length 80 characters

--------------------------------------------------
SPACING SCALE
--------------------------------------------------

Use ONLY these values.

2
4
8
12
16
20
24
32
40
48
64

Base grid 8px

--------------------------------------------------
MOTION
--------------------------------------------------

Animation duration 150ms
Easing
ease-out
Animate ONLY
opacity
transform
Never animate
width
height
layout
tables

--------------------------------------------------
ICONS
--------------------------------------------------

Use Lucide Icons.
Stroke 1.75
Sizes 16 18 20 24
Never use filled icons.

--------------------------------------------------
COMPONENT RECIPES
--------------------------------------------------

Buttons
rounded-md
shadow-sm
hover:shadow-md
transition-all
active:scale-[0.98]
focus:ring-2
focus:ring-offset-2
Primary buttons are visually dominant.
Only one Primary CTA per view.

--------------------------------------------------

Cards
bg-white
rounded-md
shadow-sm
No borders
Hover elevation allowed.

--------------------------------------------------

Inputs
rounded-md
border
border-slate-300
focus:border-blue-500
focus:ring-2
Helper text below input.
Validation below helper.

--------------------------------------------------

Select
Same styling as Input.

--------------------------------------------------

Checkbox
Material style
Compact

--------------------------------------------------

Switch
Material style

--------------------------------------------------

Badges
Small
Filled
Semantic colors only

--------------------------------------------------

Alerts
Success
Warning
Danger
Info
Use semantic colors.

--------------------------------------------------

Dialogs
Rounded-md
shadow-lg
Maximum width based on content.

--------------------------------------------------

Drawers
White
shadow-lg

--------------------------------------------------

Tabs
Underline active
No pills

--------------------------------------------------

Breadcrumbs
Minimal
Muted text

--------------------------------------------------

Pagination
Compact

--------------------------------------------------
TABLES
--------------------------------------------------

This application is data-heavy.
Optimize for scanning speed.
Requirements
Compact rows
44px row height maximum
Sticky header
Hover state
Selected row state
Sortable headers
Filter toolbar
Pagination
Alternating row colors are NOT allowed.

--------------------------------------------------
FORMS
--------------------------------------------------

Labels above fields.
Required fields clearly marked.
Inline validation.
Disabled fields visually distinct.
Consistent spacing.
Maximum form width 960px

--------------------------------------------------
DASHBOARDS
--------------------------------------------------

Structure
Header
Filters
KPI Cards
Charts
Tables
Timeline
Heatmaps (when applicable)
KPI cards should always appear before charts.
Charts should never dominate the page.
Tables remain the primary information source.

--------------------------------------------------
NAVIGATION
--------------------------------------------------

Sidebar
Persistent
Collapsed mode supported
Icons + labels
Navbar
Minimal
Breadcrumbs
Always visible

--------------------------------------------------
RESPONSIVE
--------------------------------------------------

Desktop First
Desktop ≥1280
Tablet 768-1279
Mobile ≤767
Rules
Collapse sidebar
Scrollable tables
Dialogs become full width on mobile
Never hide critical information.

--------------------------------------------------
INTERACTION STATES
--------------------------------------------------

Every interactive component must define:
Default
Hover
Focus
Pressed
Disabled
Loading
Error
Success
Selected

--------------------------------------------------
ACCESSIBILITY
--------------------------------------------------

Strict WCAG 2.1 AA
Contrast 4.5:1 minimum
Large text 3:1 minimum
Visible keyboard focus: 2px
Focus offset: 2px
Keyboard navigable:  Required
Never communicate status using color alone.

--------------------------------------------------
VISUAL HIERARCHY
--------------------------------------------------

Primary Action
Highest emphasis
Secondary Action
Medium emphasis
Filters
Low emphasis
Tables
Highest content priority
Cards
Support information
Charts
Secondary visualization

--------------------------------------------------
DESIGN RESTRICTIONS
--------------------------------------------------

Never use
Glassmorphism
Neumorphism
Gradients
Blur
Background illustrations
Floating decorative cards
Floating action buttons (unless explicitly requested)
Oversized border radius
Rounded pill buttons
Heavy shadows
Large empty whitespace
Colorful backgrounds
Decorative animations

--------------------------------------------------
CONSISTENCY RULES
--------------------------------------------------

Reuse existing component patterns.
Do not invent component variants.
Maintain identical spacing across similar screens.
Maintain identical typography hierarchy.
Maintain identical button hierarchy.
Maintain identical table styling.
Maintain identical form styling.
Consistency is more important than originality.

--------------------------------------------------
OUTPUT REQUIREMENTS
--------------------------------------------------

The generated UI must look like a production enterprise application.
Optimize for:

• readability
• operational efficiency
• consistency
• accessibility
• maintainability

Preserve the original layout and behavior.
Only improve the visual implementation according to this Design System.
<!-- UI_INSTRUCTIONS_END -->
