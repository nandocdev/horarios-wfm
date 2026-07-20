---
trigger: always_on
---

---
trigger: always_on
---

# horarios-wfm — Instrucciones Generales

Stack confirmado (fuente: `docs/PRD.md` §6.1): PHP 8.3+, Laravel 13, Livewire 4, FluxUI 2, TailwindCSS 4, PostgreSQL 16, Redis, Laravel Horizon, Reverb, Fortify, Pest 4.

**Nota:** si tu editor traía un archivo previo referenciando "Antigravity", PHP 8.2 o Livewire 3 — era una versión desalineada con el PRD actual. Este archivo es la única fuente de convenciones de stack/arquitectura para autocompletado en editor.

---

## Precedencia

1. Políticas de plataforma.
2. Flujo conversacional: `/.github/instructions/main.instructions.md` (si existe).
3. Este archivo: arquitectura, stack, calidad de código, siempre activo.
4. Para decisiones que este archivo no resuelve (clasificación Core/Supporting, diseño de un módulo nuevo, priorización de negocio), consulta los skills: `wfm-software-architect`, `wfm-laravel-developer`, `wfm-ui-engineer`, `wfm-product-owner`. Este archivo no los reemplaza — les da la base común de convenciones que todos comparten.

---

## Arquitectura: Monolito Modular + DDD Parcial

15 módulos documentados en `docs/USE_CASES.md`: CoreModule, OrganizationModule, GeoModule, PersonnelModule, WfmModule, ConnectModule, OperationsModule, CommunicationsModule, QualityModule, AuditModule, WorkflowsModule, HelpdeskModule, KnowledgeModule, DocumentationModule, FilesystemModule.

**La mayoría usa transaction script + Eloquent anémico** (reglas de abajo, sin excepción). Un subconjunto de módulos Core opera bajo tactical DDD (Eloquent enriquecido con invariantes propias) — esa clasificación la define `wfm-software-architect` en ADR, no se infiere en el editor. **Si vas a modificar un Model y no sabes si su módulo es Core o Supporting, pregunta antes de asumir que es anémico.**

### Estructura canónica de módulo

Creado con `php artisan make:module {Modulo}`:

```text
app/Modules/{Modulo}/
├── Actions/                # Casos de uso, un método execute()
├── Console/Commands/
├── Database/Migrations/
├── DTOs/                   # Inmutables (readonly class)
├── Emails/
├── Enums/
├── Events/
├── Http/
│   ├── Controllers/        # Solo APIs/webhooks
│   └── Requests/
├── Jobs/
├── Listeners/               # ShouldQueue
├── Livewire/
│   └── Forms/
├── Mail/
├── Models/
├── Notifications/
├── Observers/
├── Policies/
├── Providers/               # ModuleServiceProvider
├── Repositories/            # No usar por defecto en Supporting
├── Resources/Views/
├── Routes/                  # web.php, api.php
└── Services/                # Solo si hay reutilización real entre Actions
```

### Prohibiciones absolutas

* Nunca lógica de negocio fuera de `app/Modules/`.
* Nunca dependencia directa a Models de otro módulo. Comunicación vía Events, DTOs o Actions públicas.
* Nunca lógica de negocio en Livewire — Livewire es orquestador UI, delega todo a una Action.
* Nunca `$table->json()` en Postgres — usar `jsonb()`.
* Nunca `DB::raw()` con sintaxis MySQL (`DATE_FORMAT`, etc.) — usar `TO_CHAR` o casting de Eloquent.

---

## Naming

| Tipo          | Regla                                          | Ejemplo                 |
| ------------- | ---------------------------------------------- | ----------------------- |
| Action        | Sufijo `Action`, un método público (`execute`) | `CreateUserAction.php`  |
| DTO           | Sufijo `DTO`, readonly                         | `UserDTO.php`           |
| Livewire      | Verbo/sustantivo descriptivo                   | `CreateUser.php`        |
| Livewire Form | Sufijo `Form`                                  | `UserForm.php`          |
| Event         | Acción en pasado                               | `UserRegistered.php`    |
| Listener      | Sufijo `Listener`, `ShouldQueue`               | `SendEmailListener.php` |

