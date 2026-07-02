# Especificación Técnica Detallada: WorkflowsModule (Módulo de Aprobaciones y Procesos)

> Documento RUP Centrado en Arquitectura
> **Módulo:** WorkflowsModule
> **Ruta:** `app/Modules/WorkflowsModule`

## 1. Resumen Ejecutivo y Propósito del Módulo

El **WorkflowsModule** es el motor de orquestación de estados (State Machine) para los procesos que requieren autorización jerárquica en la empresa. Su propósito arquitectónico es desacoplar la lógica de "Aprobaciones" de los módulos de dominio (como `WfmModule` o `PersonnelModule`), centralizando las reglas de quién puede aprobar qué, y en qué orden.

Actualmente, el módulo gestiona el ciclo de vida de los **Permisos/Vacaciones (`LeaveRequest`)** y de los **Intercambios de Turno (`ShiftSwapRequest`)**, rastreando firmas digitales (`Approval`) en flujos de múltiples niveles (ej. Nivel 1: Jefe Directo -> Nivel 2: WFM -> Nivel 3: RRHH).

---

## 2. Casos de Uso Detallados

A continuación, los flujos principales de orquestación de aprobaciones:

### CU-WF-01: Aprobación Multinivel de Vacaciones (Leave Request)

- **Actor:** Supervisor (Aprobador L1) / Analista WFM (Aprobador L2).
- **Descripción:** Procesamiento de una solicitud de ausencia prolongada.
- **Flujo Principal:**
  1. Un empleado genera la solicitud (Vía UI en `WfmModule`), lo que inserta un registro en `LeaveRequest` con estado `Pending`.
  2. El WorkflowsModule notifica al Supervisor Directo del empleado.
  3. El Supervisor ingresa al sistema y aprueba, generando un registro en `LeaveRequestApproval` indicando `level = 1`, `status = approved`.
  4. El Workflow detecta que falta una firma y escala la solicitud al departamento de WFM.
  5. WFM aprueba (`level = 2`). El `LeaveRequest` principal cambia a `Fully_Approved`.
  6. El módulo despacha un evento informando al `WfmModule` que proceda a bloquear los horarios del empleado.

### CU-WF-02: Rechazo y Cancelación de Flujo (Shift Swap)

- **Actor:** Supervisor o Analista WFM.
- **Descripción:** Interrupción de un proceso de intercambio de turno.
- **Flujo Principal:**
  1. Dos agentes acuerdan cambiar de turno (`ShiftSwapRequest`).
  2. La solicitud llega a la bandeja del Analista WFM.
  3. El Analista determina que el cambio viola una regla de negocio (ej. falta de habilidades de un agente) y rechaza la solicitud.
  4. Se crea un `ShiftSwapApproval` con `status = rejected` y un campo obligatorio de `comments` (justificación).
  5. El `ShiftSwapRequest` se marca como `Rejected` cerrando el flujo, y se notifica a ambos agentes.

---

## 3. Requerimientos Funcionales (RF)

- **RF-WF-01 (Máquina de Estados Estricta):** Una solicitud (`Request`) solo puede ser aprobada si su estado actual es `Pending` o `Partially_Approved`. No se puede alterar una solicitud ya en estado `Rejected`, `Cancelled` o `Fully_Approved`.
- **RF-WF-02 (Delegación de Aprobadores):** El módulo debe soportar (a futuro) la delegación temporal de firmas; si un Supervisor L1 está de vacaciones, el sistema debe permitir que un Supervisor Sustituto firme los `LeaveRequestApproval`.
- **RF-WF-03 (Historial Inmutable):** Toda decisión tomada (`LeaveRequestApproval`, `ShiftSwapApproval`) debe registrar de forma inmutable el ID del aprobador, el timestamp exacto, y el comentario emitido, para fines de auditoría del Ministerio de Trabajo.

---

## 4. Requerimientos No Funcionales (RNF)

- **RNF-WF-01 (Desacoplamiento mediante Eventos):** El WorkflowsModule no debe llamar directamente a los *Actions* de otros módulos para ejecutar lógica de negocio. Una vez completado un flujo, debe limitarse a emitir un evento global (Ej. `LeaveRequestFullyApproved`), dejando que el módulo interesado (WFM, Operaciones) lo escuche y reaccione.
- **RNF-WF-02 (Trazabilidad Transaccional):** Si la firma de un aprobador L2 provoca el cierre definitivo del ticket, ambas operaciones (insertar el Approval y actualizar el Request a *Fully Approved*) deben ir envueltas en una `DB::transaction`.

