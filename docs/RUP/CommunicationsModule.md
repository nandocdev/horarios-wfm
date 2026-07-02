# Especificación Técnica Detallada: CommunicationsModule (Módulo de Comunicaciones)

> Documento RUP Centrado en Arquitectura
> **Módulo:** CommunicationsModule
> **Ruta:** `app/Modules/CommunicationsModule`

## 1. Resumen Ejecutivo y Propósito del Módulo

El **CommunicationsModule** constituye el núcleo de *engagement*, comunicación interna corporativa y difusión de información del Monolito. Funciona como una red social privada para los empleados y un tablón de anuncios oficial para el área de Recursos Humanos / Comunicación Corporativa.

Su propósito es democratizar y centralizar la información mediante la publicación de Noticias (`News`), recopilar *feedback* del personal a través de Encuestas (`Polls`), y fomentar un ambiente laboral positivo mediante Reconocimientos Públicos (`Shoutouts`). A su vez, soporta un esquema de interacción altamente acoplado a la participación del usuario mediante Comentarios, Reacciones y Menciones, todo ello soportado por un sólido sistema de Notificaciones (In-App y Email).

---

## 2. Casos de Uso Detallados

A continuación, se describen los flujos de interacción fundamentales, especificando actores y lógica interna:

### CU-COM-01: Redacción y Programación de Comunicados (News)

- **Actor:** Administrador / Redactor (Comunicaciones).
- **Descripción:** Permite redactar comunicados oficiales, asignarles taxonomías y diferir su publicación.
- **Flujo Principal:**
  1. El actor ingresa al panel de administración y redacta el artículo (WYSIWYG/Rich Text).
  2. Asigna Categorías (`Category`) y Etiquetas (`Tag`) para segmentación.
  3. Define un `published_at` futuro.
  4. El `CreateNewsAction` persiste el artículo en estado `draft`.
  5. Un Cron Job ejecuta periódicamente el `PublishScheduledContentAction`, el cual encuentra los borradores vencidos, cambia su estado a `published` y dispara el evento `NewsPublished`.

### CU-COM-02: Creación y Participación en Encuestas (Polls)

- **Actor (Creador):** HR / Administrador.
- **Actor (Participante):** Empleado regular.
- **Flujo de Participación:**
  1. El Empleado visualiza una encuesta activa en el Feed.
  2. Selecciona una de las opciones predefinidas (almacenadas en JSON).
  3. El sistema (vía Livewire) invoca un Action para crear un `PollResponse`, validando que el usuario no haya votado previamente y que el `expires_at` de la encuesta no haya sido superado.
  4. Si la encuesta está configurada para notificar a los rezagados, el `SendExpiredPollRemindersAction` procesa el envío de correos recordatorios a quienes aún no han emitido su voto 24h antes del cierre.

### CU-COM-03: Reconocimiento entre Pares (Shoutouts)

- **Actor:** Cualquier Empleado.
- **Descripción:** Fomento del buen clima laboral mediante menciones positivas.
- **Flujo Principal:**
  1. El actor invoca la creación de un Shoutout, seleccionando a uno o varios compañeros (vínculo a `User`).
  2. Redacta el motivo del reconocimiento.
  3. Al procesar el `CreateShoutoutAction`, el sistema notifica inmediatamente a los galardonados y el Shoutout aparece en el muro global para recibir interacciones.

### CU-COM-04: Interacción Social (Reacciones, Comentarios y Menciones)

- **Actor:** Empleado.
- **Descripción:** Respuesta dinámica al contenido publicado (Polimorfismo).
- **Flujo Principal:**
  1. El actor lee un comunicado y hace clic en el botón "Aplauso". El `ToggleReactionAction` añade o retira el registro polimórfico en la tabla `reactions`.
  2. El actor comenta en la noticia, escribiendo "@juan_perez buen trabajo".
  3. El `CreateCommentAction` guarda el comentario y delega la cadena de texto al `ProcessMentionsAction`.
  4. Éste extrae el patrón con Regex, resuelve que `@juan_perez` es el usuario ID 45, crea un registro en la tabla `mentions` y envía un `Notification` in-app a Juan.

