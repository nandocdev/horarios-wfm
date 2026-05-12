# Supplementary Modules (Helpdesk, Operations, Support)

## 🎯 Propósito General
Este conjunto de módulos proporciona funcionalidades complementarias y de soporte administrativo que extienden las capacidades core de WFM, permitiendo una gestión integral del entorno de trabajo.

---

## 🚀 HelpdeskModule
Módulo diseñado para la gestión de solicitudes internas y soporte técnico dentro de la organización.
- **Tickets Internos:** Permite a los colaboradores abrir tickets de soporte (`HelpdeskTicket`) para reportar incidencias.
- **Categorización:** Clasificación de solicitudes (ej: Soporte IT, Mantenimiento, Consultas de Nómina).
- **Colaboración:** Soporta comentarios internos (`HelpdeskTicketComment`) para el seguimiento y resolución de casos.

---

## 🚀 OperationsModule
Módulo enfocado en la gestión de incidencias operativas y ajustes detallados de la jornada.
- **Incidentes de Asistencia:** Registro de `AttendanceIncident` para documentar eventos imprevistos que afectan la puntualidad o presencia del colaborador.
- **Actividades Intradiarias:** Proporciona modelos adicionales para la asignación de tareas específicas durante el turno (`IntradayActivityAssignment`), complementando la planificación de `WfmModule`.
- **Tipificación:** Catálogo de `IncidentType` para estandarizar los reportes operativos.

---

## 🚀 SupportModule
Módulo de utilidades transversales. Actualmente contiene implementaciones de bitácoras de auditoría que sirven como respaldo o base para procesos de diagnóstico.

---

## 🛠 Estructura Técnica

### Modelos Clave
- `HelpdeskTicket`: Entidad de soporte.
- `AttendanceIncident`: Registro de excepciones operativas.
- `AuditLog` (Support): Bitácora de eventos del sistema.

---

## ⚠️ [RIESGOS / DEUDA TÉCNICA]
1. **Duplicidad de Modelos:** Se identifica la presencia de modelos `AuditLog` y `IntradayActivity` en múltiples módulos (`AuditModule`, `WfmModule`, `OperationsModule`, `SupportModule`). Esto sugiere una necesidad de refactorización para centralizar estas entidades y evitar inconsistencias.
2. **Madurez del Código:** A diferencia de los módulos core, estos módulos presentan menos lógica encapsulada en `Actions`, delegando posiblemente más responsabilidad a los componentes de UI.
3. **Fronteras Difusas:** La línea entre una "Excepción de Horario" (en `WfmModule`) y un "Incidente de Asistencia" (en `OperationsModule`) no es del todo clara en la implementación actual.
