# Casos de Uso — Sistema WFM Call Center CSS

**Proyecto:** Sistema de Gestión de Horarios (WFM) — Call Center CSS
**Versión:** v1.0
**Fecha:** Febrero 2026
**Stack:** PHP 8.3 · Laravel 12 · PostgreSQL 16
**Fase RUP:** Elaboración

---

## 1. Convenciones

| Prefijo | Rol                                             |
| ------- | ----------------------------------------------- |
| UC-COM  | Casos comunes — todos los usuarios autenticados |
| UC-OP   | Operador                                        |
| UC-SUP  | Supervisor (Operador II)                        |
| UC-COOR | Coordinador                                     |
| UC-JEF  | Jefe                                            |
| UC-WFM  | Analista Workforce                              |
| UC-DIR  | Director                                        |
| UC-ADM  | Administrador del Sistema                       |
| UC-INT  | Intrínsecos del Sistema (automáticos)           |

Cada caso de uso incluye: **actor principal**, **precondición**, **postcondición**, **flujo principal** y **flujo alternativo/excepción** cuando aplica.

---

## 2. Casos de Uso Comunes

### UC-COM-01 — Iniciar sesión

- **Actor:** Usuario autenticado
- **Precondición:** Usuario existe y está activo
- **Postcondición:** Sesión iniciada, `last_login_at` actualizado

**Flujo principal:**

1. El usuario accede a la URL de login
2. Ingresa email y contraseña
3. El sistema valida credenciales contra la tabla `users`
4. Si es correcto: genera token Sanctum y redirige al dashboard
5. Se registra `last_login_at` en la base de datos

**Flujo alternativo:**
Credenciales incorrectas → el sistema muestra error y registra intento fallido. Tras 5 intentos: bloqueo temporal por rate limiting.

---

### UC-COM-02 — Cerrar sesión

- **Actor:** Usuario autenticado
- **Precondición:** Sesión activa
- **Postcondición:** Token Sanctum revocado, sesión finalizada

**Flujo principal:**

1. El usuario accede a "Cerrar sesión"
2. El sistema revoca el token Sanctum activo
3. Redirige al login

---

### UC-COM-03 — Recuperar contraseña

- **Actor:** Usuario
- **Precondición:** Email registrado en el sistema
- **Postcondición:** Token de recuperación enviado por correo (expira en 60 minutos)

**Flujo principal:**

1. El usuario accede a "¿Olvidaste tu contraseña?"
2. Ingresa su email registrado
3. El sistema genera un token temporal y envía el correo
4. El usuario accede al link y define nueva contraseña

---

### UC-COM-04 — Cambiar contraseña propia

- **Actor:** Usuario autenticado
- **Precondición:** Sesión activa
- **Postcondición:** Contraseña actualizada, sesiones anteriores invalidadas

**Flujo principal:**

1. El usuario accede a "Mi perfil" > "Cambiar contraseña"
2. Ingresa contraseña actual, nueva contraseña y confirmación
3. El sistema valida la contraseña actual con `Hash::check()`
4. Valida que la nueva contraseña cumpla reglas de complejidad
5. Actualiza el campo `password` con bcrypt (cost 12)
6. Invalida todos los tokens Sanctum anteriores

**Flujo alternativo:**
Contraseña actual incorrecta → error de validación. Nueva contraseña no cumple reglas → error específico por regla.

---

### UC-COM-05 — Ver perfil de usuario

- **Actor:** Usuario autenticado
- **Precondición:** Sesión activa
- **Postcondición:** Perfil visualizado (solo lectura)

---

### UC-COM-06 — Ver notificaciones

- **Actor:** Usuario autenticado
- **Precondición:** Sesión activa
- **Postcondición:** Lista de notificaciones consultada (aprobaciones, rechazos, cambios)

---

## 3. Casos de Uso — Operador

> El Operador incluye todos los casos comunes (UC-COM-\*) más los siguientes.

### UC-OP-01 — Ver mi información laboral

- **Actor:** Operador
- **Precondición:** Sesión activa, empleado asociado al usuario
- **Postcondición:** Ficha laboral visualizada (cargo, equipo, estado, jerarquía)

---

### UC-OP-02 — Ver mi horario actual

- **Actor:** Operador
- **Precondición:** Sesión activa como Operador
- **Postcondición:** Se muestra el horario del día/semana actual únicamente si la planificación semanal está publicada

