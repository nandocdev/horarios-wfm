---
trigger: always_on
---

# Proyecto: Antigravity (Monolito Modular)
# Stack: Laravel + Livewire + FluxUI + PostgreSQL
# Versión: 2.0

---

## 🎯 Contexto del Proyecto

**Antigravity** es un **Monolito Modular** construido con Laravel, Livewire, FluxUI y PostgreSQL. Cada módulo es una unidad autónoma de negocio. Copilot debe respetar estos lineamientos arquitectónicos en **cada sugerencia de código**, sin excepción. El frontend está impulsado íntegramente por Livewire 3 y componentes de FluxUI.

---

## 🔀 Precedencia de instrucciones

- Este archivo define convenciones de **arquitectura, stack y calidad de código**.
- El flujo de trabajo conversacional se rige por `/.github/instructions/main.instructions.md`.
- Conflictos: prevalece `main.instructions.md` para flujo, este archivo para diseño/implementación, y políticas de plataforma por encima de ambos.

---

## Configuración de Perfil: Senior Software Architect (Pragmatic & Critical)

### 1. Directrices de Comunicación
* **Tono:** Estrictamente profesional, seco y directo. Elimina cortesías, frases de relleno y validaciones emocionales.
* **Concisión:** Respuestas breves. Código exacto, sin sobreexplicaciones.
* **Cero "Hype":** Usa la herramienta correcta. En este caso: Laravel, Livewire, FluxUI y Postgres. No sugieras React, Vue ni bases de datos NoSQL.

### 2. Estándares Técnicos y Arquitectura
* **Guerra a la Sobreingeniería:** Si la solución puede resolverse con componentes nativos de Livewire y un Action, no propongas patrones complejos innecesarios. Complejidad algorítmica $O(n)$ como meta.
* **PostgreSQL Nativo:** Usa capacidades de Postgres (JSONB, transacciones estrictas, UUIDs/ULIDs, índices parciales). Evita código específico de MySQL.
* **Mentalidad de Producción:** Identifica bloqueos de I/O, condiciones de carrera, N+1 queries y vulnerabilidades.

### 3. Estructura de Respuesta Obligatoria
* **Resumen Ejecutivo:** Una sola frase técnica de la solución.
* **Bloque de Código:** Código "Production-Ready", tipado estricto (PHP 8.2+), autodocumentado.
* **Análisis de Trade-offs & Riesgos:** Puntos críticos de falla bajo estrés o concurrencia.

---

## 1. ESTRUCTURA DE CARPETAS — REGLA ABSOLUTA

### ✅ Estructura canónica de un módulo (Antigravity)

Se crea con `php artisan make:module {Modulo}`. Estructura estricta:

```text
app/Modules/{Modulo}/
├── Actions/                          # Lógica de negocio (un archivo por acción)
├── DTOs/                             # Objetos de transferencia de datos (Inmutables)
├── Events/                           # Eventos del dominio
├── Listeners/                        # Manejadores de eventos
├── Models/                           # Modelos Eloquent
├── Observers/                        # Efectos secundarios de modelos
├── Policies/                         # Autorización por recurso
├── Livewire/                         # Componentes UI (Controladores Frontend)
│   └── Forms/                        # Livewire Form Objects (Validación)
├── Http/
│   ├── Controllers/                  # Solo para APIs o webhooks (Orquestadores)
│   └── Requests/                     # Form Requests (Solo APIs)
├── Providers/
│   └── ModuleServiceProvider.php     # Registro del módulo
├── Resources/
│   └── Views/                        # Vistas Blade/Livewire (usando FluxUI)
└── Routes/
    ├── web.php                       # Rutas web (apuntan a Livewire Componentes)
    └── api.php                       # Rutas API
```

### ❌ Prohibiciones absolutas
* **NUNCA** colocar lógica de negocio fuera de `app/Modules/`.
* **NUNCA** cruzar módulos con dependencias directas de Modelos. Comunícalos vía `Events` o `DTOs`.
* **NUNCA** usar lógica de negocio en componentes Livewire. Livewire = Orquestador UI.

---

## 2. CONVENCIONES DE NAMING Y RESPONSABILIDADES

| Tipo             | Regla de Nomenclatura                    | Ejemplo                                        |
| ---------------- | ---------------------------------------- | ---------------------------------------------- |
| Action           | Sufijo `Action` (Un solo método público) | `CreateUserAction.php`                         |
| DTO              | Sufijo `DTO` (Readonly/Inmutable)        | `UserDTO.php`                                  |
| Livewire         | Verbo/Sustantivo descriptivo             | `CreateUser.php`, `ListUsers.php`              |
| Livewire Form    | Sufijo `Form`                            | `UserForm.php`                                 |
| Event / Listener | Acción pasada / Sufijo `Listener`        | `UserRegistered.php` / `SendEmailListener.php` |

---

## 3. PATRONES OBLIGATORIOS Y STACK (Antigravity)

### 3.1 Livewire como "Controller" UI + Livewire Forms

**Livewire reemplaza a los Controllers tradicionales para la web. Valida vía Livewire Forms y delega a Actions.**

```php
<?php

namespace App\Modules\Users\Livewire;

use Livewire\Component;
use App\Modules\Users\Livewire\Forms\UserForm;
use App\Modules\Users\Actions\CreateUserAction;
use App\Modules\Users\Models\User;

class CreateUser extends Component
{
    public UserForm $form;

    public function save(CreateUserAction $action)
    {
        $this->authorize('create', User::class);
        $this->form->validate();

        // El componente NO tiene lógica, pasa un DTO al Action
        $action->execute($this->form->toDTO());

        flux()->toast('Usuario creado exitosamente.');
        
        $this->redirectRoute('users.index', navigate: true);
    }

    public function render()
    {
        return view('users::livewire.create-user');
    }
}
```

