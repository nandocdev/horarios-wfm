---
tipo: index
proyecto: "HorariosWFM"
estado: activo
fecha: 2026-08-12
tags:
  - proyecto
  - index
---

# HorariosWFM

## Estado Actual
| Campo              | Valor                     |
|--------------------|---------------------------|
| **Estado**         | Activo                    |
| **Fase**           | Desarrollo (Fases 6 y 7)  |
| **Salud**          | 🟢 Verde                  |
| **Última actualización** | 2026-08-12             |
| **Responsable**    | Equipo Dev                |
| **Cliente / Sponsor** | CSS Panamá (Call Center)|

## Resumen Ejecutivo
HorariosWFM es un sistema integral de Workforce Management para la Caja de Seguro Social de Panamá, que reemplaza procesos manuales con una plataforma web basada en Laravel y Livewire. Automatiza la planificación de horarios, el cálculo de adherencia en tiempo real (integración Cisco Finesse) y la autogestión de permisos e intercambios de turnos.

## Navegación Rápida

### Documentos Core
- [[01-Vision-y-Alcance]]
- [[02-Requisitos]]
- [[03-Arquitectura]]
- [[04-Backlog]]
- [[05-Decisiones]]
- [[06-Riesgos]]
- [[07 — Guía de Desarrollo (Onboarding)]]
- [[99 Checklist de Lanzamiento (Go-Live)]]

### Comercial
- [[Comercial/Cotizacion]]
- [[Comercial/Propuesta-Comercial]]
- [[Comercial/Contrato-Borrador]]

### Gestión
- [[Gestion/Plan]]
- [[Gestion/Estado]]

## Salud del Proyecto
| Área            | Estado     | Nota rápida                     |
|-----------------|------------|---------------------------------|
| Alcance         | 🟢         | Módulos Core (1 a 5) completados. |
| Tiempo          | 🟢         | Avance acorde a las fases del roadmap. |
| Costo           | 🟢         | Dentro de los recursos previstos. |
| Riesgos         | 🟡         | Riesgo en integración Cisco (CTI) monitoreado. |
| Calidad         | 🟡         | Deuda técnica pendiente en pruebas de UI. |

## Prioridades Actuales
1. Resolver pendientes UI del Dashboard principal y migración a `<x-apex-chart>`.
2. Completar mejoras de experiencia de usuario en autogestión (Alertas de saldo y cuatrimestres).
3. Añadir sistema de Notificaciones Webex.

## Blockers Activos
- [ ] Limpieza de menús Operaciones en espera del paso a producción del `ReportingModule` (BL-100).

## Próximos Hitos
| Hito                          | Fecha objetivo | Estado      |
|-------------------------------|----------------|-------------|
| Go-Live de Unificación Visual | TBD            | En progreso |
| Pruebas automatizadas Pest    | TBD            | Pendiente   |

## Enlaces Externos
- Repositorio: (Tu URL o GitHub)
- Tablero (Linear/Jira/GitHub Projects): 
- Entorno de staging: 
- Documentación adicional: Archivos Markdown base en `docs/`

## Historial de Cambios Relevantes
| Fecha       | Evento / Decisión                       | Link / Nota              |
|-------------|----------------------------------------|--------------------------|
| 2026-08-12  | Centralización inicial del Vault Obsidian | Consolidación de docs |

---

**Plantilla base**: `_Template-Proyecto`  
**Última revisión del index**: 2026-08-12