**Flujo principal:**

1. El operador accede a "Mi Horario"
2. El sistema localiza la `weekly_schedule` de la semana actual (`week_start_date`)
3. El sistema valida que el estado sea `published`
4. Consulta `weekly_schedule_assignments` para su `employee_id`
5. Muestra su turno base semanal y descansos aplicables

**Flujo alternativo:**
No existe planificación publicada para la semana → se muestra mensaje informativo.

---

### UC-OP-03 — Ver mi historial de horarios

- **Actor:** Operador
- **Postcondición:** Lista histórica de asignaciones de horario visualizada

---

### UC-OP-04 — Ver mis excepciones

- **Actor:** Operador
- **Postcondición:** Lista de "Excepciones" visualizada como vista unificada (leave requests aprobados + attendance incidents justificados)

**Nota de implementación:**
En el DDL final no existe tabla `exceptions`; la UI agrupa registros de `leave_requests` aprobados y `attendance_incidents` justificados, vinculados por `incident_type_id` en `incident_types`.

---

### UC-OP-05 — Solicitar permiso total (día completo)

- **Actor:** Operador
- **Precondición:** No existe solicitud pendiente para la misma fecha
- **Postcondición:** Solicitud creada en estado `pending`, notificación enviada al Coordinador del equipo

**Flujo principal:**

1. El operador accede a "Solicitar Permiso"
2. Selecciona tipo "Total" y la fecha deseada
3. Ingresa motivo (campo obligatorio)
4. El sistema valida que no exista solicitud duplicada ni excepción activa para esa fecha
5. Crea registro en `leave_requests` con `status='pending'`
6. Envía notificación al coordinador de su `team_id`

**Flujo alternativo:**
Fecha ya tiene excepción o solicitud aprobada → el sistema bloquea y muestra el conflicto.

---

### UC-OP-06 — Solicitar permiso parcial (rango horario)

- **Actor:** Operador
- **Precondición:** No existe permiso parcial que se solape en el mismo rango horario
- **Postcondición:** Solicitud creada con `type='parcial'`, horas específicas registradas

---

### UC-OP-07 — Consultar estado de permiso

- **Actor:** Operador
- **Postcondición:** Estado de la solicitud visualizado (pending / approved / rejected)

---

### UC-OP-08 — Solicitar cambio de turno

- **Actor:** Operador (requester)
- **Precondición:** Ambos empleados tienen horario asignado en la fecha objetivo
- **Postcondición:** Solicitud creada, notificación enviada al empleado destino para aceptación

**Flujo principal:**

1. El operador accede a "Cambio de Turno"
2. Busca y selecciona al empleado con quien desea cambiar
3. Indica la fecha del cambio
4. El sistema valida que ambos empleados tengan horario ese día
5. Valida que no existan excepciones activas en esa fecha para ninguno
6. Crea registro en `shift_swap_requests` con `status='pending_acceptance'`
7. Notifica al empleado destino para su aceptación y posterior aprobación del Coordinador

**Flujo alternativo:**
El empleado destino tiene excepción ese día → cambio bloqueado con mensaje explicativo.

---

### UC-OP-09 — Aceptar/rechazar cambio de turno recibido

- **Actor:** Operador (target)
- **Precondición:** Existe solicitud de cambio en `status='pending_acceptance'` dirigida al operador
- **Postcondición:** Solicitud actualizada a `accepted` o `rejected_by_target`

---

### UC-OP-10 — Ver mi asistencia

- **Actor:** Operador
- **Postcondición:** Registro de asistencia del día consultado

---

### UC-OP-11 — Ver historial de asistencias

- **Actor:** Operador
- **Postcondición:** Lista de asistencias por rango de fechas visualizada

---

### UC-OP-12 — Ver mis actividades del día

- **Actor:** Operador
- **Precondición:** Sesión activa como Operador
- **Postcondición:** Se visualizan actividades intradía del día (reuniones, capacitaciones, coaching y pausas activas)

**Flujo principal:**

1. El operador accede a "Mi Día"
2. El sistema consulta `intraday_activity_assignments` del operador para la fecha actual
3. Se cargan detalles desde `intraday_activities` (tipo, `start_time`, `end_time`)
4. Se muestran en una línea de tiempo junto al turno general

**Flujo alternativo:**
No existen actividades asignadas para hoy → se muestra estado "Sin actividades intradía".

