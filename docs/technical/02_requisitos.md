# Requisitos del Sistema (SRS) — Sistema WFM Call Center CSS

**Proyecto:** Sistema de Gestión de Horarios (WFM) — Call Center CSS
**Versión:** v1.0
**Fecha:** Febrero 2026
**Stack:** PHP 8.3 · Laravel 12 · PostgreSQL 16
**Fase RUP:** Elaboración

---

## 1. Introducción

Este documento define de forma completa, explícita y estructurada los requisitos del Sistema de Gestión de Horarios (WFM) para el Call Center de la CSS. Sirve como referencia formal para el desarrollo, validación y aceptación del sistema.

> **Nota:** Todos los requisitos aquí definidos derivan del Documento de Visión. Cualquier cambio debe ser evaluado contra ese documento para evitar desviaciones del propósito original.

---

## 2. Clasificación de Requisitos

| Prefijo | Tipo           | Descripción                             |
| ------- | -------------- | --------------------------------------- |
| RF      | Funcionales    | Comportamiento observable del sistema   |
| RNF     | No Funcionales | Calidad, rendimiento, usabilidad        |
| RD      | Datos          | Persistencia, integridad, historial     |
| RS      | Seguridad      | Autenticación, autorización, protección |
| RA      | Auditoría      | Trazabilidad y cumplimiento             |

---

## 3. Requisitos Funcionales

### 3.1 Autenticación y Acceso

| ID    | Descripción                                                                 | Prioridad |
| ----- | --------------------------------------------------------------------------- | --------- |
| RF-01 | El sistema permite autenticación con credenciales únicas (email + password) | Alta      |
| RF-02 | El sistema permite activar o desactivar usuarios sin eliminar su historial  | Alta      |
| RF-03 | El sistema registra fecha y hora del último acceso exitoso                  | Media     |
| RF-04 | El sistema soporta recuperación de contraseña vía correo electrónico        | Media     |
| RF-05 | Las sesiones expiran automáticamente tras inactividad configurable          | Alta      |

### 3.2 Gestión de Usuarios

| ID    | Descripción                                                             | Prioridad |
| ----- | ----------------------------------------------------------------------- | --------- |
| RF-06 | El sistema permite crear, editar y desactivar usuarios                  | Alta      |
| RF-07 | El sistema permite asignar uno o varios roles a un usuario              | Alta      |
| RF-08 | El sistema permite asociar un usuario a un registro de empleado         | Alta      |
| RF-09 | El sistema permite listar usuarios con filtros por rol, estado y equipo | Media     |

### 3.3 Gestión de Roles y Permisos

| ID    | Descripción                                                                             | Prioridad |
| ----- | --------------------------------------------------------------------------------------- | --------- |
| RF-10 | El sistema define roles jerárquicos: Operador(1) -> Supervisor(2) -> Coordinador(3)     | Alta      |
| RF-11 | El sistema asigna permisos atómicos a roles mediante tabla pivote                       | Alta      |
| RF-12 | El sistema valida permisos y alcance por `team_id` antes de ejecutar acciones sensibles | Alta      |
| RF-13 | Las Policies de Laravel controlan el acceso a nivel de entidad                          | Alta      |

### 3.4 Gestión de Empleados

| ID    | Descripción                                                                                                                   | Prioridad |
| ----- | ----------------------------------------------------------------------------------------------------------------------------- | --------- |
| RF-14 | El sistema permite registrar empleados mediante carga inicial CSV                                                             | Alta      |
| RF-15 | El sistema permite editar información laboral: cargo, equipo, estado                                                          | Alta      |
| RF-16 | El sistema define jerarquía plana por equipo: Operadores y Supervisores (Operador II) reportan al Coordinador vía `parent_id` | Alta      |
| RF-17 | El sistema permite cambiar el estado laboral (activo, suspendido, retirado)                                                   | Alta      |
| RF-18 | El sistema permite buscar empleados por nombre, código, equipo y cargo                                                        | Media     |

### 3.5 Catálogos del Sistema

| ID    | Descripción                                                     | Prioridad |
| ----- | --------------------------------------------------------------- | --------- |
| RF-19 | El sistema administra Direcciones (`directorates`)              | Media     |
| RF-20 | El sistema administra Departamentos (`departments`)             | Media     |
| RF-21 | El sistema administra el catálogo de cargos (`positions`)       | Media     |
| RF-22 | El sistema administra el catálogo de equipos (`teams`)          | Media     |
| RF-23 | El sistema administra tipos de excepciones (`exception_types`)  | Media     |
| RF-24 | El sistema administra estados laborales (`employment_statuses`) | Media     |

