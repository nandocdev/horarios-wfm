---
tipo: backlog
proyecto: "HorariosWFM"
estado: activo
fecha: 2026-08-12
tags:
  - proyecto
  - backlog
---

# 04 — Backlog

## 1. Resumen del Estado
- **Última actualización**: 2026-08-12
- **Total ítems abiertos**: 11
- **En progreso**: 0
- **Bloqueados**: 1

## 2. Épicas

### Épica 1 — Unificación Visual del Dashboard (OperationsModule)
**Objetivo**: Estandarizar la UI del dashboard utilizando ApexCharts y mostrar indicadores correctos según la clasificación de Operadores.  
**Requisitos relacionados**: [[02-Requisitos#RF-03 — Operaciones: Adherencia y Dashboards en Vivo]]  
**Estado**: Por hacer

#### Features / Historias
| ID     | Título                                | Prioridad | Estado       | Estimación | Notas / Criterios de aceptación                                            |
|--------|---------------------------------------|-----------|--------------|------------|----------------------------------------------------------------------------|
| BL-001 | Estandarizar ApexCharts en Dashboard  | Media     | Por hacer    | Bajo       | Reemplazar SVG inline por `<x-apex-chart>`.                                |
| BL-002 | Asistencia/ausencia por Operador I/II | Alta      | Por hacer    | Medio      | Calcular métricas cruzando `position_id` con `agent_realtime_states`.      |
| BL-003 | Cobertura en vivo por equipo          | Alta      | Por hacer    | Medio      | `% de agentes en estado productivo / total agentes del equipo`.            |
| BL-004 | Colas desde Cisco (activas)           | Media     | Por hacer    | Bajo       | Filtrar CSQs con actividad en las últimas 24h.                             |
| BL-005 | Indicadores acumulados del día        | Media     | Por hacer    | Alto       | HeroKpiWidget con datos de `call_records` y `agent_call_performance`.      |

---

### Épica 2 — Autogestión (Leave Requests) (WfmModule)
**Objetivo**: Mejorar la experiencia de solicitud de permisos y cálculo de saldos disponibles.  
**Requisitos relacionados**: [[02-Requisitos#RF-04 — Autogestión de Personal y Flujos de Trabajo]]  
**Estado**: Por hacer

#### Features / Historias
| ID     | Título                                | Prioridad | Estado       | Estimación | Notas / Criterios de aceptación                                            |
|--------|---------------------------------------|-----------|--------------|------------|----------------------------------------------------------------------------|
| BL-010 | Cambio a periodo cuatrimestral        | Alta      | Por hacer    | Bajo       | Cambiar constante en cálculo de balances (Ene-Abr, May-Ago, Sep-Dic).      |
| BL-011 | Mostrar saldo de horas disponible     | Alta      | Por hacer    | Medio      | Restar horas tomadas del total cuatrimestral en `LeaveRequestForm`.        |
| BL-012 | Alerta visual límite de saldo         | Alta      | Por hacer    | Bajo       | Regla de validación y alerta visual si se excede el saldo.                 |

---

### Épica 3 — Planificación Semanal UX (WfmModule)
**Objetivo**: Facilitar la carga de planillas de horarios a los analistas WFM.  
**Requisitos relacionados**: [[02-Requisitos#RF-01 — Planificación y Gestión de Horarios (WFM)]]  
**Estado**: Por hacer

#### Features / Historias
| ID     | Título                                | Prioridad | Estado       | Estimación | Notas / Criterios de aceptación                                            |
|--------|---------------------------------------|-----------|--------------|------------|----------------------------------------------------------------------------|
| BL-020 | Copiar día a toda la semana           | Alta      | Por hacer    | Bajo       | Botón en UI para replicar un turno a múltiples días.                       |
| BL-021 | Validación de solapes y almuerzos     | Alta      | Por hacer    | Bajo       | Validar almuerzo (min 30m, 2h post inicio) y descanso en Livewire.         |
| BL-022 | Exportar Excel "Mi Equipo"            | Media     | Por hacer    | Medio      | Implementar exportación CSV natively (sin maatwebsite/laravel-excel).      |

---

### Épica 4 — Notificaciones y Ecosistema
**Objetivo**: Asegurar que todos los stakeholders reciban avisos en vivo de eventos importantes.  
**Requisitos relacionados**: [[02-Requisitos#RF-02 — Integración Cisco CTI (Tiempo real e histórico)]]  
**Estado**: Por hacer

#### Features / Historias
| ID     | Título                                | Prioridad | Estado       | Estimación | Notas / Criterios de aceptación                                            |
|--------|---------------------------------------|-----------|--------------|------------|----------------------------------------------------------------------------|
| BL-030 | Webex Notificaciones a involucrados   | Media     | Por hacer    | Medio      | Listeners para permisos, cambios de turno y caída de adherencia.           |
| BL-031 | Notificaciones Toast vía socket       | Media     | Por hacer    | Bajo       | Conectar `broadcastNotification` con el front via Laravel Echo + Reverb.   |

---

## 3. Backlog Priorizado (Vista plana)

| ID     | Título                              | Épica     | Prioridad | Estado        | Estimación | Dependencias |
|--------|-------------------------------------|-----------|-----------|---------------|------------|--------------|
| BL-010 | Cambio a periodo cuatrimestral      | Épica 2   | Alta      | Por hacer     | Bajo       | Ninguna      |
| BL-011 | Mostrar saldo de horas disponible   | Épica 2   | Alta      | Por hacer     | Medio      | BL-010       |
| BL-012 | Alerta visual límite de saldo       | Épica 2   | Alta      | Por hacer     | Bajo       | BL-011       |
| BL-002 | Asistencia/ausencia por Operador    | Épica 1   | Alta      | Por hacer     | Medio      | Ninguna      |
| BL-020 | Copiar día a toda la semana UX      | Épica 3   | Alta      | Por hacer     | Bajo       | Ninguna      |
| BL-021 | Validación solapes/almuerzos        | Épica 3   | Alta      | Por hacer     | Bajo       | Ninguna      |
| BL-003 | Cobertura en vivo por equipo        | Épica 1   | Alta      | Por hacer     | Medio      | Ninguna      |
| BL-022 | Exportar Excel "Mi Equipo"          | Épica 3   | Media     | Por hacer     | Medio      | Ninguna      |
| BL-030 | Webex Notificaciones a involucrados | Épica 4   | Media     | Por hacer     | Medio      | Ninguna      |
| BL-031 | Notificaciones Toast vía socket     | Épica 4   | Media     | Por hacer     | Bajo       | BL-030       |
| BL-001 | Estandarizar ApexCharts en Dashboard| Épica 1   | Media     | Por hacer     | Bajo       | Ninguna      |

## 4. Ítems Bloqueados
| ID     | Motivo del bloqueo                            | Desde      | Acción requerida                                      | Responsable |
|--------|-----------------------------------------------|------------|-------------------------------------------------------|-------------|
| BL-100 | Limpieza menús Operaciones (`ReportingModule`) | 2026-08-12 | Esperar a que el módulo de Reportes pase a producción | Equipo Dev  |

## 5. Definición de Hecho (DoD)
Un ítem se considera **Hecho** cuando:
- [ ] Cumple los criterios de aceptación (verificado manualmente).
- [ ] Pasa pruebas automatizadas (Pest PHP).
- [ ] No introduce queries N+1 ni fallas de tipado estricto.
- [ ] Utiliza componentes nativos de Flux UI para la vista (sin HTML crudo si es evitable).
- [ ] Código ha sido validado por `pint`.

## 6. Deuda Técnica
| ID     | Descripción                         | Impacto | Esfuerzo estimado | Prioridad |
|--------|-------------------------------------|---------|-------------------|-----------|
| DT-01  | Falta de tests en Dashboard y WFM   | Medio   | Alto              | Media     |
| DT-02  | Reestructuración de Módulo Export   | Bajo    | Medio             | Baja      |

## 7. Ideas / Icebox
- Implementación futura de un Dashboard ejecutivo consolidado (ReportingModule).
- Caché estricto en Redis para el mapeo `external_id -> employee_id` para reducir queries a BD en cada ciclo CTI.

## 8. Historial de Cambios Relevantes
| Fecha       | Cambio                              | Autor           |
|-------------|-------------------------------------|-----------------|
| 2026-08-12  | Backlog poblado desde `PENDIENTES_UI.md` | Sistema Agentic |

---

**Relacionado**
- [[02-Requisitos]]
- [[03-Arquitectura]]
- [[05-Decisiones]]
- [[00-Index]]