---
tipo: requisitos
proyecto: "HorariosWFM"
estado: borrador
fecha: 2026-08-12
tags:
  - proyecto
  - requisitos
---

# 02 — Requisitos

## 1. Resumen
HorariosWFM es un sistema monolítico modular (PHP/Laravel/Livewire) que gestiona y optimiza el capital humano del Call Center. El sistema debe importar planillas, asignar turnos, calcular la adherencia en tiempo real cruzando datos del horario contra la conexión del agente en Cisco Finesse, y permitir la autogestión automatizada para los empleados.

## 2. Requisitos Funcionales Principales

### RF-01 — Planificación y Gestión de Horarios (WFM)
**Prioridad**: Crítica  
**Descripción**: El sistema debe permitir importar, crear y publicar horarios semanales (turnos, breaks, excepciones) para todos los operadores, verificando que no existan colisiones.
**Criterios de Aceptación**:
- [ ] Importación masiva de planillas desde Excel (modelos Erlang-C).
- [ ] Validación automática de colisiones sin traslapes para los agentes.
- [ ] Publicación de horarios con notificación inmediata a los agentes.
- [ ] Gestión de actividades intra-día (breaks, almuerzos, capacitaciones).

**Notas**: Representa el core funcional inicial (WfmModule).

---

### RF-02 — Integración Cisco CTI (Tiempo real e histórico)
**Prioridad**: Crítica  
**Descripción**: Sincronización continua de estados de agente y CDRs (Call Detail Records) con las plataformas Cisco UCCX, Finesse y CUIC.
**Criterios de Aceptación**:
- [ ] Sincronización programada de estados (Finesse) cada 5 segundos durante horario laboral.
- [ ] Sincronización ETL histórica desde bases de datos de Cisco (CUIC).
- [ ] Mapeo automático de estados nativos de Cisco a estados estándar del sistema.
- [ ] Resiliencia implementada (circuit breaker, backoff exponencial) ante caídas de Cisco.

**Notas**: Manejado por el ConnectModule, es la fuente de verdad técnica de la actividad del operador.

---

### RF-03 — Operaciones: Adherencia y Dashboards en Vivo
**Prioridad**: Crítica  
**Descripción**: Monitoreo en tiempo real para supervisores, reconciliando lo planificado contra lo real.
**Criterios de Aceptación**:
- [ ] Reconciliación automática y cálculo de adherencia en tiempo real.
- [ ] Dashboard de monitoreo en vivo con widgets actualizados sin recargar (Livewire/Reverb).
- [ ] Línea de tiempo visual (Gantt) comparando lo planificado vs real por agente.
- [ ] Generación automática de incidencias (tardanzas, ausencias).

**Notas**: Provee la visibilidad gerencial principal (OperationsModule).

---

### RF-04 — Autogestión de Personal y Flujos de Trabajo
**Prioridad**: Alta  
**Descripción**: Portal de autogestión para los operadores y flujos de aprobación (Workflows) multinivel.
**Criterios de Aceptación**:
- [ ] Solicitud y aprobación de intercambios de turno entre agentes.
- [ ] Solicitud de permisos, justificaciones y vacaciones.
- [ ] Máquina de estados estricta (Pending → Approved → Rejected) con flujo: Supervisor → WFM → RRHH.
- [ ] Trazabilidad e historial inmutable en las aprobaciones y rechazos.

**Notas**: Mejora drásticamente la experiencia del empleado y reduce carga administrativa.

---

### RF-05 — Control de Identidad y Auditoría
**Prioridad**: Crítica  
**Descripción**: Seguridad robusta, autenticación 2FA, RBAC y auditoría inmutable de todo el sistema.
**Criterios de Aceptación**:
- [ ] Login con email, contraseña y doble factor de autenticación (TOTP).
- [ ] Roles y permisos granulares según la jerarquía (Spatie Permission).
- [ ] Registro automático de cambios críticos (estado before/after en formato JSON).

**Notas**: Requisito base transversal (CoreModule y AuditModule).

## 3. Requisitos No Funcionales

| ID     | Categoría          | Requisito                              | Métrica / Criterio          | Prioridad |
|--------|--------------------|----------------------------------------|-----------------------------|-----------|
| RNF-01 | Rendimiento        | Carga Dashboard / Autenticación        | Carga < 2s / Búsqueda < 5ms | Crítica   |
| RNF-02 | Disponibilidad     | Resiliencia Cisco (Circuit breaker)    | Fallback a datos cacheados  | Crítica   |
| RNF-03 | Consistencia       | Prevención de queries N+1              | PreventLazyLoading activo   | Alta      |
| RNF-04 | Mantenibilidad     | Strict types obligatorio en PHP        | declare(strict_types=1)     | Alta      |
| RNF-05 | Seguridad          | Trazabilidad Auditoría (Inmutabilidad) | Append-only (sin deletes)   | Crítica   |
| RNF-06 | Seguridad          | Privacidad de Datos Sensibles          | Cifrado de DB (médicos/$$)  | Alta      |
| RNF-07 | Rendimiento        | Sincronización CTI rápida y masiva     | Upserts en batches de 1000  | Crítica   |

## 4. Reglas de Negocio
1. **Adherencia estricta**: El cálculo de adherencia depende del cruce exacto entre el turno asignado y los eventos en vivo del CTI. Si Cisco cae, la adherencia queda "Pendiente de Validación Manual".
2. **Auditoría inmutable**: Cualquier acción que cambie el estado de un turno, empleado o sistema debe persistir un log de auditoría (JSON Before/After) que no puede ser alterado o borrado, ni siquiera por el super administrador.
3. **Control de Ausencias**: Toda ausencia genera una incidencia que requiere un flujo de justificación automatizado si no ha sido reportada y aprobada con anticipación.

## 5. Restricciones Técnicas
- **Integración Obligatoria**: Lecturas de bases de datos y APIs REST XML de infraestructura existente: Cisco Finesse y CUIC.
- **Stack Tecnológico Estricto**: Uso exclusivo de PHP 8.3+, Laravel 13, Livewire 4, Flux UI 2 y PostgreSQL 16.
- **Arquitectura de Software**: Monolito Modular con comunicación inter-módulo exclusiva por *Events* y *DTOs*. Se prohíben llamadas directas a modelos Eloquent entre módulos (desacoplamiento).