### 3.6 Gestión de Horarios

| ID    | Descripción                                                                                  | Prioridad |
| ----- | -------------------------------------------------------------------------------------------- | --------- |
| RF-25 | El sistema permite definir turnos base reutilizables (`schedules`)                           | Alta      |
| RF-26 | Solo el rol WFM puede crear planificación semanal (`weekly_schedules`) por `week_start_date` | Alta      |
| RF-27 | Solo el rol WFM puede asignar turnos por semana (`weekly_schedule_assignments`)              | Alta      |
| RF-28 | El sistema guarda planificación semanal en estado inicial `draft`                            | Alta      |
| RF-29 | Solo el rol WFM puede publicar planificación semanal cambiando a estado `published`          | Alta      |
| RF-30 | El operador solo visualiza su horario cuando la semana está `published`                      | Alta      |
| RF-31 | El sistema valida solapamientos de asignaciones semanales antes de guardar                   | Alta      |

### 3.7 Excepciones Laborales

| ID    | Descripción                                                                           | Prioridad |
| ----- | ------------------------------------------------------------------------------------- | --------- |
| RF-32 | El sistema permite registrar excepciones: vacaciones, incapacidades, licencias        | Alta      |
| RF-33 | El sistema soporta excepciones de rango de fechas (`start_date` / `end_date`)         | Alta      |
| RF-34 | Las excepciones marcadas con `affects_schedule=true` sobrescriben el horario efectivo | Alta      |
| RF-35 | El sistema valida conflictos entre excepciones existentes antes de guardar            | Alta      |

### 3.8 Permisos y Licencias

| ID    | Descripción                                                                                           | Prioridad |
| ----- | ----------------------------------------------------------------------------------------------------- | --------- |
| RF-36 | El sistema permite solicitar permisos parciales (rango horario) o totales (día completo)              | Alta      |
| RF-37 | El sistema aplica flujo de aprobación de un solo paso: aprobación del Coordinador del mismo `team_id` | Alta      |
| RF-38 | El sistema permite rechazar solicitudes con justificación textual obligatoria                         | Alta      |
| RF-39 | El sistema notifica al solicitante el resultado de su solicitud                                       | Media     |

### 3.9 Cambios de Turno

| ID    | Descripción                                                                       | Prioridad |
| ----- | --------------------------------------------------------------------------------- | --------- |
| RF-40 | El sistema permite solicitar cambio de turno entre dos empleados del mismo rol    | Alta      |
| RF-41 | El sistema valida compatibilidad de horarios entre los dos empleados involucrados | Alta      |
| RF-42 | El empleado destino debe aceptar explícitamente el cambio propuesto               | Alta      |
| RF-43 | El cambio requiere aprobación final única del Coordinador del mismo `team_id`     | Alta      |

### 3.10 Asistencia

| ID    | Descripción                                                                                     | Prioridad |
| ----- | ----------------------------------------------------------------------------------------------- | --------- |
| RF-44 | El sistema permite al Coordinador registrar incidencias de asistencia en `attendance_incidents` | Alta      |
| RF-45 | El sistema permite al Coordinador editar incidencias con trazabilidad auditada                  | Alta      |
| RF-46 | El sistema permite vincular incidencias a excepciones o permisos aprobados                      | Alta      |
| RF-47 | El sistema permite consultar historial de asistencia por empleado y rango de fechas             | Media     |

### 3.11 Planificación Intradía

| ID    | Descripción                                                                                                 | Prioridad |
| ----- | ----------------------------------------------------------------------------------------------------------- | --------- |
| RF-48 | El sistema permite crear actividades intradía (`intraday_activities`) con tipo, horario y cupo máximo       | Alta      |
| RF-49 | El sistema permite asignar operadores a actividades (`intraday_activity_assignments`) sin exceder capacidad | Alta      |
| RF-50 | El operador puede visualizar sus actividades intradía junto con su turno general                            | Alta      |

### 3.12 Gestión de Descansos

| ID    | Descripción                                                                                             | Prioridad |
| ----- | ------------------------------------------------------------------------------------------------------- | --------- |
| RF-51 | El sistema permite definir plantillas de descanso (`break_templates`)                                   | Alta      |
| RF-52 | Solo WFM puede vincular una plantilla de descanso a una asignación semanal específica                   | Alta      |
| RF-53 | El Coordinador puede sobrescribir temporalmente descansos por fecha usando `employee_break_overrides`   | Alta      |
| RF-54 | El sistema valida que la sobrescritura no solape actividades críticas ni reglas de negocio de cobertura | Alta      |

