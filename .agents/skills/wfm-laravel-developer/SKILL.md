---
name: wfm-laravel-developer
description: Implementa funcionalidades Laravel en horarios-wfm respetando la arquitectura DDD Parcial del proyecto (tactical DDD en Core, transaction script en Supporting/Generic), priorizando simplicidad y código listo para producción.
---

# Laravel Developer — horarios-wfm

## Contexto obligatorio

Lee antes de evaluar, priorizar o documentar cualquier requerimiento:

- `docs/PRD.md` — fuente de verdad de requerimientos funcionales (RF-*), no funcionales (RNF-*), personas, fases, riesgos, fuera de alcance.
- `docs/USE_CASES.md` — catálogo de módulos y casos de uso con su responsabilidad declarada.
- `docs/ARCHITECTURE.md` — para no proponer requerimientos que violen principios ya decididos (ej. RNF-16: sin dependencias directas entre módulos).
- `docs/DATA_MODEL.md` — para verificar si un requerimiento nuevo ya tiene soporte de datos o implica modelo nuevo.
- `AGENTS.md` — para conocer el contexto del proyecto y las reglas de arquitectura.

---

## Cuándo utilizar esta skill

Implementar o modificar funcionalidades dentro del proyecto Laravel: módulos, casos de uso, Livewire, Actions, Models, DTOs, Policies, Jobs, Events/Listeners, migraciones, refactors, optimización de queries, integración entre módulos, fixes funcionales, tests.

**No usar para:** UX/UI, documentación funcional, DevOps, BI/análisis de datos.

---

## 0. Clasificar el contexto (paso obligatorio, antes de diseñar)

Todo módulo pertenece a uno de dos grupos. La clasificación determina qué reglas aplican — no son intercambiables.

### Core: Scheduling, WorkforceRequests, ContactCenterOps

Tactical DDD completo:

- Eloquent enriquecido: el modelo protege sus propias invariantes mediante métodos de dominio explícitos, no setters anémicos.
- Repository es válido si el Aggregate lo justifica (persistencia no trivial, múltiples fuentes, necesidad de aislar el ORM del dominio). No es obligatorio, pero no está prohibido como en Supporting.
- Antes de crear o modificar un Aggregate, consulta el `ddd-reviewer` subagent si está disponible.
- Los invariantes de negocio no salen del Aggregate hacia un Action "por conveniencia".

### Supporting / Generic: todo lo demás

Transaction script + Eloquent (reglas de las secciones siguientes aplican tal cual, sin excepción):

- Actions como caso de uso.
- Models anémicos (solo persistencia, relaciones, scopes, casts).
- Sin Repositories por defecto.

**Si no tienes certeza de a qué grupo pertenece un módulo, pregunta — no asumas Supporting por defecto.** Aplicar transaction script a un Core context revierte silenciosamente la migración DDD ya decidida.

---

## Arquitectura de carpetas

```text
app/Modules/{Module}/
├── Actions/
├── Console/Commands/
├── Database/Migrations/
├── DTOs/
├── Emails/
├── Enums/
├── Events/
├── Http/
│   ├── Controllers/
│   └── Requests/
├── Jobs/
├── Listeners/
├── Livewire/
│   └── Forms/
├── Mail/
├── Models/
├── Notifications/
├── Observers/
├── Policies/
├── Providers/
├── Repositories/
├── Resources/
│   └── Views/
├── Routes/
└── Services/
```

Nunca crear código fuera del módulo responsable, salvo infraestructura compartida ya definida por el proyecto. No acceder directamente a clases internas de otro módulo — comunicación entre módulos vía Actions, eventos o contratos compartidos.

---

## Responsabilidades por carpeta

**Actions** — un caso de uso por Action, único método `execute()`, controla transacciones. En Core, orquesta llamadas al Aggregate; no absorbe sus invariantes.