### CU-COM-05: Moderación de Contenido Inapropiado

- **Actor:** Moderador.
- **Flujo Principal:** El moderador oculta un comentario tóxico (`ModerateContentAction`), registrando la justificación. El comentario deja de ser visible en el Feed pero permanece en la base de datos para auditoría.

---

## 3. Requerimientos Funcionales (RF)

- **RF-COM-01 (Soporte Polimórfico Core):** Las entidades `Comment` y `Reaction` deben implementarse usando relaciones `MorphTo` en Eloquent, permitiendo que puedan asociarse indistintamente a un `News`, un `Poll` o un `Shoutout` sin alterar el esquema de base de datos de las tablas hijas.
- **RF-COM-02 (Procesador de Texto Rico):** Todos los campos de contenido largo (ej. `News.content`) deben soportar subida de imágenes embebidas, delegando el almacenamiento de los binarios al `FilesystemModule`.
- **RF-COM-03 (Taxonomías Dinámicas):** Debe existir una relación Many-to-Many (`news_category`, `news_tag`) para organizar los contenidos, soportando filtrado por estos ejes en el Dashboard.
- **RF-COM-04 (Notificaciones Multicanal):** La entidad local `Notification` debe reflejarse en la UI (campanita) y ser respaldada por el sistema de Notificaciones nativo de Laravel (vía Mail o Broadcast).
- **RF-COM-05 (Auto-Archivado):** Un comando de consola (`AutoArchiveContentAction`) debe marcar como *Archivados* los contenidos cuya caducidad de relevancia (ej. Encuestas 30 días después de cerradas) haya expirado, retirándolos del Feed por defecto.

---

## 4. Requerimientos No Funcionales (RNF)

- **RNF-COM-01 (Caché y Counters):** Las consultas al Feed implican calcular conteos masivos. Se debe usar `$model->withCount(['comments', 'reactions'])` y preferiblemente almacenar contadores desnormalizados en las tablas maestras (`reactions_count` en la tabla `news`) para evitar cuellos de botella en la renderización del Feed.
- **RNF-COM-02 (Colas para Notificaciones):** Procesos como `SendAutomaticNewsletterAction` y `SendExpiredPollRemindersAction` enviarán miles de correos. DEBEN ejecutarse obligatoriamente usando Jobs asíncronos (`ShouldQueue`) con control de reintentos y delimitación de lotes (Chunking).
- **RNF-COM-03 (Seguridad contra XSS):** Dado que el módulo es el punto principal de "User Generated Content", todos los inputs que soporten HTML (noticias, comentarios) deben ser estrictamente pasados por una librería de sanitización (ej. HTMLPurifier) antes de tocar la base de datos.
- **RNF-COM-04 (Protección de Mutabilidad Rápida):** Prevenir abusos (Spam). El `ToggleReactionAction` y la creación de comentarios en Livewire deben tener límites de tasa de peticiones (Rate Limiting) y *debouncing*.

---

## 5. Modelos de Datos Detallados

A continuación, la estructura relacional de los modelos de dominio:

| Atributo / Método Clave | Tipo / Cast Eloquent | Descripción y Lógica de Negocio |
| :--- | :--- | :--- |
| **Entidad: `News`** | | **Comunicados Oficiales** |
| `status` | `string` / `enum` | Estados: `draft`, `published`, `archived`. |
| `published_at` | `datetime` | Determina si el sistema debe mostrarlo o retenerlo. |
| `author_id` | `integer` (FK) | Relación a `User`. |
| **Entidad: `Poll`** | | **Encuestas de Votación** |
| `options` | `array` (JSON) | Define las opciones válidas: `[{"id": 1, "text": "Sí"}, ...]`. |
| `expires_at` | `datetime` | Momento exacto donde se bloquea la inserción de respuestas. |
| **Entidad: `PollResponse`** | | **Respuestas de Encuesta** |
| `poll_id`, `user_id` | `integer` (FKs) | Restricción de unicidad (Unique Index) para evitar votos múltiples. |
| `selected_option_id`| `string` / `int` | Refiere al ID del JSON `options` de la encuesta padre. |
| **Entidad: `Comment` & `Reaction`**| | **Interacción Polimórfica** |
| `commentable_id` | `integer` | ID del recurso padre (News, Poll, Shoutout). |
| `commentable_type` | `string` | FQCN del recurso padre. |
| `type` (Reaction) | `string` | Tipo de reacción: `like`, `heart`, `clap`. |
| **Entidad: `Mention`** | | **Resolución de Menciones** |
| `user_id` | `integer` (FK) | El usuario que fue mencionado. |
| `mentionable_id / type`| `polymorphic` | Dónde fue mencionado (Generalmente un `Comment`). |

