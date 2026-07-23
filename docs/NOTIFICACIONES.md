# Mensajes de Notificación del Sistema

Todos los mensajes se envían por los canales `database` + `broadcast` + `webex` (si configurado).
El formato Webex es `*{title}*\n\n{message}` generado por `BaseNotification::toWebex()`.

---

## Swap (Shift Swap)

### SendShiftSwapNotification (`WfmModule/Listeners`)

| Evento                                              | title                                                   | message                                                                                                        |
| --------------------------------------------------- | ------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------- |
| `ShiftSwapRequested` (al destinatario)              | Nueva Solicitud de Intercambio                          | `{nombre} ha solicitado intercambiar un turno contigo para el periodo {fecha}.`                                |
| `ShiftSwapRequested` (al supervisor)                | Solicitud de Intercambio Pendiente                      | `{nombre} tiene una solicitud de intercambio pendiente de {nombre} para el periodo {fecha}.`                   |
| `ShiftSwapApproved` (al solicitante + destinatario) | Cambio de Turno Aprobado                                | `El intercambio de turno para el periodo {fecha} ha sido aprobado y aplicado.`                                 |
| `ShiftSwapRejected` (al solicitante + destinatario) | Cambio de Turno Rechazado                               | `El intercambio de turno para el periodo {fecha} ha sido rechazado. Motivo: {razón}`                           |
| `ShiftSwapCancelled` (al destinatario)              | Solicitud de Intercambio Cancelada                      | `El solicitante ha cancelado la solicitud de intercambio para el periodo {fecha}.`                             |
| `ShiftSwapAccepted` (al solicitante)                | Intercambio Aceptado — Pendiente de Aprobación          | `Tu solicitud de intercambio para el periodo {fecha} ha sido aceptada. Queda pendiente de aprobación por WFM.` |
| `ShiftSwapAccepted` (al coordinador)                | Intercambio de Turno Aceptado — Pendiente de Aprobación | `{A} y {B} han acordado un intercambio para el periodo {fecha}. Requiere aprobación de WFM.`                   |
| `ShiftSwapRejectedByPeer` (al solicitante)          | Intercambio Rechazado                                   | `Tu solicitud de intercambio para el periodo {fecha} ha sido rechazada por el destinatario.`                   |

### NotifyShiftSwapApproved (`WfmModule/Listeners`)

| title                    | message                                                         |
| ------------------------ | --------------------------------------------------------------- |
| Cambio de Turno Aprobado | `El cambio de turno para el periodo {fecha} ha sido procesado.` |

### WfmSwapApprovals (Livewire, notificación directa)

| title                          | message                                                                                            |
| ------------------------------ | -------------------------------------------------------------------------------------------------- |
| Intercambio de Turno Rechazado | `Tu solicitud de intercambio para el {fecha} ha sido rechazada por el supervisor. Motivo: {razón}` |

### SwapRequestHistory (Livewire, notificaciones directas)

| title                                                   | message                                                                                              |
| ------------------------------------------------------- | ---------------------------------------------------------------------------------------------------- |
| Solicitud Cancelada                                     | `{nombre} ha cancelado la solicitud de intercambio para el {fecha}.`                                 |
| Intercambio Aceptado                                    | `{nombre} ha aceptado tu solicitud de intercambio para el {fecha}. Pendiente por aprobación de WFM.` |
| Intercambio de Turno Aceptado — Pendiente de Aprobación | `{op} y {other} han acordado un intercambio para el {fecha}. Requiere aprobación de WFM.`            |
| Intercambio Rechazado                                   | `{nombre} ha rechazado tu solicitud de intercambio para el {fecha}.`                                 |

---

## Leave (Permisos)

### SendLeaveRequestNotification (`WfmModule/Listeners`)

| Evento                                        | title                      | message                                                      |
| --------------------------------------------- | -------------------------- | ------------------------------------------------------------ |
| `LeaveRequestCreated` (al supervisor)         | Nueva Solicitud de Permiso | `{nombre} ha solicitado un {tipo} para el {fecha}.`          |
| `LeaveRequestDecision` approved (al empleado) | Permiso Aprobado           | `Tu solicitud de permiso para el {fecha} ha sido aprobada.`  |
| `LeaveRequestDecision` rejected (al empleado) | Permiso Rechazado          | `Tu solicitud de permiso para el {fecha} ha sido rechazada.` |

---

## Operations

### SendAdherenceAlertNotification (`OperationsModule/Listeners`)

| title                | message               |
| -------------------- | --------------------- |
| Alerta de Adherencia | `{nombre} — {label}.` |

### SendAttendanceIncidentNotification (`OperationsModule/Listeners`)

| title                    | message                                                                 |
| ------------------------ | ----------------------------------------------------------------------- |
| Incidencia de Asistencia | `Se ha registrado una incidencia de tipo '{tipo}' para el día {fecha}.` |

### ReconcileEmployeeAttendanceAction (notificación directa)

| title                    | message                                                                                      |
| ------------------------ | -------------------------------------------------------------------------------------------- |
| Incidencia de Asistencia | `Se ha registrado una incidencia de tipo '{tipo}' para el día {fecha}. Motivo: {comentario}` |

---

## Intraday

### AssignIntradayActivityAction (`WfmModule/Actions`)

| title                    | message                                                    |
| ------------------------ | ---------------------------------------------------------- |
| Nueva Actividad Asignada | `Se te ha asignado una actividad intradía: {descripción}.` |

---

## Horarios

### EmployeeWeeklyPlanning (Livewire)

| title            | message                                                      |
| ---------------- | ------------------------------------------------------------ |
| Horario Asignado | `Tu horario para la semana del {fecha} ha sido actualizado.` |

### CommunicationsModule Listeners

| Evento                      | title                                      | message                                                                     |
| --------------------------- | ------------------------------------------ | --------------------------------------------------------------------------- |
| `WeeklySchedulePublished`   | Nuevo Horario Publicado                    | `El horario para la semana {periodo} ya está disponible.`                   |
| `ScheduleAssignmentUpdated` | Horario Actualizado                        | `Tu horario para el {fecha} ha sido modificado.`                            |
| `LeaveRequestCreated`       | Nueva Solicitud de Ausencia                | `Un miembro de tu equipo ha solicitado un permiso o vacaciones.`            |
| `LeaveRequestDecision`      | Solicitud de Ausencia {Aprobada/Rechazada} | `Tu solicitud de ausencia ha sido {aprobada/rechazada}.`                    |
| `ShiftSwapApproved`         | Intercambio de Turno Aprobado              | (vía CommunicationsModule)                                                  |
| `ShiftSwapRequested`        | Nueva Solicitud de Intercambio             | `Has recibido una nueva solicitud de intercambio de turno de un compañero.` |



---






