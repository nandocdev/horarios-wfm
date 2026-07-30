# Sistema de Notificaciones — horarios-wfm

## Arquitectura General

**Canales de notificación**:

| Canal | Medio | Uso |
|---|---|---|
| `database` | Tabla `notifications` | Consultable desde el UI (NotificationBell) |
| `broadcast` | Reverb (WebSocket) | Push en tiempo real, recibido por NotificationBell y Toast |
| `webex` | Cisco Webex API | Opcional, envía a sala/sala de Webex si configurado |
| `mail` | Mail driver de Laravel | Solo para notificaciones específicas |

**Clase base**: `App\Shared\Notifications\BaseNotification` — implementa `database` + `broadcast` + condicional `webex`.

**Eventos compartidos**: en `App\Shared\Events\`, disparados desde Livewire components, Actions o Commands, escuchados por Listeners en cada módulo.

---

## 1. Gestión de Turnos e Intercambios (WFM)

### Intercambio de Turno Solicitado

| Campo | Valor |
|---|---|
| **Clases** | `SwapRequestNotification` (WfmModule) · `ShiftSwapReceivedNotification` (CommunicationsModule) |
| **Evento** | `ShiftSwapRequested` |
| **Disparo** | Livewire `RequestShiftSwap::save()` + Listener `SendShiftSwapNotification` |
| **Receptor** | Destinatario del cambio + su supervisor |
| **Canales** | database, broadcast, webex |
| **Propósito** | Notificar que alguien solicitó intercambiar un turno |

### Intercambio Aceptado por el Compañero

| Campo | Valor |
|---|---|
| **Clase** | `SwapStatusChangedNotification` (WfmModule) |
| **Evento** | `ShiftSwapAccepted` |
| **Disparo** | Listener `SendShiftSwapNotification` + Livewire `SwapRequestHistory` |
| **Receptor** | Solicitante original |
| **Canales** | database, broadcast, webex |
| **Propósito** | Indicar que el compañero aceptó y ahora WFM debe aprobar |

### Intercambio Rechazado por el Compañero

| Campo | Valor |
|---|---|
| **Clase** | `SwapStatusChangedNotification` (WfmModule) |
| **Evento** | `ShiftSwapRejectedByPeer` |
| **Disparo** | Listener `SendShiftSwapNotification` + Livewire `SwapRequestHistory` |
| **Receptor** | Solicitante original |
| **Canales** | database, broadcast, webex |
| **Propósito** | Informar que el compañero rechazó el intercambio |

### Intercambio Aprobado por WFM

| Campo | Valor |
|---|---|
| **Clases** | `ShiftSwapApprovedNotification` (WfmModule) · `ShiftSwapApprovedNotification` (CommunicationsModule) · `ShiftSwapApprovedMail` (Mailable) |
| **Evento** | `ShiftSwapApproved` |
| **Disparo** | Listener `SendShiftSwapNotification` + `NotifyShiftSwapApproved` + `SendShiftSwapApprovedNotification` |
| **Receptor** | Ambas partes (solicitante + destinatario + managers + aprobador) |
| **Canales** | database, broadcast, webex, mail |
| **Propósito** | Notificar que WFM aprobó el intercambio y se aplicó al horario |

### Intercambio Rechazado por WFM

| Campo | Valor |
|---|---|
| **Clase** | `SwapStatusChangedNotification` (WfmModule) |
| **Evento** | `ShiftSwapRejected` |
| **Disparo** | Listener `SendShiftSwapNotification` + Livewire `WfmSwapApprovals` |
| **Receptor** | Ambas partes |
| **Canales** | database, broadcast, webex |
| **Propósito** | Informar que WFM rechazó el intercambio |

### Intercambio Cancelado

| Campo | Valor |
|---|---|
| **Clase** | `SwapStatusChangedNotification` (WfmModule) |
| **Evento** | `ShiftSwapCancelled` |
| **Disparo** | Listener `SendShiftSwapNotification` + Livewire `SwapRequestHistory` |
| **Receptor** | Destinatario del cambio |
| **Canales** | database, broadcast, webex |
| **Propósito** | Indicar que el solicitante canceló la solicitud de intercambio |

### Solicitud de Permiso/Vacaciones Creada

| Campo | Valor |
|---|---|
| **Clases** | `LeaveRequestNotification` (WfmModule) · `LeaveRequestCreatedNotification` (CommunicationsModule) |
| **Evento** | `LeaveRequestCreated` |
| **Disparo** | Listener `SendLeaveRequestNotification` + `SendLeaveRequestCreatedNotification` |
| **Receptor** | Supervisor/aprobadores del empleado |
| **Canales** | database, broadcast, webex |
| **Propósito** | Alertar al liderazgo que hay una solicitud de permiso pendiente |

### Respuesta a Solicitud de Permiso

| Campo | Valor |
|---|---|
| **Clases** | `LeaveRequestDecisionNotification` (WfmModule) · `LeaveRequestDecisionNotification` (CommunicationsModule) |
| **Evento** | `LeaveRequestDecision` |
| **Disparo** | Listener `SendLeaveRequestNotification` + `SendLeaveRequestDecisionNotification` |
| **Receptor** | Empleado que solicitó el permiso |
| **Canales** | database, broadcast, webex |
| **Propósito** | Informar la resolución (aprobado/rechazado) |

---

## 2. Horarios y Asignaciones (WFM)

### Horario Semanal Publicado

| Campo | Valor |
|---|---|
| **Clases** | `SchedulePublishedNotification` (WfmModule) · `WeeklySchedulePublishedNotification` (CommunicationsModule) |
| **Evento** | `WeeklySchedulePublished` |
| **Disparo** | Listener `SendWeeklySchedulePublishedNotification` |
| **Receptor** | Todos los usuarios activos con roles: operator, coordinator, supervisor, chief, director, admin, wfm |
| **Canales** | database, broadcast, mail, webex |
| **Propósito** | Notificar que el horario de la semana ya está disponible |

### Modificación de Asignación de Horario

| Campo | Valor |
|---|---|
| **Clases** | `ScheduleModifiedNotification` (WfmModule) · `ScheduleAssignmentUpdatedNotification` (CommunicationsModule) |
| **Evento** | `ScheduleAssignmentUpdated` |
| **Disparo** | Livewire `EmployeeWeeklyPlanning` + Listener `SendScheduleAssignmentUpdatedNotification` |
| **Receptor** | Agente afectado + su supervisor |
| **Canales** | database, broadcast, mail, webex |
| **Propósito** | Informar cambios en la asignación del día (entrada, salida, actividades) |

### Asignación de Actividad Intradía

| Campo | Valor |
|---|---|
| **Clase** | `IntradayActivityNotification` (WfmModule) |
| **Evento** | No usa evento — llamado directo desde `AssignIntradayActivityAction` |
| **Disparo** | `AssignIntradayActivityAction::execute()` línea 158, cuando un supervisor asigna un break, capacitación, coaching, etc. al empleado |
| **Receptor** | Empleado al que se le asigna la actividad |
| **Canales** | database, broadcast, webex |
| **Propósito** | Notificar cambios inmediatos en la agenda del día en curso |

---

## 3. Operaciones y Adherencia (Operations)

### Alerta de Adherencia

| Campo | Valor |
|---|---|
| **Clase** | `AdherenceAlertNotification` (OperationsModule) |
| **Evento** | `AdherenceAlertTriggered` (Shared) |
| **Disparo** | `RealtimeMonitoring.php:244-252` — cuando se detecta un agente fuera de adherencia por ≥5 minutos (`currentDuration >= 300`) |
| **Receptor** | Supervisor/manager del equipo (`$employee->manager ?? $employee->team?->supervisor`) |
| **Canales** | database, broadcast, webex |
| **Propósito** | Alertar al liderazgo para tomar acción sobre agentes que no cumplen su horario planificado |

### Incidencia de Asistencia

| Campo | Valor |
|---|---|
| **Clase** | `AttendanceIncidentNotification` (WfmModule) |
| **Evento** | `AttendanceIncidentRegistered` (Shared) |
| **Disparo** | `ReconcileEmployeeAttendanceAction` — compara horas reales de login (CTI) contra horario WFM. Si hay tardanza, ausencia, etc. |
| **Receptor** | Empleado/agente infractor (`$employee->user->notify(...)`) |
| **Canales** | database, broadcast, webex |
| **Propósito** | Informar al agente que se registró una incidencia en su registro de asistencia |

---

## 4. Calidad y Evaluaciones (Quality)

### Evaluación de Calidad Realizada

| Campo | Valor |
|---|---|
| **Clase** | `EvaluationNotification` (QualityModule) |
| **Evento** | `EvaluationCreated` |
| **Disparo** | Listener `SendEvaluationNotification` — cuando un evaluador completa una evaluación de llamada |
| **Receptor** | Agente evaluado (`$evaluation->employee->user->notify(...)`) |
| **Canales** | database, broadcast, webex |
| **Propósito** | Notificar al agente que su llamada fue evaluada, con puntaje y enlace al feedback |

---

## 5. Comunicaciones Sociales (Communications)

### Nueva Mención

| Campo | Valor |
|---|---|
| **Modelo** | `App\Modules\CommunicationsModule\Models\Notification` (modelo custom, no Laravel notify) |
| **Evento** | `MentionCreated` |
| **Disparo** | Listener `SendMentionNotificationListener` — cuando alguien etiqueta `@usuario` |
| **Receptor** | Usuario mencionado |
| **Canal** | database (solo) |
| **Propósito** | Notificar que alguien te mencionó en un comentario o publicación |

### Nuevo Comentario

| Campo | Valor |
|---|---|
| **Modelo** | Modelo custom en CommunicationsModule |
| **Evento** | `CommentCreated` |
| **Disparo** | Listener `SendCommentNotificationListener` |
| **Receptor** | Autor del post + usuarios mencionados en el comentario |
| **Canal** | database (solo) |
| **Propósito** | Notificar nueva actividad en una publicación |

### Nueva Reacción

| Campo | Valor |
|---|---|
| **Modelo** | Modelo custom en CommunicationsModule |
| **Evento** | `ReactionAdded` |
| **Disparo** | Listener `SendReactionNotificationListener` — cuando alguien reacciona (like, etc.) |
| **Receptor** | Autor del comentario/shoutout |
| **Canal** | database (solo) |
| **Propósito** | Notificar que alguien reaccionó a tu contenido |

---

## 6. Sistema y Seguridad (Core / Connect)

### Restablecimiento de Contraseña

| Campo | Valor |
|---|---|
| **Clase** | `ResetPasswordNotification` (CoreModule) |
| **Disparo** | `User::sendPasswordResetNotification()` — llamado por Laravel Fortify cuando el usuario solicita restablecer su contraseña |
| **Receptor** | Usuario que solicita el cambio |
| **Canal** | mail |
| **Propósito** | Envío del enlace para restablecer la contraseña olvidada |

### Cambio de Contraseña Exitoso

| Campo | Valor |
|---|---|
| **Clase** | `PasswordChangedNotification` (CoreModule) |
| **Disparo** | Fortify `ResetUserPassword::reset()` + formulario de cambio de contraseña en `security.blade.php` |
| **Receptor** | Usuario que cambió su contraseña |
| **Canales** | mail, database |
| **Propósito** | Confirmación de seguridad y alerta ante cambios no autorizados |

### Modo Mantenimiento Activado/Desactivado

| Campo | Valor |
|---|---|
| **Clase** | `MaintenanceModeNotification` (CoreModule) |
| **Disparo** | Livewire `SystemMaintenance` — cuando un admin activa o desactiva el modo mantenimiento |
| **Receptor** | Todos los usuarios activos (`Notification::send($users, ...)`) |
| **Canales** | database, broadcast, webex |
| **Propósito** | Alertar a todo el sistema sobre el estado de mantenimiento |

### Fallo de Sincronización (CUIC/Finesse/UCCX)

| Campo | Valor |
|---|---|
| **Clase** | `SyncFailedNotification` (ConnectModule) |
| **Evento** | `SyncFailed` (Shared) — contiene `source`, `message`, `consecutiveFailures` |
| **Disparo** | `cuic:sync` (2 catches) · `cuic:backfill` (por chunk) · `uccx:auto-import` (por archivo) |
| **No disparan** | `finesse:sync-queues` ❌ · `finesse:sync` ❌ · `cuic:sync-realtime` ❌ |
| **Receptor** | Usuario hardcodeado `ferncastillo` (vía `User::where('username', 'ferncastillo')`) |
| **Canales** | database, broadcast, webex |
| **Propósito** | Alertar al equipo técnico sobre fallos en la ingesta de datos del Contact Center |

---

## 7. Mapa de Gaps

| Notificación faltante | Acción requerida |
|---|---|
| `finesse:sync-queues` no emite `SyncFailed` | Agregar catch + `event(new SyncFailed(...))` |
| `finesse:sync` no emite `SyncFailed` | Agregar `event(new SyncFailed(...))` en catch existente |
| `cuic:sync-realtime` no emite `SyncFailed` | Agregar catch + `event(new SyncFailed(...))` |
| `SyncFailedNotification` hardcodeada a ferncastillo | Generalizar a role de administrador/supervisor |

---

## 8. Nota sobre duplicación de clases

Cada evento base tiene **dos notificaciones** (una en WfmModule + otra en CommunicationsModule) que se envían simultáneamente desde ambos módulos. Esto es intencional: la duplicación refleja responsabilidades separadas dentro del Monolito Modular (WfmModule maneja la lógica de negocio/UI, CommunicationsModule maneja canales externos como Webex y mail con formatos específicos).