---

## Patrón obligatorio: Livewire → Form → Action

```php
class CreateUser extends Component
{
    public UserForm $form;

    public function save(CreateUserAction $action): void
    {
        $this->authorize('create', User::class);
        $this->form->validate();
        $action->execute($this->form->toDTO());
        \Flux::toast('Usuario creado.');
        $this->redirectRoute('users.index', navigate: true);
    }
}
```

```php
class CreateUserAction
{
    public function execute(UserDTO $dto): User
    {
        return DB::transaction(function () use ($dto) {
            $user = User::create([...]);
            event(new UserRegistered($user));
            return $user;
        });
    }
}
```

En módulos Core clasificados como tal, el Action orquesta contra un Aggregate (Model con invariantes propias) en vez de `User::create([...])` plano — confirma la clasificación antes de generar el Action.

---

## PostgreSQL

* `jsonb()`, índices parciales y compuestos según cardinalidad.
* Transacciones estrictas en toda escritura multi-entidad (`DB::transaction()`).
* ULID como PK en modelos de negocio (ver `docs/DATA_MODEL.md` §1.1).

## Frontend: Livewire + FluxUI

* Prohibido HTML plano si FluxUI tiene contraparte (`flux:input`, `flux:button`, `flux:modal`, `flux:table`, `flux:toast`).
* `wire:navigate` en enlaces internos; `navigate: true` en redirects.
* Errores y estados los maneja Livewire Forms + FluxUI automáticamente vía `wire:model`.
* El estado "sin permisos" se renderiza desde una Policy ya evaluada (`@can`), nunca evaluado dentro del componente.

## Seguridad

* Toda acción en Livewire/Controller/Request valida contra una Policy (Spatie Permission).
* Eager loading estricto; `Model::preventLazyLoading(!app()->isProduction())` activo.
* Nunca confiar en input del cliente sin Form Request, Livewire Form o DTO tipado.

## Module Service Provider

Cada módulo registra rutas, vistas y componentes Livewire de forma aislada en su propio `ModuleServiceProvider`. No registrar nada de un módulo en el provider de otro.

---

## Checklist antes de generar código

- [ ] ¿El archivo va en `app/Modules/{Modulo}/` correcto?
- [ ] ¿Se confirmó si el módulo es Core o Supporting (si aplica)?
- [ ] ¿Livewire delega la lógica a una Action, no la ejecuta?
- [ ] ¿La validación usa Livewire Form (v4), no reglas inline?
- [ ] ¿El frontend usa componentes FluxUI, no HTML plano?
- [ ] ¿Las queries evitan N+1 y usan sintaxis Postgres?
- [ ] ¿La escritura está envuelta en `DB::transaction()`?
- [ ] ¿Se validó la Policy antes de ejecutar la acción?
- [ ] ¿Los cambios de estado disparan Events para desacoplar módulos?

---

## Antipatrones — nunca sugerir

```php
// ❌ Livewire con lógica mezclada
public function save() {
    $user = User::create([...]);
    Mail::send(...);   // debe ser Event/Listener
}

// ❌ Dependencia directa entre módulos
$product = \App\Modules\Inventory\Models\Product::find($id);
// ✅ Usar DTO + Action pública del módulo dueño

// ❌ HTML plano existiendo FluxUI
<input wire:model="name" class="border p-2">
// ✅ <flux:input wire:model="name" />

// ❌ json() en Postgres
$table->json('data');
// ✅ $table->jsonb('data');
```

---

## Documentación de referencia

`docs/PRD.md` (requerimientos, prioridades, alcance) · `docs/ARCHITECTURE.md` (principios, integraciones, seguridad) · `docs/DATA_MODEL.md` (esquema, convenciones de datos) · `docs/USE_CASES.md` (catálogo de módulos y responsabilidades). Ante cualquier conflicto entre este archivo y esos documentos, los documentos ganan — este archivo es un resumen operativo, no la fuente de verdad.