---

### UC-OP-13 — Ver mi información médica y dependientes

- **Actor:** Operador
- **Precondición:** Sesión activa y empleado asociado
- **Postcondición:** El operador visualiza su información de bienestar familiar y salud registrada

**Flujo principal:**

1. El operador accede a "Mi Perfil de Bienestar"
2. El sistema consulta `employee_dependents`, `employee_disabilities` y `employee_diseases`
3. Se muestran registros activos y su vigencia

**Flujo alternativo:**
No existen registros cargados → se muestra estado "Sin información registrada".

---

## 4. Casos de Uso — Supervisor

> El Supervisor (Operador II) incluye todos los casos del Operador. En WFM su rol es **operativo**, sin aprobaciones ni administración de planificación.

### UC-SUP-01 — Ver planificación publicada de su equipo (solo lectura)

- **Actor:** Supervisor (Operador II)
- **Precondición:** Permiso `team_schedules.view`
- **Postcondición:** Visualiza turnos publicados del equipo para soporte en piso

---

### UC-SUP-02 — Ver disponibilidad intradía del equipo (solo lectura)

- **Actor:** Supervisor (Operador II)
- **Precondición:** Permisos de lectura de planificación e intradía
- **Postcondición:** Identifica operadores disponibles por franja para asistencia técnica

---

### UC-SUP-03 — Escalar incidencia operativa al Coordinador

- **Actor:** Supervisor (Operador II)
- **Precondición:** Existe un evento operativo que afecta la continuidad del servicio
- **Postcondición:** Incidencia comunicada al Coordinador para gestión administrativa

**Flujo principal:**

1. El supervisor detecta incidencia en piso
2. Registra detalle operativo (contexto técnico y operador afectado)
3. El sistema envía notificación al Coordinador del `team_id`

**Flujo alternativo:**
No existe Coordinador asignado al equipo → se escala a rol de contingencia definido por política.

---

### UC-SUP-04..UC-SUP-11 — Reasignados

- **Estado:** Reasignados al rol Coordinador en este nuevo paradigma.
- **Motivo:** El Supervisor (Operador II) no ejecuta aprobaciones ni acciones administrativas en Workflow/Planning.

---

## 5. Casos de Uso — Coordinador

> El Coordinador es el **administrador directo de un único equipo** y asume la carga administrativa diaria del Workflow.

### UC-COOR-01 — Ver equipo directo bajo su administración

- **Actor:** Coordinador
- **Precondición:** Coordinador asignado a un solo `team_id`
- **Postcondición:** Lista de empleados (operadores y supervisores/operador II) del mismo `team_id` visualizada

---

### UC-COOR-02 — Registrar incidencias de asistencia del equipo

- **Actor:** Coordinador
- **Precondición:** El empleado objetivo pertenece al mismo `team_id`
- **Postcondición:** Incidencia registrada en `attendance_incidents` (tardanza, inasistencia, etc.)

**Flujo principal:**

1. El coordinador accede a "Incidencias de Asistencia"
2. Selecciona fecha y empleado del equipo
3. Registra tipo de incidencia y observaciones
4. El sistema valida alcance por `team_id`
5. Guarda la incidencia y audita la acción

---

### UC-COOR-03 — Aprobar/rechazar permisos del equipo (único nivel)

- **Actor:** Coordinador
- **Precondición:** Existe solicitud `leave_requests` en estado pendiente de un empleado del mismo `team_id`
- **Postcondición:** Solicitud aprobada o rechazada en un solo paso

**Flujo principal:**

1. El coordinador abre "Solicitudes de Permiso"
2. Selecciona solicitud de su equipo
3. Aprueba o rechaza con justificación
4. El sistema registra decisión en `leave_request_approvals` con `step=1`
5. El sistema actualiza `leave_requests.status` y notifica al solicitante

**Flujo alternativo:**
Si el empleado no pertenece al `team_id` del coordinador → Policy deniega la acción.

---

### UC-COOR-04 — Aprobar/rechazar cambios de turno del equipo (único nivel)

- **Actor:** Coordinador
- **Precondición:** Existe `shift_swap_requests` en estado pendiente con aceptación entre operadores
- **Postcondición:** Cambio aprobado o rechazado por el único aprobador (Coordinador)

**Flujo principal:**