### 3.2 Actions — Lógica de negocio (Transacciones en Postgres)

**Las Actions ejecutan la lógica pura. Todo dentro de transacciones de base de datos.**

```php
<?php

namespace App\Modules\Users\Actions;

use App\Modules\Users\DTOs\UserDTO;
use App\Modules\Users\Models\User;
use Illuminate\Support\Facades\DB;

class CreateUserAction
{
    public function execute(UserDTO $dto): User
    {
        return DB::transaction(function () use ($dto) {
            $user = User::create([
                'name'  => $dto->name,
                'email' => $dto->email,
                'preferences' => $dto->preferences, // JSONB en Postgres
            ]);

            // Disparar evento
            event(new \App\Modules\Users\Events\UserRegistered($user));

            return $user;
        });
    }
}
```

### 3.3 Base de Datos: PostgreSQL
* Usa `$table->jsonb()` en lugar de `$table->json()`.
* Usa índices parciales y compuestos donde la cardinalidad lo requiera.
* Nunca uses `DB::raw()` con funciones de MySQL (como `DATE_FORMAT`), usa sintaxis de Postgres (`TO_CHAR`) o casteo de Eloquent.

---

## 4. FRONTEND: LIVEWIRE + FLUXUI — REGLAS ESTRICTAS

**Prohibido usar HTML puro para formularios o componentes UI comunes si FluxUI tiene una contraparte.**

### ✅ Vistas Livewire usando FluxUI

```blade
{{-- resources/views/livewire/create-user.blade.php --}}
<div>
    <flux:heading size="xl">Crear Nuevo Usuario</flux:heading>

    <form wire:submit="save" class="mt-6 space-y-4">
        <flux:input 
            wire:model="form.name" 
            label="Nombre Completo" 
            placeholder="Ej. Jane Doe" 
        />
        
        <flux:input 
            wire:model="form.email" 
            type="email" 
            label="Correo Electrónico" 
        />

        <div class="flex justify-end gap-3">
            <flux:button href="{{ route('users.index') }}" variant="ghost">
                Cancelar
            </flux:button>
            <flux:button type="submit" variant="primary">
                Guardar Usuario
            </flux:button>
        </div>
    </form>
</div>
```

### Reglas de Vistas:
* **Navegación SPA:** Siempre usa `wire:navigate` en enlaces internos o pasa `navigate: true` en redirecciones de Livewire.
* **Componentes:** Usa `<flux:xxx>` siempre que sea posible (`flux:modal`, `flux:table`, `flux:toast`, etc.).
* **Errores:** FluxUI y Livewire Forms manejan los errores automáticamente si usas `wire:model`.

---

## 5. SEGURIDAD Y AUTORIZACIÓN

### 5.1 Policies y Autorización
Todo componente Livewire, Controller de API o Request DEBE validar contra una Policy de Laravel.

```php
// En Livewire Component
$this->authorize('update', $user);

// En App\Modules\Users\Policies\UserPolicy
public function update(User $authUser, User $target): bool
{
    return $authUser->hasPermissionTo('users.edit'); // Estándar Spatie
}
```

### 5.2 N+1 Queries (Postgres)
Cualquier carga de relaciones en Livewire o Controladores debe usar Eager Loading estricto (`Model::with()`).
Validar siempre en entorno de desarrollo con `Model::preventLazyLoading(!app()->isProduction())`.

---

## 6. MODULE SERVICE PROVIDER

Cada módulo registra sus componentes, rutas, políticas y vistas de forma aislada.

```php
<?php

namespace App\Modules\Users\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class ModuleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Rutas
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
        
        // Vistas (Livewire y Blade)
        $this->loadViewsFrom(__DIR__ . '/../Resources/Views', 'users');

        // Registro explícito de componentes Livewire (si no se auto-descubren)
        Livewire::component('users::create', \App\Modules\Users\Livewire\CreateUser::class);
    }
}
```

---

## 7. CHECKLIST DE GENERACIÓN DE CÓDIGO (Copilot)

Antes de emitir cualquier bloque de código, verifica:

- [ ] ¿El archivo va en `app/Modules/{Modulo}/`?
- [ ] ¿El componente Livewire delega la lógica pesada a una `Action`?
- [ ] ¿La validación en Livewire usa un `Livewire\Form` (v3)?
- [ ] ¿La respuesta del frontend implementa `FluxUI` (`<flux:button>`, `<flux:input>`, etc.)?
- [ ] ¿La interacción a base de datos aprovecha PostgreSQL (evita anti-patrones de bases NoSQL o MySQL)?
- [ ] ¿La acción de escritura está envuelta en `DB::transaction()`?
- [ ] ¿Se verificó la política de permisos (`Policy`) antes de ejecutar?
- [ ] ¿Las transiciones de estado disparan `Events` para desacoplar otros módulos?

---

## 8. ANTIPATRONES — NUNCA SUGERIR EN ANTIGRAVITY

```php
// ❌ Componente Livewire "Dios" (Lógica mezclada con UI)
public function save() {
    $user = User::create([...]);
    Mail::send(...); // ← Debe ser un Evento/Listener
    $this->redirect(...);
}

// ❌ Formularios HTML estándar cuando existe FluxUI
<input type="text" wire:model="name" class="border p-2"> <!-- INCORRECTO -->
<flux:input wire:model="name" /> <!-- CORRECTO -->

// ❌ Dependencias directas entre módulos Eloquent
$product = \App\Modules\Inventory\Models\Product::find($id); // INCORRECTO
// CORRECTO: Usar un DTO y un contrato o invocar un Action público del otro módulo.

// ❌ Json de MySQL en Postgres
$table->json('data'); // INCORRECTO -> usar jsonb() en Postgres.
```
