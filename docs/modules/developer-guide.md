# Guía para Desarrolladores: Arquitectura Modular (DDD)

## 📌 Introducción
Este documento técnico describe los lineamientos de arquitectura y convenciones de código para el desarrollo de **HorariosWFM**. El sistema está construido bajo el patrón de **Monolito Modular** impulsado por diseño orientado a dominio (DDD), utilizando **Laravel, Livewire, FluxUI y PostgreSQL**.

---

## 🏗️ Estructura del Proyecto

El código de negocio reside exclusivamente en la carpeta `app/Modules/` y `Shared/`. 

```text
app/
├ Modules/                          # Módulos de dominio independientes
│   ├ AuditModule/
│   ├ CoreModule/
│   ├ OperationsModule/
│   ├ PersonnelModule/
│   ├ WfmModule/
│   └ ...
│
└ Shared/                           # Código transversal y contratos
    ├ DTOs/                         # Objetos de transferencia globales
    ├ Contracts/                    # Interfaces para comunicación inter-módulo
    ├ Events/                       # Eventos de dominio públicos
    └ Infrastructure/               # Adaptadores genéricos
```

---

## 📦 Estructura Interna de un Módulo
Cada módulo opera de forma autónoma y debe respetar la siguiente estructura:

- `Actions/`: Lógica de negocio (un archivo por acción). Todas las acciones de escritura deben estar dentro de transacciones de base de datos (`DB::transaction`).
- `DTOs/`: Objetos de transferencia de datos inmutables internos.
- `Events/ & Listeners/`: Para comunicación asíncrona y reactiva.
- `Models/`: Modelos Eloquent PRIVADOS al módulo. **Prohibido instanciar modelos de un módulo desde otro módulo.**
- `Policies/`: Reglas de autorización por recurso.
- `Livewire/ & Resources/Views/`: Controladores de UI y Vistas Blade (usando FluxUI). Los componentes Livewire son orquestadores, la lógica pesada se delega a `Actions`.
- `Http/Controllers/ & Requests/`: Reservado para APIs externas o webhooks.
- `Providers/`: `ModuleServiceProvider` para registrar rutas, vistas y componentes del módulo.

---

## 🛑 Reglas Estrictas de Comunicación (Fronteras)

### 1. Aislamiento de Modelos
Un módulo **NUNCA** debe instanciar un modelo Eloquent de otro módulo directamente ni acceder a sus tablas de base de datos a través de `DB::table()`.

### 2. Comunicación Síncrona (Lecturas rápidas)
Si el *Módulo A* necesita datos del *Módulo B* en tiempo real, debe hacerlo a través de un **Contrato (Interface)** ubicado en `Shared/Contracts/`. El *Módulo B* implementará este contrato y retornará siempre un **DTO** estandarizado.

### 3. Comunicación Asíncrona (Escrituras / Reacciones)
Cuando ocurre una mutación de estado en un módulo (ej. empleado creado), se emite un evento genérico en `Shared/Events/`. Los módulos interesados escucharán este evento mediante sus respectivos `Listeners` encolados.

---

## 🎨 Frontend (Livewire + FluxUI)
- Los formularios deben utilizar **Livewire Form Objects**.
- Todo componente de UI debe utilizar las etiquetas de **FluxUI** (`<flux:input>`, `<flux:button>`, `<flux:table>`, etc.). Evitar el uso de HTML puro y estilos ad-hoc en Tailwind.
- Utilizar `wire:navigate` para transiciones estilo SPA.

---

## ✅ Checklist de Generación de Código
1. Uso de **strict_types=1** al inicio del archivo.
2. Uso de **DTOs** para la entrada/salida de datos entre la capa UI y la capa Action.
3. Transacciones estables de PostgreSQL para escrituras.
4. Uso de **PostgreSQL nativo** (ej. `$table->jsonb()`, `TO_CHAR`).
5. Inclusión del bloque `[RIESGOS]` en los PHPDoc para identificar posibles cuellos de botella (ej. N+1 queries o race conditions).
6. Tests utilizando Pest cubriendo los flujos críticos (ej. aserción de eventos disparados).
