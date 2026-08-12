---
tipo: vision-alcance
proyecto: "HorariosWFM"
estado: borrador
fecha: 2026-08-12
tags:
  - proyecto
  - vision
  - alcance
---

# 01 — Visión y Alcance

## 1. Resumen Ejecutivo
HorariosWFM es un sistema integral de Workforce Management diseñado para profesionalizar la gestión de turnos, el rendimiento operativo y la calidad del servicio en el Call Center de la Caja de Seguro Social de Panamá. Reemplaza procesos manuales basados en hojas de cálculo y correos electrónicos por una plataforma digital integrada que garantiza transparencia, eficiencia y equidad para cada operador y coordinador.

## 2. Problema / Oportunidad

- **Situación actual**: Planificación manual con hojas de cálculo (propensas a errores), seguimiento inexistente o reactivo de adherencia y asistencia, y procesos de autogestión limitados que dependen de correos electrónicos.
- **Impacto del problema**: Decisiones administrativas y operativas lentas, falta de visibilidad en tiempo real para los supervisores, y experiencia deficiente para los agentes en la gestión de sus horarios.
- **Por qué ahora**: Se requiere un sistema integrado con la telefonía (Cisco UCCX/Finesse) para automatizar el control del capital humano, mejorando el rendimiento general y reduciendo drásticamente el tiempo operativo de la gerencia.

## 3. Objetivos del Proyecto

1. Reducir el tiempo de planificación semanal de horarios de horas a menos de 30 minutos.
2. Mejorar la adherencia de los agentes mediante visibilidad en tiempo real (> 90%).
3. Automatizar la detección de incidencias de asistencia (tardanzas, ausencias).
4. Empoderar a los empleados para gestionar más del 80% de sus solicitudes (permisos, vacaciones, cambios) vía autogestión en el sistema.

### Objetivos No Relacionados (Fuera de Alcance explícito)
- Módulo de nómina/payroll
- Reclutamiento y selección (applicant tracking)
- Evaluación de desempeño 360° y Gamificación
- Portal de autogestión para el asegurado (cliente externo)

## 4. Alcance

### Incluido (In-Scope)
- **Gestión de Horarios (WFM)**: Planificación semanal, asignación masiva, validación de colisiones y excepciones.
- **Integración Cisco CTI**: Sincronización en tiempo real (Finesse) e histórica (CUIC) de estados de agentes y llamadas.
- **Operaciones y Adherencia**: Reconciliación automática planificado vs real, dashboards y scorecards en tiempo real.
- **Autogestión de Personal**: Flujos multinivel para aprobación de permisos e intercambios de turnos.
- **Evaluación de Calidad (QA)**: Rúbricas de evaluación de llamadas y procesos de disputa.

### Excluido (Out-of-Scope)
- Aplicación móvil nativa (la UI será web responsive vía Livewire/Tailwind).
- Integración con Active Directory / LDAP.
- Machine learning para predicción de demanda (se importarán planillas Erlang-C externas).
- Chatbot interno.

## 5. Stakeholders
| Rol              | Nombre / Área     | Interés / Responsabilidad          | Poder |
|------------------|-------------------|------------------------------------|-------|
| Sponsor          | Alta Dirección CSS| Eficiencia operativa del servicio  | Alto  |
| Product Owner    | WFM Manager       | Priorización funcional             | Alto  |
| Usuarios clave   | Supervisores/Ops  | Monitoreo, KPIs y aprobaciones     | Medio |
| Técnico          | IT / Soporte      | Infraestructura, Integración Cisco | Medio |

## 6. Restricciones
- **Tecnológicas**: Integración obligatoria con infraestructura heredada de Cisco (UCCX/CUIC/Finesse) mediante bases de datos y APIs. Stack definido: PHP 8.3+, Laravel 13 y PostgreSQL 16.
- **Rendimiento**: Carga inicial del dashboard en < 2s, sincronización de Cisco cada 5s.
- **Cumplimiento y Privacidad**: Datos médicos y salariales cifrados en reposo, y uso de RBAC estricto según jerarquía de rol.
- **Auditoría**: Inmutabilidad de los registros de auditoría (sin endpoints de update/delete).

## 7. Supuestos
- Red e infraestructura estable para mantener la sincronización continua en tiempo real con Cisco Finesse.
- Disponibilidad del equipo de la Caja de Seguro Social para la validación de reglas de negocio y UAT (User Acceptance Testing).

## 8. Criterios de Éxito
- Reducción del tiempo de creación de planillas a minutos.
- Detección de incidencias en la asistencia en < 1 hora del evento.
- Sincronización efectiva con el CTI en < 30 segundos por ciclo.
- Adopción exitosa de autogestión por parte de los agentes.

## 9. Riesgos Principales (alto nivel)
| Riesgo                        | Probabilidad | Impacto | Mitigación inicial     |
|-------------------------------|--------------|---------|------------------------|
| Caída de infraestructura Cisco| Media        | Crítico | Circuit breaker, fallback a "stale data" con alertas y colas independientes. |
| Volumen masivo de CDRs        | Alta         | Alto    | Upserts por lotes (chunks de 1000) y uso de tablas UNLOGGED para datos en vivo. |
| Dead cycle en CiscoSync       | Alta         | Crítico | Health check previo, re-despacho con backoff exponencial, y notificación a Webex. |

## 10. Próximos Pasos
- [x] Validar esta visión con el Product Owner (CSS).
- [ ] Llenar el documento detallado de requisitos → [[02-Requisitos]].
- [ ] Refinar las épicas en el [[04-Backlog]].

---

**Relacionado**
- [[00-Index]]
- [[02-Requisitos]]
- [[06-Riesgos]]