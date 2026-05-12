# Documento de Visión — Sistema WFM Call Center CSS

**Proyecto:** Sistema de Gestión de Horarios (WFM) — Call Center CSS
**Versión:** v1.0
**Fecha:** Febrero 2026
**Stack:** PHP 8.3 · Laravel 13 · PostgreSQL 16
**Fase RUP:** Elaboración

---

## 1. Introducción

### 1.1 Propósito del documento

Este documento define la visión del Sistema de Gestión de Horarios (WFM) para el Call Center de la Caja de Seguro Social (CSS). Establece el problema de negocio a resolver, los objetivos del sistema, los actores involucrados, las capacidades clave y las restricciones del proyecto. Sirve como referencia de alineación entre stakeholders, analistas y el equipo de desarrollo durante todas las fases del ciclo de vida.

### 1.2 Alcance

El sistema cubre la planificación semanal de turnos, la gestión de excepciones laborales, el registro de asistencia, la administración de permisos, los cambios de turno y la planificación intradía para el personal operativo del Call Center. No incluye nómina, control de acceso físico ni integración con sistemas externos fuera del alcance definido en este documento.

### 1.3 Definiciones y acrónimos

| Término               | Definición                                                                                |
| --------------------- | ----------------------------------------------------------------------------------------- |
| WFM                   | Workforce Management — Gestión de la fuerza laboral                                       |
| CSS                   | Caja de Seguro Social de Panamá                                                           |
| Turno                 | Horario base asignado a un empleado para una semana laboral                               |
| Excepción             | Evento que altera el horario efectivo de un empleado (vacación, incapacidad, licencia)    |
| Incidencia            | Registro de asistencia con desviación respecto al turno planificado                       |
| Planificación semanal | Conjunto de asignaciones de turnos para todos los empleados de una semana determinada     |
| Intradía              | Actividades que ocurren dentro del turno de trabajo (reuniones, capacitaciones, coaching) |

---

## 2. Posicionamiento

### 2.1 Oportunidad de negocio

El Call Center de la CSS opera con personal distribuido en múltiples equipos y turnos rotativos. La ausencia de un sistema centralizado genera inconsistencias en la planificación, dificultades en el seguimiento de asistencia y retrasos en la aprobación de permisos. Estos problemas afectan directamente la cobertura operativa y la calidad del servicio ciudadano.

### 2.2 Declaración del problema

|                                |                                                                                                                                                                          |
| ------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| **El problema de**             | la gestión manual y fragmentada de horarios, permisos y asistencia                                                                                                       |
| **afecta a**                   | operadores, coordinadores, analistas WFM y la dirección del Call Center                                                                                                  |
| **cuyo impacto es**            | baja visibilidad operativa, errores en planificación, procesos de aprobación lentos y ausencia de trazabilidad                                                           |
| **una solución exitosa sería** | un sistema centralizado que automatice la planificación semanal, gestione solicitudes con flujos de aprobación claros y proporcione información confiable en tiempo real |

### 2.3 Declaración de posición del producto

|                      |                                                                                                                   |
| -------------------- | ----------------------------------------------------------------------------------------------------------------- |
| **Para**             | el personal del Call Center CSS (operadores, coordinadores, analistas WFM, jefes y dirección)                     |
| **que**              | necesita planificar, gestionar y supervisar horarios, permisos y asistencia de forma eficiente                    |
| **el sistema WFM**   | es una aplicación web centralizada de gestión de fuerza laboral                                                   |
| **que**              | automatiza la planificación semanal, estandariza los flujos de aprobación y provee visibilidad operativa completa |
| **a diferencia de**  | hojas de cálculo, correos electrónicos y procesos manuales dispersos                                              |
| **nuestro producto** | garantiza trazabilidad, control jerárquico y cumplimiento de las reglas operativas del Call Center                |

---

## 3. Stakeholders y Usuarios

### 3.1 Resumen de stakeholders

| Stakeholder               | Rol en el proyecto    | Interés principal                                   |
| ------------------------- | --------------------- | --------------------------------------------------- |
| Dirección del Call Center | Patrocinador          | Visibilidad global, KPIs operativos                 |
| Jefatura de Operaciones   | Usuario clave         | Aprobación de excepciones, reportes consolidados    |
| Coordinadores de Equipo   | Usuario primario      | Gestión diaria: permisos, asistencia, planificación |
| Analistas Workforce (WFM) | Usuario especializado | Planificación semanal, configuración del sistema    |
| Administrador de TI       | Responsable técnico   | Estabilidad, seguridad, mantenimiento               |

### 3.2 Perfiles de usuario

#### Operador
Empleado de primera línea del Call Center. Consulta su horario, solicita permisos y visualiza sus actividades del día. Interacción principalmente de lectura y solicitudes.

#### Supervisor (Operador II)
Perfil operativo sin responsabilidades administrativas en el workflow. Tiene visibilidad de lectura sobre el equipo para soporte en piso y puede escalar incidencias al coordinador.

#### Coordinador
Administrador directo de un único equipo. Gestiona la aprobación de permisos y cambios de turno, registra incidencias de asistencia y supervisa la planificación operativa. Es el único aprobador en el flujo de solicitudes.