**Models (Supporting)** — persistencia, relaciones, scopes, casts, accessors/mutators. Sin lógica de negocio compleja.

**Models / Aggregates (Core)** — persistencia + invariantes de dominio protegidas en métodos explícitos.

**Livewire** — interacción de usuario, estado de pantalla, validación visual, navegación. Toda lógica de negocio se delega a Actions.

**DTOs** — objetos tipados entre capas; evitar arrays asociativos cuando el contrato es conocido.

**Policies** — toda autorización vía Policies/Gates. Nunca validar permisos dentro de Livewire.

**Jobs** — procesos asíncronos (correos, exportaciones, integraciones, tareas pesadas). No mover a cola lógica crítica que afecte la experiencia inmediata.

**Events** — hechos del dominio. No reemplazar llamadas directas sin beneficio claro de desacoplamiento.

**Services** — solo si hay lógica reutilizable entre múltiples Actions. Nunca Services CRUD.

**Repositories** — prohibidos por defecto en Supporting. En Core, justificados solo por consultas complejas reutilizables, múltiples orígenes de datos, o necesidad de optimización específica.

---

## Restricciones (aplican a ambos grupos)

No introducir: capas innecesarias, patrones Enterprise sin justificación, interfaces sin múltiples implementaciones reales, abstracciones "por si acaso", helpers globales, lógica duplicada.

No colocar reglas de negocio en: Blade, Controllers, Livewire, Requests (ni en Models si es Supporting). No hacer consultas complejas en vistas.

---

## Flujo de trabajo

1. **Clasificar** — Core o Supporting (ver sección 0). Si dudas, pregunta.
2. **Analizar** — objetivo funcional, módulo responsable, impacto cross-módulo, dependencias, mecanismo de tenancy vigente.
3. **Diseñar** — Action, Models/Aggregates, DTOs, Policies, Events, Jobs necesarios. Solo lo necesario.
4. **Implementar** — según arquitectura del grupo clasificado.
5. **Validar** — N+1, duplicación, acoplamiento, transacciones innecesarias, autorización, seguridad de tenant scoping.
6. **Documentar** — si la implementación cambia arquitectura, contratos, módulos, reglas de negocio o APIs.

---

## Convenciones técnicas

**Base de datos** — PostgreSQL. Aprovechar índices, JSONB, CTE, Window Functions, Materialized Views cuando simplifiquen la solución. No resolver en PHP lo que la BD resuelve eficientemente.

**Consultas** — revisar siempre eager loading, índices, cardinalidad, costo de ejecución. Evitar N+1.

**Validación** — Form Requests, Livewire Forms o DTOs. Nunca confiar en input del cliente.

**Tenancy** — validar contra `LARASHIFT_GUIDELINES.md` el mecanismo de aislamiento vigente antes de escribir cualquier query que toque datos de tenant. No asumir global scope automático.

**Testing** — prioridad: Feature > Integration > Unit. Validar comportamiento observable, no implementación interna.

---

## Criterios de aceptación

- Módulo clasificado correctamente (Core/Supporting) antes de implementar.
- Arquitectura del grupo respetada sin mezclar patrones.
- Cada clase ubicada en su carpeta.
- Lógica de negocio en Actions (Supporting) o en el Aggregate (Core) — nunca en Livewire/Controllers/Blade.
- Consultas eficientes, sin N+1.
- Autorización y validación correctas.
- Tenant scoping verificado, no asumido.
- Sin dependencias innecesarias entre módulos.
- Documentación y tests actualizados si corresponde.

## Principio rector

Ante cualquier decisión técnica: simplicidad → cohesión → bajo acoplamiento → legibilidad → mantenibilidad.

Si una solución requiere explicar demasiado su diseño, probablemente es más compleja de lo necesario. Esto no exime al Core de tactical DDD donde ya se decidió — la complejidad de un Aggregate bien diseñado es la complejidad inherente del dominio, no sobreingeniería.