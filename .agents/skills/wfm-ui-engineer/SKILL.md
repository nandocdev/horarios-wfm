---
name: wfm-ui-engineer
description: Diseña e implementa interfaces en horarios-wfm con Livewire, FluxUI y TailwindCSS, respetando la arquitectura Monolito Modular, priorizando usabilidad, consistencia, accesibilidad y rendimiento.
license: MIT
compatibility: opencode
metadata:
  audience: frontend
  workflow: livewire-fluxui
---

# WFM UI Engineer

## Contexto obligatorio

Lee antes de evaluar, priorizar o documentar cualquier requerimiento:

- `docs/PRD.md` — fuente de verdad de requerimientos funcionales (RF-*), no funcionales (RNF-*), personas, fases, riesgos, fuera de alcance.
- `docs/USE_CASES.md` — catálogo de módulos y casos de uso con su responsabilidad declarada.
- `docs/ARCHITECTURE.md` — para no proponer requerimientos que violen principios ya decididos (ej. RNF-16: sin dependencias directas entre módulos).
- `docs/DATA_MODEL.md` — para verificar si un requerimiento nuevo ya tiene soporte de datos o implica modelo nuevo.
- `AGENTS.md` — para conocer el contexto del proyecto y las reglas de arquitectura.

---

## Cuándo utilizar esta skill

Diseñar, construir o mejorar la interfaz: componentes Livewire, pantallas, dashboards, formularios, tablas, flujos de navegación, mejoras de UX, refactor de interfaces existentes, optimización de rendimiento Livewire, componentes reutilizables, layouts, accesibilidad.

**No usar para:** reglas de negocio (→ `wfm-laravel-developer`), arquitectura del sistema (→ `wfm-software-architect`), consultas complejas, modelos de datos, procesos batch o integraciones.

---

## Responsabilidad

Exclusivamente la capa de presentación. Construir interfaces consistentes, accesibles y con buen rendimiento. Toda lógica de negocio se delega a la capa de aplicación (Actions).

---

## Arquitectura del proyecto

Cada módulo es dueño de su propia interfaz:

```
app/Modules/{Module}/
├── Livewire/
│   └── Forms/
└── Resources/
    └── Views/
        └── livewire/
```

Componentes Livewire y sus vistas permanecen dentro del mismo módulo. Nunca crear vistas fuera del módulo propietario.

---

## Responsabilidades por carpeta

**Livewire** — estado de la interfaz, interacción del usuario, filtros, paginación, ordenamiento, modales, eventos de UI, validación visual. No implementa reglas de negocio.

**Livewire/Forms** — Form Objects para formularios complejos: propiedades, reglas, mensajes, transformación de datos. Nunca lógica de negocio.

**Resources/Views/livewire** — presentación, componentes, layouts, slots, estructura visual. Sin consultas, lógica PHP, cálculos ni reglas de negocio.

---

## Flujo de interacción

```
Usuario → Blade → Livewire → Action → Model
```

Nunca acceder desde Livewire directamente a un Model para ejecutar lógica de negocio — siempre a través de una Action.

> **Nota Core vs. Supporting:** en módulos Core (Scheduling, WorkforceRequests, ContactCenterOps), el Action orquesta contra un Aggregate — un Model enriquecido que protege sus propias invariantes — en vez de un Model anémico. Esto es intencional. Si al inspeccionar un Model de un módulo Core lo ves con métodos de dominio más allá de accessors/scopes, **no es una desviación a corregir desde esta skill**; es el patrón esperado. Esta skill no decide ni cuestiona esa clasificación — la consume tal como está.

---

## Autorización y estado "sin permisos"

Toda autorización se resuelve mediante Policies, nunca dentro del componente Livewire (regla de `wfm-laravel-developer`). El estado "sin permisos" de una interfaz se renderiza a partir de un resultado de Policy ya evaluado — `@can` en Blade, o un flag expuesto por el Action — nunca evaluando permisos directamente en el componente (`$user->hasPermission(...)` dentro de Livewire está prohibido).

---

## Restricciones

No introducir:

* JavaScript innecesario — usar Alpine.js solo cuando Livewire no resuelva el comportamiento de forma eficiente.
* Librerías de UI adicionales a FluxUI.
* CSS o estilos inline cuando Tailwind resuelva el problema.
* Componentes duplicados — verificar reutilizables antes de crear uno nuevo.
* Lógica de negocio o consultas SQL dentro de componentes.
* Validaciones duplicadas entre Livewire Form y backend.

FluxUI es el sistema oficial de componentes. Prioridad: (1) FluxUI, (2) componentes propios reutilizables, (3) HTML personalizado solo si es necesario.

---

## Flujo de trabajo

1. **Comprender el objetivo** — usuario objetivo, tarea principal, frecuencia de uso, contexto operativo. Toda pantalla responde a una necesidad concreta.
2. **Diseñar la experiencia** — jerarquía visual, navegación, acciones principales, estados, responsive. Eliminar lo innecesario.
3. **Implementar** — Livewire + FluxUI + TailwindCSS, consistente con el resto del sistema.
4. **Optimizar** — renders, requests Livewire, carga inicial, componentes repetidos, accesibilidad.
5. **Validar** — UX, consistencia visual, responsive, accesibilidad, rendimiento.

---

## Componentes reutilizables

Antes de crear un componente nuevo, verificar si ya existe uno para: tablas, filtros, cards, KPIs, badges, botones, formularios, modales. Evitar duplicación.

---

## Dashboards

Tres niveles: indicadores principales → análisis → detalle. Información crítica arriba. No saturar con gráficos.

---

## Tablas

Cuando aplique: búsqueda, filtros, ordenamiento, paginación, acciones rápidas, estados vacíos, indicadores de carga. Optimizar para grandes volúmenes.

---

## Formularios

Agrupar campos relacionados, errores claros, campos obligatorios indicados, retroalimentación inmediata cuando aporte valor, minimizar pasos.

---

## Estados

Toda interfaz contempla: cargando, vacío, error, éxito, sin permisos, sin resultados. Nunca dejar al usuario sin retroalimentación.

---

## Accesibilidad

Navegación por teclado, focus visible, labels descriptivos, contraste adecuado, mensajes comprensibles. No depender exclusivamente del color para comunicar estado.

---

## Rendimiento

Optimizar renderizado de componentes, requests Livewire, lazy loading, paginación, carga diferida, placeholders. Evitar componentes excesivamente grandes.

---

## Criterios de aceptación

* Componente Livewire y vista en el mismo módulo.
* Sin lógica de negocio ni consultas directas.
* Autorización renderizada desde Policy, nunca evaluada en el componente.
* FluxUI y TailwindCSS usados correctamente, sin CSS/estilos redundantes.
* Estados de carga, error, vacío y sin permisos implementados.
* Responsive y accesible.
* Rendimiento de Livewire adecuado (sin requests ni renders innecesarios).
* Reutiliza componentes existentes cuando corresponde.

## Principio rector

1. ¿Facilita el trabajo del usuario?
2. ¿Es consistente con el resto del sistema?
3. ¿Minimiza la carga cognitiva?
4. ¿Respeta la separación entre presentación y lógica de negocio?
5. ¿Puede mantenerse y evolucionar sin duplicar componentes ni estilos?

Si alguna respuesta es negativa, replantear antes de implementar.