---

## 5. Modelos de Datos Detallados

Este módulo maneja el patrón "Documento - Firma":

| Atributo | Tipo / Cast | Descripción y Lógica de Negocio |
| :--- | :--- | :--- |
| **Entidad: `LeaveRequest` / `ShiftSwapRequest`**| | **Documentos de Flujo (Tickets)** |
| `id` | `uuid` (PK)| Identificador de la solicitud. |
| `requester_id` | `integer` (FK)| Quién inició el flujo. |
| `payload` | `json` | Datos del requerimiento (Fechas, motivos) agnósticos a este módulo. |
| `status` | `enum` | `Pending`, `L1_Approved`, `Fully_Approved`, `Rejected`, `Cancelled`. |
| `current_level` | `integer` | Nivel actual de firma requerido (1, 2, 3...). |
| **Entidad: `LeaveRequestApproval` / `ShiftSwapApproval`**| | **Firmas Digitales / Resoluciones** |
| `request_id` | `uuid` (FK)| Ticket padre. |
| `approver_id` | `integer` (FK)| Quién tomó la decisión (`User`). |
| `level_signed` | `integer` | Qué nivel firmó (ej. Nivel 1 = Supervisor). |
| `decision` | `enum` | `Approved` o `Rejected`. |
| `comments` | `text` | Texto obligatorio si la decisión es `Rejected`. |

---

## 6. Roles y Permisos (Policies)

- **Firmas Estrictas (`workflows.approve`):**
  - **Supervisores Operativos:** Pueden emitir `Approvals` exclusivamente para el Nivel 1, y únicamente si el `requester_id` pertenece a su mismo equipo.
  - **Analistas WFM:** Tienen autorización global para emitir `Approvals` de Nivel 2 en adelante.
  - **Solicitantes:** Tienen permiso para cancelar (`status = Cancelled`) su propia solicitud, siempre y cuando ningún aprobador haya emitido una firma todavía.

---

## 7. Eventos, Listeners y Notificaciones

- `WorkflowStepCompleted`: Emitido cada vez que un aprobador intermedio firma. Puede despachar una notificación al siguiente eslabón de la cadena (Ej. "Supervisor L1 aprobó; es tu turno, Analista WFM").
- `WorkflowFullyApproved`: Evento clave escuchado por el `WfmModule` (para descontar horas), el `OperationsModule` (para justificar métricas) y el `PersonnelModule` (para temas de nómina).
- `WorkflowRejected`: Dispara correo inmediato al solicitante con los motivos del rechazo.

---

## 8. Servicios y Acciones (Proyectados)

Aunque actualmente en etapa temprana, este módulo requerirá clases como:

- **`SubmitLeaveRequestAction`:** Valida la disponibilidad del empleado e inicializa la máquina de estado en Nivel 1.
- **`ProcessApprovalStepAction`:** Registra la firma del aprobador, verifica si era la última firma requerida y, de ser así, cierra el Request emitiendo el evento global de completitud.

---

## 9. Endpoints o Rutas Detalladas

Este módulo carece de UI propia pesada, ya que los botones de "Aprobar" / "Rechazar" suelen estar embebidos en los Dashboards de otros módulos (ej. `ManagerApprovals` de WfmModule). Sin embargo, exporta API interna:

- **`POST /api/workflows/leave/{id}/approve`**
- **`POST /api/workflows/leave/{id}/reject`**

---

## 10. Dependencias con otros Módulos

- **Dependencia Horizontal (`WfmModule`):** El principal "Cliente" de este módulo. Envía las solicitudes en bruto y se queda a la espera de los eventos de aprobación para mover sus turnos en la base de datos.
- **Dependencia Downstream (`CoreModule`):** Acceso a `User` para identificar al Solicitante y a los Aprobadores.

---

## 11. Estructura de Carpetas Actual

```tree
app/Modules/WorkflowsModule
├── Models
│   ├── LeaveRequestApproval.php
│   ├── LeaveRequest.php
│   ├── ShiftSwapApproval.php
│   └── ShiftSwapRequest.php
├── Providers
│   └── ModuleServiceProvider.php
└── Routes
    └── web.php

4 directories, 6 files
```

---

*Documento técnico profundo generado bajo lineamientos de arquitectura iterativa RUP.*