1. El coordinador abre bandeja de cambios de turno
2. Selecciona solicitud aceptada por ambas partes
3. Evalúa cobertura y impacto operativo
4. Aprueba o rechaza
5. El sistema actualiza `shift_swap_requests.status` y audita

---

### UC-COOR-05 — Ver bandeja administrativa del equipo

- **Actor:** Coordinador
- **Postcondición:** Lista consolidada de pendientes administrativos del equipo visualizada

---

### UC-COOR-06 — Crear excepción individual justificada

- **Actor:** Coordinador
- **Precondición:** El empleado pertenece a su `team_id`
- **Postcondición:** Excepción registrada fuera del flujo de solicitud, con trazabilidad

---

### UC-COOR-07 — Sobrescribir descansos del día

- **Actor:** Coordinador
- **Precondición:** El empleado pertenece a su `team_id` y tiene asignación semanal vigente
- **Postcondición:** Sobrescritura temporal guardada en `employee_break_overrides`

---

### UC-COOR-08 — Ver reportes de cumplimiento del equipo

- **Actor:** Coordinador
- **Postcondición:** Reporte de asistencia y cumplimiento de horarios visualizado

---

### UC-COOR-09 — Ver planificación publicada del equipo

- **Actor:** Coordinador
- **Precondición:** Existe planificación semanal en estado `published`
- **Postcondición:** Planificación consultada en modo lectura para coordinación operativa

**Flujo principal:**

1. El coordinador selecciona semana y equipo
2. El sistema muestra asignaciones publicadas y actividades intradía
3. El coordinador usa la vista para gestión de ausentismo y soporte en piso

---

## 6. Casos de Uso — Jefe

> El Jefe incluye todos los casos del Coordinador más los siguientes.

### UC-JEF-01 — Ver estructura jerárquica completa

- **Actor:** Jefe
- **Postcondición:** Árbol organizacional completo de su unidad visualizado

---

### UC-JEF-02 — Aprobar vacaciones largas

- **Actor:** Jefe
- **Precondición:** Solicitud de excepción de tipo vacaciones con duración > umbral configurado
- **Postcondición:** Vacaciones aprobadas o rechazadas con justificación

---

### UC-JEF-03 — Aprobar incapacidades prolongadas

- **Actor:** Jefe
- **Postcondición:** Incapacidad registrada y aprobada a nivel jefatura

---

### UC-JEF-04 — Aprobar permisos de coordinadores

- **Actor:** Jefe
- **Postcondición:** Permiso del coordinador aprobado o rechazado

---

### UC-JEF-05 — Ver reportes consolidados

- **Actor:** Jefe
- **Postcondición:** Reportes de toda su unidad generados y visualizados

---

### UC-JEF-06 — Autorizar excepciones especiales

- **Actor:** Jefe
- **Postcondición:** Excepción fuera del flujo estándar aprobada con justificación registrada

---

## 7. Casos de Uso — Analista Workforce (WFM)

> Rol transversal, no jerárquico. Acceso funcional amplio al sistema.

### UC-WFM-01 — Crear horario base del sistema

- **Actor:** Analista Workforce
- **Precondición:** El analista tiene permiso `schedules.create`
- **Postcondición:** Nuevo horario disponible para asignación a equipos o individuos

**Flujo principal:**

1. El analista accede a "Catálogo de Horarios"
2. Crea nuevo horario: nombre, hora inicio, hora fin, minutos de descanso
3. El sistema calcula `total_minutes` automáticamente
4. El analista guarda el horario
5. El sistema valida que `start_time < end_time` y que `total_minutes > 0`

**Flujo alternativo:**
Horario con nombre duplicado → error de validación.

---

### UC-WFM-02 — Editar horario base

- **Actor:** Analista Workforce
- **Precondición:** Horario existe y no tiene asignaciones activas (o se confirma el cambio)
- **Postcondición:** Horario actualizado, cambio auditado

---

### UC-WFM-03 — Definir tolerancias y configuraciones

- **Actor:** Analista Workforce
- **Postcondición:** Parámetros del sistema actualizados (umbrales de aprobación, etc.)

---

### UC-WFM-04 — Crear/asignar planificación semanal

- **Actor:** Analista Workforce
- **Precondición:** Existe semana objetivo y el actor posee permisos exclusivos de planificación
- **Postcondición:** Planificación semanal guardada en estado `draft`

**Flujo principal:**

