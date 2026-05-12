---
name: new-task
description: Ejecutar una tarea del ROADMAP en HorariosWFM (Monolito Modular) usando Laravel + Livewire + PostgreSQL, respetando estrictamente la arquitectura y el flujo de Git.
---

## 🎯 Contexto obligatorio

Proyecto: HorariosWFM
Arquitectura: Monolito Modular (app/Modules)
Stack: Laravel 12 + Livewire 3 + FluxUI + PostgreSQL + Pest/PHPUnit

Documentación base (leer SIEMPRE el contexto de estos archivos si están disponibles):
- docs/technical/07_Arquitectura.md
- docs/technical/08_ModuleModels.md
- docs/technical/05_DDL.md
- docs/technical/04_model.md
- docs/technical/03_casos_uso.md
- docs/technical/02_requisitos.md
- docs/technical/06_Permisos.md
- docs/technical/ROADMAP.md
- docs/features/NuevosModulos.md

---

## 🌿 Reglas de Control de Versiones (Git Flow)

Al procesar la tarea, DEBES estructurar tu respuesta o ejecución (si eres un agente de terminal) siguiendo estrictamente este flujo:
1. **Crear rama:** A partir de `develop`, crear una rama descriptiva (`feature/UC-XX-nombre` o `fix/nombre`).
2. **Ejecutar tarea:** Escribir todo el código requerido separando las responsabilidades (ver sección de Output).
3. **Commits atómicos:** Generar commits modulares por cada unidad lógica usando Conventional Commits.
4. **Merge a develop:** Finalizar con los comandos para cambiar a `develop` y hacer el merge de la rama trabajada.

**EJECUTA TODA LA TAREA EN UN SOLO BLOQUE DE RESPUESTA, INCLUYENDO LOS COMANDOS DE GIT. NO HAGAS RESPUESTAS PARCIALES.**

---

## 🚫 Reglas Arquitectónicas Inquebrantables

- Livewire = controlador UI → NUNCA lógica de negocio.
- Lógica de negocio → SOLO en Actions (una responsabilidad).
- Validación → Livewire Form Objects (NO FormRequest en web).
- Escrituras → `DB::transaction()` obligatorio.
- Autorización → Policies SIEMPRE invocadas antes de la acción.
- Módulos NO se acoplan directamente (usar Events, Listeners o DTOs).
- PostgreSQL nativo (jsonb, constraints, índices, sin sintaxis de MySQL).
- Evitar N+1 (uso de `with()` obligatorio para relaciones).
- No sobreingeniería.

---

## ⚙️ Tarea a ejecutar

{{TAREA_DEL_ROADMAP}}

*(Ejemplo: UC-OP-05 — Solicitud de Permiso Total con validación de duplicados)*

---

## 🧠 Proceso interno de la IA

1. **Planificar Git:** Definir nombre de la rama.
2. **Identificar:** Módulo destino, entidades involucradas, caso de uso asociado.
3. **Determinar Arquitectura:** DTO necesario, Action principal, Eventos, Policy requerida.
4. **Detectar Riesgos:** Race conditions (prevenir en DB), N+1 queries, dependencias entre módulos cruzados.
5. **Generar Tests:** Definir las pruebas unitarias/feature para blindar el Action.

---

## 📦 Output obligatorio (código listo para producción)

Generar SOLO lo necesario, separado por rutas de archivos reales:

### Scripts Git (Inicio)
- Comando de creación de rama. ``git checkout -b feature/UC-XX-nombre develop``

### Backend (Obligatorio)
- Model & Migration (optimizada para PostgreSQL).
- DTO (readonly, tipado estricto PHP 8.2+).
- Action (transaccional, única responsabilidad).
- Event + Listener (si interactúa con otro módulo).
- Policy (autorización estricta).

### Frontend / UI (Obligatorio)
- Componente Livewire (orquestador, `wire:submit`, redirecciones con `navigate: true`).
- Livewire Form Object (reglas de validación).
- Blade con FluxUI (Uso exclusivo de `<flux:input>`, `<flux:button>`, etc.).

### Testing (Obligatorio)
- Feature Test (Pest o PHPUnit) evaluando la Action, la validación del Form Object y la base de datos.

### Scripts Git (Cierre y Commits)
- Lista de comandos de commits atómicos.
- Comandos para merge a `develop`.

---

## ⚠️ Reglas Livewire y FluxUI específicas

- UI SIEMPRE con FluxUI. **Si no conoces un componente específico de FluxUI**, usa componentes HTML nativos de Laravel/Blade pero coméntalos con `<!-- TODO: Refactor to FluxUI -->`. **NO inventes propiedades de FluxUI**.
- Usar `wire:model` apuntando al Form Object.
- NO lógica de base de datos en métodos del componente Livewire (solo delegación a la Action).

---

## 🧪 Validaciones mínimas obligatorias

- No permitir duplicados (Constraint DB + Validación Form).
- Validar ownership (`.own`, `.team` o jerarquía).
- Manejo de excepciones consistente dentro del Action.

---

## 🔍 Checklist de validación (La IA debe cumplir TODO)

- [ ] Comandos Git incluidos (Rama, Commits, Merge).
- [ ] Código dentro de `app/Modules/{Modulo}`.
- [ ] Action implementa `DB::transaction()`.
- [ ] Policy aplicada correctamente.
- [ ] Sin N+1 queries.
- [ ] Feature Test incluido y funcional.
- [ ] Tipado estricto en PHP 8.2+.
- [ ] Sin explicaciones innecesarias ("yapping").

---

## 🧾 Entrega final y Commits Atómicos

- **CERO EXPLICACIONES:** No expliques cómo instalar Laravel, ni cómo funciona el código. Empieza directamente con los comandos Git y las rutas de los archivos con su código.
- Los commits deben usar **Conventional Commits en español**.

**Formato de Commits requeridos al final de la respuesta:**
`git commit -m "feat(<modulo>): crear DTO y Action transaccional para <entidad>"`
`git commit -m "feat(<modulo>): implementar Livewire component y Form Object"`
`git commit -m "test(<modulo>): agregar feature tests para <caso_uso>"`

**Cierre del flujo Git:**
`git checkout develop`
`git merge --no-ff <nombre-de-la-rama>`
