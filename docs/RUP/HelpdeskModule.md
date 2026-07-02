# Especificación Técnica Detallada: HelpdeskModule (Módulo de Soporte Interno y Tickets)

> Documento RUP Centrado en Arquitectura
> **Módulo:** HelpdeskModule
> **Ruta:** `app/Modules/HelpdeskModule`

## 1. Resumen Ejecutivo y Propósito del Módulo

El **HelpdeskModule** es el sistema de gestión de incidencias (*Ticketing*) interno de la organización. Su propósito es proveer un canal formal, auditable y medible para que los empleados reporten problemas o soliciten servicios a departamentos de soporte operativo (por ejemplo, IT, Mantenimiento, Recursos Humanos, o Soporte Técnico Interno).

A través de la categorización de incidencias (`HelpdeskCategory`), el rastreo del ciclo de vida del ticket (`HelpdeskTicket`) y la comunicación asíncrona entre el solicitante y el agente de resolución (`HelpdeskTicketComment`), este módulo asegura que ninguna solicitud interna se pierda y permite medir los Acuerdos de Nivel de Servicio (SLA).

---

## 2. Casos de Uso Detallados

A continuación, se describen los flujos fundamentales de interacción con el sistema de tickets:

### CU-HD-01: Creación de Ticket de Soporte

- **Actor:** Empleado (Solicitante).
- **Descripción:** Un empleado reporta una falla o solicita un requerimiento.
- **Flujo Principal:**
  1. El empleado ingresa a la vista "Mis Tickets" (`MyTickets` Livewire Component).
  2. Completa un formulario especificando: Asunto, Categoría (ej. "Hardware", "Nómina") y un Nivel de Urgencia (`Low`, `Medium`, `High`, `Critical`).
  3. Puede adjuntar capturas de pantalla o evidencias (delegado al `FilesystemModule`).
  4. El sistema crea el `HelpdeskTicket` con estado `Open` (Abierto) y notifica al departamento correspondiente.

### CU-HD-02: Asignación y Gestión de Ticket

- **Actor:** Agente de Soporte / Administrador Helpdesk.
- **Descripción:** Toma de propiedad del caso y resolución.
- **Flujo Principal:**
  1. El Agente visualiza la bandeja general (`ManageTickets`).
  2. Selecciona un ticket sin asignar y se lo auto-asigna (`assigned_to = auth()->id()`), cambiando el estado a `In Progress`.
  3. Trabaja en la incidencia y se comunica con el solicitante a través de comentarios (`HelpdeskTicketComment`).
  4. Una vez resuelto, el Agente cambia el estado del ticket a `Resolved` (Resuelto).

### CU-HD-03: Interacción Asíncrona (Comentarios)

- **Actor:** Solicitante o Agente.
- **Descripción:** Hilo de comunicación bidireccional anidado al ticket.
- **Flujo Principal:**
  1. Cualquiera de las partes ingresa al detalle del ticket (`TicketDetail`).
  2. Escribe una respuesta o solicitud de mayor información.
  3. El sistema crea el `HelpdeskTicketComment` y dispara una notificación (Email/In-App) a la contraparte avisando que hay un nuevo mensaje.

---

## 3. Requerimientos Funcionales (RF)

- **RF-HD-01 (Clasificación Dinámica):** El sistema debe permitir definir N categorías (`HelpdeskCategory`) desde un panel administrativo, a fin de enrutar correctamente los tickets a las áreas resolutoras sin necesidad de alterar código duro.
- **RF-HD-02 (Estados del Ticket):** Un ticket debe tener un ciclo de vida estrictamente definido (ej. `Open` -> `In Progress` -> `Resolved` -> `Closed`). Transiciones específicas deben ser controladas (ej. un usuario regular no puede cerrar un ticket, solo el Agente o el sistema tras inactividad).
- **RF-HD-03 (Historial de Conversación):** Todo ticket debe exhibir cronológicamente los comentarios (`HelpdeskTicketComment`) emulando una conversación de chat o foro, identificando visualmente qué mensaje es del solicitante y cuál del agente.
- **RF-HD-04 (Control de Urgencias y SLAs):** El módulo debe identificar y permitir priorizar visualmente (ej. colores en el grid) aquellos tickets cuya prioridad sea `Critical` o que tengan más días sin respuesta.

---

## 4. Requerimientos No Funcionales (RNF)

- **RNF-HD-01 (Notificaciones Sincronizadas):** Las alertas de "Nuevo Ticket" o "Nuevo Comentario" deben despacharse de forma asíncrona (`Queue`) para evitar que el usuario que envía el mensaje perciba lentitud (latencia de correo).
- **RNF-HD-02 (Protección contra Spam Interno):** Implementar *Rate Limiting* en la creación de tickets para evitar que un empleado impaciente genere 20 tickets idénticos por un fallo de red en menos de un minuto.
- **RNF-HD-03 (Control de Accesos a Adjuntos):** Las evidencias adjuntas en los tickets (ej. un recibo de pago mal calculado) deben estar estrictamente protegidas. Un Agente solo puede ver adjuntos de tickets asignados a su área, y un Solicitante solo de sus propios tickets.

---

## 5. Modelos de Datos Detallados

A continuación, la estructura relacional de los modelos de dominio:

| Atributo | Tipo / Cast | Descripción y Lógica de Negocio |
| :--- | :--- | :--- |
| **Entidad: `HelpdeskTicket`** | | **Cabecera de la Incidencia** |
| `id` | `uuid` / `bigint` | Identificador (ej. `#TCK-1002`). |
| `user_id` | `integer` (FK)| Solicitante original (Relación a `User`). |
| `category_id` | `integer` (FK)| Área resolutora (Relación a `HelpdeskCategory`). |
| `assigned_to` | `integer` (FK)| Agente que tomó el caso (`User`, nullable). |
| `subject`, `priority` | `string` | Asunto breve y Nivel de Urgencia (Enum). |
| `status` | `string` | Estado actual: `Open`, `In Progress`, `Resolved`, `Closed`. |
| `description` | `text` | Texto descriptivo inicial de la falla. |
| **Entidad: `HelpdeskCategory`**| | **Taxonomía de Soporte** |
| `name` | `string` | Nombre (ej. "Fallo de Red", "Errores en Nómina"). |
| `is_active` | `boolean` | Permite deshabilitar categorías obsoletas. |
| **Entidad: `HelpdeskTicketComment`**| | **Conversación y Bitácora** |
| `ticket_id` | `integer` (FK)| El ticket padre. |
| `user_id` | `integer` (FK)| Quién emitió el comentario (Solicitante o Agente). |
| `body` | `text` | Contenido del mensaje. |

---

## 6. Roles y Permisos (Policies)

La visibilidad es crucial en el Helpdesk:

- **`HelpdeskTicketPolicy`:**
  - `view`: El empleado solo puede ver tickets donde él sea el `user_id`. El Agente puede ver todos los tickets (o los filtrados por su categoría).
  - `create`: Abierto a todo el personal autenticado.
  - `update` (Resolver/Cerrar): Reservado al Agente (`assigned_to`) o Administradores de área.
- **Rol `Helpdesk Agent`:** Rol especial del CoreModule que permite a los usuarios acceder al componente `ManageTickets` para auto-asignarse trabajo.

---

## 7. Eventos, Listeners y Notificaciones

El ecosistema reactivo de incidencias:

- `TicketCreated`: Disparado al guardar un nuevo ticket. Escuchado por un `NotifyDepartmentListener` que avisa al grupo de Agentes responsables.
- `TicketAssigned`: Notifica al solicitante: *"Juan Pérez está revisando tu caso"*.
- `TicketCommentAdded`: Notifica asíncronamente a la contraparte para mantener fluidez en el soporte.
- `TicketResolved`: Cierra el SLA y podría disparar un correo con una encuesta de satisfacción hacia el solicitante.

---

## 8. Servicios y Acciones Detallados (Actions)

*(Proyección de Refactor, ya que la lógica actual parece residir en los componentes Livewire)*

Para mantener la robustez, el módulo debería extraer la lógica hacia clases dedicadas:

- **`CreateTicketAction`:** Recibiría el DTO, subiría los adjuntos llamando al `UploadFileAction` del `FilesystemModule` y guardaría el ticket emitiendo el evento.
- **`AddTicketCommentAction`:** Validaría que el ticket no esté `Closed` antes de permitir insertar un nuevo comentario.
- **`ResolveTicketAction`:** Cambiaría el estado y registraría el *timestamp* de resolución para calcular tiempos de respuesta (KPIs del Helpdesk).

---

## 9. Endpoints o Rutas Detalladas (Livewire / Web)

Este módulo basa su interfaz de usuario íntegramente en Livewire SPA:

- **`GET /helpdesk/my-tickets`** -> Componente `Livewire\MyTickets`.
  - Vista para el empleado común. Permite ver el historial de sus casos y pulsar un botón para abrir el modal/formulario de creación de nuevo ticket.
- **`GET /helpdesk/tickets/{id}`** -> Componente `Livewire\TicketDetail`.
  - Vista dual (usada por Empleado y Agente). Muestra el hilo de comentarios y una caja de texto para responder. Los Agentes ven opciones adicionales (ej. botón "Marcar como Resuelto").
- **`GET /admin/helpdesk/manage`** -> Componente `Livewire\ManageTickets`.
  - Tablero de control (Kanban o Data Table) exclusivo para los `Helpdesk Agents`. Permite filtrar tickets por `Open`, categorizarlos y asignarlos masivamente.

---

## 10. Dependencias con otros Módulos

- **Dependencia (Downstream) de `CoreModule`:** Inyección absoluta de `User` para identificar a Solicitantes y Agentes.
- **Dependencia Transversal de `FilesystemModule` (Proyectada):** Las capturas de error o documentos adjuntos en los comentarios de un ticket deben gestionarse mediante el módulo de sistema de archivos.

---

## 11. Estructura de Carpetas

```tree
app/Modules/HelpdeskModule
├── Livewire
│   ├── ManageTickets.php
│   ├── MyTickets.php
│   └── TicketDetail.php
├── Models
│   ├── HelpdeskCategory.php
│   ├── HelpdeskTicketComment.php
│   └── HelpdeskTicket.php
├── Providers
│   └── ModuleServiceProvider.php
├── Resources
│   └── Views
│       └── livewire
│           ├── manage-tickets.blade.php
│           ├── my-tickets.blade.php
│           └── ticket-detail.blade.php
└── Routes
    └── web.php
```