1. El analista accede a "Planificación Semanal"
2. Selecciona `week_start_date`
3. Realiza asignaciones masivas por reglas operativas
4. El sistema guarda en `weekly_schedules` y `weekly_schedule_assignments` con estado `draft`
5. El sistema bloquea edición de planificación para roles no WFM

**Flujo alternativo:**
Semana bloqueada por cierre operativo → el sistema impide cambios y notifica motivo.

---

### UC-WFM-05 — Crear excepciones masivas

- **Actor:** Analista Workforce
- **Postcondición:** Registros masivos aplicados como solicitudes/aprobaciones o incidencias justificadas, visibles en UI como "Excepciones"

**Nota de implementación:**
No se crea ninguna entidad `exceptions`; la operación persiste en `leave_requests`/`leave_request_approvals` y/o `attendance_incidents` usando `incident_type_id`.

---

### UC-WFM-06 — Forzar aprobación de excepción institucional

- **Actor:** Analista Workforce
- **Precondición:** El analista tiene permiso `exceptions.force_approve`
- **Postcondición:** Excepción aprobada independientemente del flujo jerárquico normal

**Flujo principal:**

1. El analista localiza la excepción pendiente
2. Activa "Aprobación directa" (disponible solo para su rol)
3. Ingresa justificación obligatoria
4. El sistema aprueba la excepción y la marca con `approved_by = analista`
5. Se genera `audit_log` especial con flag `force_approved`

---

### UC-WFM-07 — Ver todos los reportes del sistema

- **Actor:** Analista Workforce
- **Postcondición:** Acceso a todos los reportes disponibles: asistencia, horarios, excepciones

---

### UC-WFM-08 — Exportar información (CSV / Excel)

- **Actor:** Analista Workforce
- **Postcondición:** Archivo exportado con los datos del reporte seleccionado

---

### UC-WFM-09 — Publicar planificación semanal

- **Actor:** Analista Workforce
- **Precondición:** Existe una `weekly_schedule` en `draft` y el actor tiene rol WFM
- **Postcondición:** La planificación queda en `published` para consumo operativo

---

### UC-WFM-10 — Crear actividad intradía

- **Actor:** Analista Workforce
- **Precondición:** Existe semana publicada o en preparación para la fecha objetivo
- **Postcondición:** Actividad creada en `intraday_activities`

**Flujo principal:**

1. El analista accede a "Planificación Intradía"
2. Crea actividad (capacitación/reunión/coaching)
3. Define `start_time`, `end_time`, `max_participants`
4. El sistema valida que no exista conflicto de capacidad y tiempo
5. Guarda la actividad

---

### UC-WFM-11 — Asignar operadores a actividad intradía

- **Actor:** Analista Workforce
- **Precondición:** Existe actividad intradía activa
- **Postcondición:** Operadores inscritos en `intraday_activity_assignments`

**Flujo principal:**

1. El analista selecciona la actividad
2. Busca y marca operadores elegibles
3. El sistema valida cupo (`max_participants`) y conflictos de horario
4. Registra asignaciones

---

### UC-WFM-12 — Asignar plantilla de descansos

- **Actor:** Analista Workforce
- **Precondición:** Existe `break_template` y asignación semanal del empleado; acción restringida a WFM
- **Postcondición:** Plantilla vinculada a la asignación semanal específica

**Flujo principal:**

1. El analista selecciona empleado y semana
2. Elige plantilla en `break_templates`
3. El sistema vincula la plantilla a la asignación semanal correspondiente
4. Se registra auditoría del cambio

---

## 8. Casos de Uso — Director

> El Director incluye todos los casos del Jefe más los siguientes.

### UC-DIR-01 — Ver toda la operación

- **Actor:** Director
- **Postcondición:** Vista global de todos los equipos, asistencias y horarios activos

---

### UC-DIR-02 — Ver indicadores globales

- **Actor:** Director
- **Postcondición:** Dashboard con KPIs de asistencia, ausentismo y cobertura de horarios

---

### UC-DIR-03 — Aprobar permisos de jefes

- **Actor:** Director
- **Postcondición:** Permiso del jefe aprobado o rechazado con registro completo

---

### UC-DIR-04 — Autorizar excepciones institucionales

- **Actor:** Director
- **Postcondición:** Excepción de alto impacto autorizada con justificación institucional

---

## 9. Casos de Uso — Administrador del Sistema

### UC-ADM-01 — Crear usuario