---

## 6. Roles y Permisos (Policies)

El módulo distribuye la capacidad operativa mediante diversas Policies de autorización:

- **`NewsPolicy` & `PollPolicy`:**
  - `viewAny`, `view`: Otorgados a cualquier usuario autenticado (`auth()->check()`).
  - `create`, `update`, `delete`: Restringidos exclusivamente a roles administrativos (ej. "Comunicaciones", "HR").
- **`ShoutoutPolicy`:**
  - `create`: Abierto a todos los empleados (fomenta la cultura).
  - `update`: Bloqueado (los reconocimientos suelen ser inmutables una vez otorgados para evitar manipulación post-notificación).
- **`CommentPolicy` & `ReactionPolicy`:**
  - `create`: Abierto a todos los empleados sobre contenidos activos (no archivados).
  - `delete`: Sólo el autor del comentario o un `Moderador` pueden eliminar/ocultar un comentario.
- **Rol Especial `Moderador` (`content.moderate`):**
  - Ignora las reglas de propiedad y puede ejecutar el `ModerateContentAction` sobre cualquier `Comment` o `News` generado por terceros.

---

## 7. Eventos, Listeners y Notificaciones

El corazón de este módulo es la propagación de eventos para mantener a los usuarios involucrados:

### Eventos de Dominio (Domain Events)

- `NewsPublished`: Disparado por `PublishScheduledContentAction` o creación directa.
- `ShoutoutCreated`: Emitido al crear un reconocimiento.
- `UserMentioned`: Emitido por `ProcessMentionsAction`.

### Listeners y Acciones Desencadenadas

- `NotifyUsersOnNewPublicationListener`: Escucha `NewsPublished` y despacha un Job que iterativamente envía una `NewArticleNotification` (In-App y Email) a los empleados del departamento destino.
- `ProcessMentionsListener`: Escucha la creación/actualización de un `Comment`. Delega al Action la resolución del Regex.

### Sistema de Notificaciones Local (`Notification`)

- Se implementa el sistema de *Database Notifications* de Laravel (`Illuminate\Notifications\DatabaseNotification`).
- Livewire consume la tabla `notifications` (filtrando por `notifiable_id = auth()->id()`) para el contador de campana no leído.

---

## 8. Servicios y Acciones Detalladas (Actions & DTOs)

Las mutaciones más densas están separadas en Actions:

### `ProcessMentionsAction`

- **Ubicación:** `App\Modules\CommunicationsModule\Actions\ProcessMentionsAction`
- **Lógica:**
  1. Recibe el modelo polimórfico recién creado (ej. `Comment`).
  2. Ejecuta una expresión regular `/(@[a-zA-Z0-9_]+)/` sobre el cuerpo del texto.
  3. Extrae los "usernames" y ejecuta una consulta `whereIn('username', $matches)` en `CoreModule\User`.
  4. Por cada usuario válido encontrado, inserta un registro en la tabla `mentions` y despacha un job de `MentionNotification`.
- **Riesgos Mitigados:** El action se asegura de no notificar al mismo usuario dos veces (usando collections `unique()`).

### `ModerateContentAction`

- **Ubicación:** `App\Modules\CommunicationsModule\Actions\ModerateContentAction`
- **Lógica:**
  1. Recibe el modelo, el ID del moderador y un `reason` (razón).
  2. Modifica el estado del modelo (ej. `is_moderated = true` o *Soft Delete*).
  3. Envía un correo disciplinario (o alerta de moderación) al autor original del contenido censurado.

### `SendAutomaticNewsletterAction`