#### Analista Workforce (WFM)
Rol transversal con acceso funcional amplio. Responsable exclusivo de crear, editar y publicar la planificación semanal, gestionar el catálogo de horarios, asignar plantillas de descanso y ejecutar operaciones masivas.

#### Jefe
Acceso consolidado a múltiples equipos. Aprueba excepciones especiales, vacaciones largas e incapacidades prolongadas. Visualiza reportes de toda su unidad.

#### Director
Visión global del Call Center. Aprueba permisos de jefes, autoriza excepciones institucionales y consulta indicadores estratégicos de cobertura y ausentismo.

#### Administrador del Sistema
Gestiona usuarios, roles, permisos y catálogos maestros. Importa empleados desde CSV y audita acciones del sistema.

---

## 4. Capacidades del Sistema

### 4.1 Autenticación y seguridad
Sesiones gestionadas con tokens Sanctum. Aplica rate limiting en login, expiración automática de sesiones y recuperación de contraseña con tokens de vida limitada (60 minutos).

### 4.2 Gestión de usuarios, roles y permisos
RBAC con jerarquía definida. Las políticas de Laravel validan el alcance por `team_id` y `parent_id` antes de ejecutar cualquier acción sensible.

### 4.3 Gestión de empleados y estructura organizacional
Estructura corporativa completa: Dirección → Departamento → Cargo → Empleado. Jerarquía por lista de adyacencia (`parent_id`). Soporta importación masiva desde CSV.

### 4.4 Planificación semanal de turnos
Exclusiva del rol WFM. Ciclo `draft → published`. Los operadores solo visualizan su turno cuando la semana está publicada.

### 4.5 Gestión de descansos
Plantillas de descanso reutilizables vinculadas a asignaciones semanales. Los coordinadores pueden sobrescribir descansos de forma temporal.

### 4.6 Planificación intradía
Actividades dentro del turno (capacitaciones, reuniones, coaching) con control de cupo y validación de conflictos. Visibles al operador junto a su turno general.

### 4.7 Permisos y licencias
Solicitudes totales o parciales con flujo de aprobación de un único paso por el coordinador del equipo.

### 4.8 Cambios de turno
Solicitud de intercambio entre dos operadores. Requiere aceptación del empleado destino y aprobación final del coordinador.

### 4.9 Incidencias de asistencia
Registro por el coordinador de tardanzas, inasistencias y salidas anticipadas, vinculables a permisos aprobados.

### 4.10 Excepciones laborales
Vista unificada que agrupa permisos aprobados e incidencias justificadas. El rol WFM puede aplicar excepciones masivas y forzar aprobaciones institucionales.

### 4.11 Información médica y dependientes
Almacena dependientes, discapacidades y enfermedades crónicas. El operador las consulta en modo lectura.

### 4.12 Auditoría y trazabilidad
Registro inmutable en `audit_logs` por cada acción CRUD sobre entidades críticas. Sin eliminación física de datos históricos.

### 4.13 Reportes y exportaciones
Reportes de asistencia, cumplimiento y cobertura. Exportación en CSV y Excel disponible para WFM y niveles superiores.

---

## 5. Restricciones y Supuestos

### 5.1 Restricciones técnicas
- Despliegue en servidor único: Nginx + PHP-FPM + PostgreSQL 16.
- Stack backend fijo: PHP 8.3 + Laravel 12.
- Sin integraciones con sistemas externos en v1.0.

### 5.2 Restricciones de negocio
- Flujo de aprobación de un solo nivel (coordinador).
- Planificación semanal exclusiva del rol WFM.
- Supervisores sin participación en aprobaciones ni planificación.
- Registros históricos inmutables.

### 5.3 Supuestos
- Cada coordinador administra un único equipo.
- Todo empleado tiene un único jefe directo (`parent_id`).
- Zona horaria UTC-5 (Panamá). Fechas almacenadas en UTC.
- Acceso desde navegador web moderno.

---

## 6. Calidad y Criterios de Éxito

| Atributo       | Criterio                                              |
| -------------- | ----------------------------------------------------- |
| Rendimiento    | < 500ms en el 95% de las peticiones bajo carga normal |
| Usabilidad     | Operable sin capacitación mayor a 2 horas             |
| Seguridad      | RBAC + jerarquía + bcrypt cost 12                     |
| Mantenibilidad | PSR-12 · cobertura mínima 60% en Services             |
| Aceptación     | 100% requisitos Alta · 80% requisitos Media           |

---

## 7. Precedencia y Prioridades

| Prioridad   | Módulo                                                            |
| ----------- | ----------------------------------------------------------------- |
| 1 — Crítico | Autenticación · Usuarios y roles · Estructura organizacional      |
| 2 — Alto    | Planificación semanal · Horarios base · Descansos                 |
| 3 — Alto    | Permisos · Cambios de turno · Incidencias de asistencia           |
| 4 — Medio   | Planificación intradía · Excepciones masivas · Información médica |
| 5 — Medio   | Reportes · Exportaciones · Dashboard KPIs                         |

---

*Documento generado como parte del proceso RUP — Fase de Elaboración. Versión sujeta a revisión por los stakeholders del proyecto.*