### UC-ADM-02 — Editar usuario

### UC-ADM-03 — Activar / desactivar usuario

### UC-ADM-04 — Asignar rol de sistema

### UC-ADM-05 — Gestionar permisos

### UC-ADM-06 — Gestionar estructura corporativa y catálogos

- **Actor:** Administrador del Sistema
- **Precondición:** El administrador tiene permisos de configuración organizacional
- **Postcondición:** Estructura corporativa creada y consistente para uso del resto de módulos

**Flujo principal:**

1. Crear Dirección en `directorates`
2. Crear Departamento asociado en `departments`
3. Crear Cargo asociado en `positions`
4. Completar catálogos complementarios (equipos, estados laborales, tipos)

**Flujo alternativo:**
Si falta una entidad padre (Dirección/Departamento) el sistema bloquea la creación del nivel siguiente.

### UC-ADM-07 — Importar empleados desde archivo CSV

- **Actor:** Administrador del Sistema
- **Precondición:** El administrador tiene permiso `employees.import`
- **Postcondición:** Empleados importados, errores reportados en log de importación

**Flujo principal:**

1. El administrador descarga la plantilla CSV del sistema
2. Completa los datos de empleados según la estructura definida
3. Sube el archivo al sistema
4. El FormRequest de importación valida cada fila: `employee_number` único, `username` válido/no duplicado, `position` y `team` existentes
5. Importa las filas válidas como registros en `employees`
6. Genera reporte de filas con error y motivo específico

**Flujo alternativo:**
Archivo con formato incorrecto → rechazo total con mensaje explicativo. Filas con errores → se omiten y se reportan sin detener la importación.

---

### UC-ADM-08 — Reprocesar información

### UC-ADM-09 — Auditar acciones del sistema

### UC-ADM-10 — Registrar información médica del empleado

- **Actor:** Administrador del Sistema (o RRHH con permisos delegados)
- **Precondición:** Existe empleado activo
- **Postcondición:** Información de dependientes, discapacidades o enfermedades crónicas registrada

**Flujo principal:**

1. El administrador accede al perfil del empleado
2. Agrega dependiente en `employee_dependents` y/o condición de salud en `employee_disabilities` o `employee_diseases`
3. El sistema valida catálogos de tipos y vigencia
4. Guarda y audita los cambios

---

## 10. Casos de Uso Intrínsecos del Sistema

> Estos casos son ejecutados **automáticamente** por el sistema. Son críticos para la integridad y no deben omitirse en la implementación.

| ID        | Descripción                                                             | Implementación en Laravel                                                                  |
| --------- | ----------------------------------------------------------------------- | ------------------------------------------------------------------------------------------ |
| UC-INT-01 | Validar coordinador directo antes de aprobar solicitudes                | `Policy` + `Service` validando `team_id` y `employees.parent_id`                           |
| UC-INT-02 | Validar solapamiento de horarios antes de guardar                       | `Rule` con scope de fechas en `ScheduleService`                                            |
| UC-INT-03 | Validar conflictos en "excepciones" lógicas                             | Servicio de reglas sobre `leave_requests` aprobados y `attendance_incidents` justificados  |
| UC-INT-04 | Bloquear acciones fuera de jerarquía organizacional                     | `Policy::before()` + `HierarchyService::isDescendantOf()` usando `employees.parent_id`     |
| UC-INT-05 | Registrar auditoría de cambios críticos                                 | `Observer` o Trait `Auditable` en modelos críticos                                         |
| UC-INT-06 | Notificar cambios relevantes a los actores                              | Laravel `Notifications` (mail / database)                                                  |
| UC-INT-07 | Calcular horario efectivo diario (prioridad)                            | `ScheduleResolverService` con lógica de precedencia                                        |
| UC-INT-08 | Resolver prioridad de reglas (excepción > intradía > semanal publicado) | `ScheduleResolverService::resolve(employee, date)`                                         |
| UC-INT-09 | Prevenir eliminación de registros históricos                            | `SoftDeletes` + `Policy::delete()` siempre retorna false                                   |
| UC-INT-10 | Manejar usuarios sin empleado asociado (roles de sistema)               | Middleware que verifica `employee_id nullable`                                             |
| UC-INT-11 | Garantizar flujo de aprobación de un solo paso                          | Restricción en `leave_request_approvals` (`step=1`) + validación de unicidad por solicitud |