- **Ubicación:** `App\Modules\CommunicationsModule\Actions\SendAutomaticNewsletterAction`
- **Lógica:** Ejecutado semanalmente por el Scheduler (`Console/Kernel.php`). Extrae los `News` más populares de los últimos 7 días y encola el envío de un correo HTML masivo.

---

## 9. Endpoints o Rutas Detalladas (Livewire / Web)

La experiencia de usuario está profundamente ligada a componentes Livewire (SPA-like feel):

- **`GET /communications/feed`** (Dashboard Social)
  - Componente Padre: `CommunicationsModule\Livewire\SocialFeed`.
  - Componentes Hijos (Iterados): `NewsCard`, `PollCard`, `ShoutoutCard`.
  - Implementa "Infinite Scrolling" mediante Livewire (`wire:scroll`).
- **Componentes de Interacción Embebidos:**
  - `Livewire\ReactionButton`: Un componente reutilizable que recibe el modelo polimórfico. Maneja el estado optimista (Optimistic UI) al hacer clic (`wire:click="toggleReaction"`).
  - `Livewire\CommentThread`: Muestra la jerarquía de comentarios (opcionalmente anidados) y expone la caja de texto rica (Trix/Quill).
- **Admin Rutas (CRUD):**
  - `GET /admin/news/create` -> `Livewire\Admin\NewsForm`.
  - `GET /admin/polls/create` -> `Livewire\Admin\PollForm` (Permite agregar/eliminar opciones dinámicas en el array JSON mediante Livewire Array Binding).

---

## 10. Dependencias con otros Módulos

El CommunicationsModule es de alto nivel y requiere interoperabilidad para ser efectivo:

- **Dependencia (Downstream) de `CoreModule`:** Absolutamente necesaria para referenciar al `User` en cada acción (Autoría, Destinatario de Menciones, Reacciones, Políticas).
- **Dependencia (Downstream) de `PersonnelModule`:** En implementaciones maduras, la segmentación de noticias y encuestas requiere conocer el organigrama. Por ejemplo, `News` puede tener una relación "Many-to-Many" con `Department` del módulo de personal, de forma que el comunicado solo se muestre en el Feed de los usuarios pertenecientes a ese departamento.
- **Dependencia Transversal de `FilesystemModule` (Proyectada):** Al adjuntar un PDF a un comunicado o embeber imágenes, la subida física del binario debe ser delegada a un Action público de `FilesystemModule` en lugar de programar la lógica de disco (S3/Local) en este módulo.

---

## 11. Estructura de Carpetas