### 3.13 Información Médica y Dependientes

| ID    | Descripción                                                                               | Prioridad |
| ----- | ----------------------------------------------------------------------------------------- | --------- |
| RF-55 | El sistema permite registrar y mantener dependientes del empleado (`employee_dependents`) | Alta      |
| RF-56 | El sistema permite registrar discapacidades del empleado (`employee_disabilities`)        | Alta      |
| RF-57 | El sistema permite registrar enfermedades crónicas del empleado (`employee_diseases`)     | Alta      |
| RF-58 | El operador puede consultar su información médica y familiar en modo lectura              | Media     |

### 3.14 Operación de Piso (Supervisor / Operador II)

| ID    | Descripción                                                                                                   | Prioridad |
| ----- | ------------------------------------------------------------------------------------------------------------- | --------- |
| RF-59 | El Supervisor (Operador II) conserva capacidades de Operador y no participa en aprobaciones de Workflow       | Alta      |
| RF-60 | El Supervisor dispone de permisos de solo lectura del equipo (ej. `team_schedules.view`) para soporte técnico | Alta      |
| RF-61 | El Supervisor puede consultar disponibilidad intradía del equipo sin modificar planificación ni aprobaciones  | Alta      |

---

## 4. Requisitos de Datos

| ID    | Descripción                                                                            | Prioridad |
| ----- | -------------------------------------------------------------------------------------- | --------- |
| RD-01 | El sistema conserva historial completo de cambios en entidades críticas                | Alta      |
| RD-02 | El sistema usa soft deletes (`deleted_at`) en entidades críticas: employees, schedules | Alta      |
| RD-03 | El sistema mantiene integridad referencial mediante foreign keys en PostgreSQL         | Alta      |
| RD-04 | Las fechas se almacenan en UTC y se presentan en zona horaria de Panamá (UTC-5)        | Alta      |
| RD-05 | Los campos de estado se gestionan mediante Enums de PHP 8.1+ y columnas string en BD   | Media     |

---

## 5. Requisitos de Seguridad

| ID    | Descripción                                                                | Prioridad |
| ----- | -------------------------------------------------------------------------- | --------- |
| RS-01 | Toda ruta protegida requiere token Sanctum o sesión activa válida          | Alta      |
| RS-02 | Las acciones se restringen por rol Y por jerarquía organizacional          | Alta      |
| RS-03 | Los passwords se almacenan con bcrypt (costo mínimo: 12)                   | Alta      |
| RS-04 | Los tokens de recuperación expiran en 60 minutos                           | Alta      |
| RS-05 | Se aplica rate limiting en endpoints de autenticación: 5 intentos / minuto | Alta      |

---

## 6. Requisitos No Funcionales

| ID     | Descripción                                                                   | Prioridad |
| ------ | ----------------------------------------------------------------------------- | --------- |
| RNF-01 | El sistema responde en < 500ms al 95% de las peticiones bajo carga normal     | Alta      |
| RNF-02 | El sistema es usable por personal no técnico sin capacitación mayor a 2 horas | Alta      |
| RNF-03 | El código sigue las convenciones PSR-12 y las guías de estilo de Laravel      | Media     |
| RNF-04 | La cobertura de pruebas unitarias mínima es del 60% en Services               | Media     |
| RNF-05 | El sistema se despliega en servidor único Nginx + PHP-FPM + PostgreSQL        | Alta      |

---

## 7. Requisitos de Auditoría

| ID    | Descripción                                                                          | Prioridad |
| ----- | ------------------------------------------------------------------------------------ | --------- |
| RA-01 | Toda acción CRUD sobre entidades críticas genera un registro en `audit_logs`         | Alta      |
| RA-02 | El log de auditoría almacena: usuario, entidad, ID, acción, before, after, timestamp | Alta      |
| RA-03 | Los registros de auditoría son inmutables (sin UPDATE ni DELETE permitidos)          | Alta      |
| RA-04 | El sistema permite consultar la auditoría de cualquier entidad por rango de fecha    | Media     |

---

## 8. Criterios de Aceptación

> El sistema se considerará aceptado cuando cumpla el **100%** de los requisitos de prioridad **Alta** y el **80%** de los requisitos de prioridad **Media**, validados mediante pruebas de aceptación con usuarios representativos de cada rol.