```tree
app/Modules/CommunicationsModule
├── Actions
│   ├── AutoArchiveContentAction.php
│   ├── CreateCategoryAction.php
│   ├── CreateCommentAction.php
│   ├── CreateNewsAction.php
│   ├── CreatePollAction.php
│   ├── CreateShoutoutAction.php
│   ├── CreateTagAction.php
│   ├── DeleteCategoryAction.php
│   ├── DeletePollAction.php
│   ├── DeleteShoutoutAction.php
│   ├── DeleteTagAction.php
│   ├── ModerateContentAction.php
│   ├── ProcessMentionsAction.php
│   ├── PublishScheduledContentAction.php
│   ├── SendAutomaticNewsletterAction.php
│   ├── SendExpiredPollRemindersAction.php
│   ├── ToggleReactionAction.php
│   ├── UpdateCategoryAction.php
│   ├── UpdateNewsAction.php
│   ├── UpdatePollAction.php
│   ├── UpdateShoutoutAction.php
│   └── UpdateTagAction.php
├── Database
│   └── Seeders
│       ├── CommunicationsPermissionSeeder.php
│       └── NewsSeeder.php
├── DTOs
│   ├── CategoryDTO.php
│   ├── CommentDTO.php
│   ├── MentionDTO.php
│   ├── ModerationDTO.php
│   ├── NewsDTO.php
│   ├── PollDTO.php
│   ├── ReactionDTO.php
│   ├── ShoutoutDTO.php
│   └── TagDTO.php
├── Events
│   ├── CommentCreated.php
│   ├── CommentDeleted.php
│   ├── MentionCreated.php
│   ├── ReactionAdded.php
│   └── ReactionRemoved.php
├── Http
│   ├── Controllers
│   │   ├── CategoryController.php
│   │   ├── CommentController.php
│   │   ├── ContentModerationController.php
│   │   ├── ReactionController.php
│   │   └── TagController.php
│   └── Requests
│       ├── ModerateContentRequest.php
│       ├── StoreCategoryRequest.php
│       ├── StoreCommentRequest.php
│       ├── StoreNewsRequest.php
│       ├── StorePollRequest.php
│       ├── StoreReactionRequest.php
│       ├── StoreShoutoutRequest.php
│       ├── StoreTagRequest.php
│       ├── UpdateCategoryRequest.php
│       ├── UpdateNewsRequest.php
│       ├── UpdatePollRequest.php
│       ├── UpdateShoutoutRequest.php
│       └── UpdateTagRequest.php
├── Listeners
│   ├── SendCommentNotificationListener.php
│   ├── SendLeaveRequestCreatedNotification.php
│   ├── SendLeaveRequestDecisionNotification.php
│   ├── SendMentionNotificationListener.php
│   ├── SendReactionNotificationListener.php
│   ├── SendScheduleAssignmentUpdatedNotification.php
│   ├── SendShiftSwapApprovedNotification.php
│   ├── SendShiftSwapReceivedNotification.php
│   └── SendWeeklySchedulePublishedNotification.php
├── Livewire
│   ├── CreateNews.php
│   ├── CreatePoll.php
│   ├── CreateShoutout.php
│   ├── EditNews.php
│   ├── EditShoutout.php
│   ├── Forms
│   │   ├── NewsForm.php
│   │   ├── PollForm.php
│   │   └── ShoutoutForm.php
│   ├── Home.php
│   ├── ListNews.php
│   ├── ListPolls.php
│   └── ListShoutouts.php
├── Models
│   ├── Category.php
│   ├── Comment.php
│   ├── Mention.php
│   ├── News.php
│   ├── Notification.php
│   ├── Poll.php
│   ├── PollResponse.php
│   ├── Reaction.php
│   ├── Shoutout.php
│   └── Tag.php
├── Notifications
│   ├── LeaveRequestCreatedNotification.php
│   ├── LeaveRequestDecisionNotification.php
│   ├── ScheduleAssignmentUpdatedNotification.php
│   ├── ShiftSwapApprovedNotification.php
│   ├── ShiftSwapReceivedNotification.php
│   └── WeeklySchedulePublishedNotification.php
├── Observers
│   ├── CategoryObserver.php
│   ├── CommentObserver.php
│   ├── MentionObserver.php
│   ├── NewsObserver.php
│   ├── NotificationObserver.php
│   ├── PollObserver.php
│   ├── ReactionObserver.php
│   ├── ShoutoutObserver.php
│   └── TagObserver.php
├── Policies
│   ├── CategoryPolicy.php
│   ├── CommentPolicy.php
│   ├── ContentModerationPolicy.php
│   ├── MentionPolicy.php
│   ├── NewsPolicy.php
│   ├── NotificationPolicy.php
│   ├── PollPolicy.php
│   ├── ReactionPolicy.php
│   ├── ShoutoutPolicy.php
│   └── TagPolicy.php
├── Providers
│   └── ModuleServiceProvider.php
├── Resources
│   └── Views
│       ├── admin
│       │   ├── categories
│       │   │   ├── create.blade.php
│       │   │   ├── edit.blade.php
│       │   │   ├── index.blade.php
│       │   │   └── show.blade.php
│       │   ├── moderation
│       │   │   └── index.blade.php
│       │   └── tags
│       │       ├── create.blade.php
│       │       ├── edit.blade.php
│       │       ├── index.blade.php
│       │       └── show.blade.php
│       └── livewire
│           ├── home.blade.php
│           ├── list-news.blade.php
│           ├── list-polls.blade.php
│           ├── list-shoutouts.blade.php
│           ├── news-form.blade.php
│           ├── poll-form.blade.php
│           └── shoutout-form.blade.php
└── Routes
    └── web.php
```
