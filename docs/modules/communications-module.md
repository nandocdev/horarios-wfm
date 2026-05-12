# Especificación Completa: CommunicationsModule

## 🎯 Propósito del Módulo

El `CommunicationsModule` es el epicentro de la cultura y el compromiso organizacional. Su objetivo es doble: por un lado, actúa como un sistema de gestión de contenido (CMS) interno para noticias y encuestas; por otro, funciona como el **Centro de Notificaciones Centralizado** de la plataforma, orquestando la comunicación de eventos críticos generados por otros módulos hacia los colaboradores.

---

## 🚀 Catálogo Completo de Funcionalidades

### 1. Sistema de Gestión de Noticias (CMS)

- **Editor de Contenido:** Creación de noticias con soporte para títulos, extractos (excerpts), slugs únicos y contenido enriquecido.
- **Ciclo de Vida de Moderación:**
  - `submitForReview`: Envío de borradores a revisión.
  - `approve`: Publicación oficial por parte de un moderador.
  - `reject`: Retorno a borrador con notas de retroalimentación.
  - `archive`: Retiro de la vista pública.
- **Gestión Multimedia (Spatie MediaLibrary):**
  - `featured_image`: Imagen principal para la Home y listados.
  - `attachments`: Soporte para adjuntar documentos (PDF, Excel) y galerías.
- **Historial de Versiones:** Registro automático de los últimos 10 cambios por noticia para auditoría de ediciones.

### 2. Reconocimiento y Red Social (Shoutouts)

- **Muro de Reconocimientos:** Espacio para destacar logros de colaboradores vinculados directamente a `EmployeesModule`.
- **Interacción Social:**
  - **Reacciones:** Sistema de "likes" o reacciones personalizadas sobre cada shoutout.
  - **Comentarios:** Hilo de conversación en noticias.
  - **Menciones:** Uso de `@username` en cualquier texto, que el motor de menciones (`ProcessMentionsAction`) detecta para notificar a los involucrados.

### 3. Centro de Notificaciones Transversal

El módulo actúa como un servicio para el resto de la aplicación, escuchando eventos externos y transformándolos en alertas para los usuarios:

- **Alertas de Horarios:**
  - `SendWeeklySchedulePublishedNotification`: Notifica cuando el horario de la semana está listo.
- **Gestión de Solicitudes (Workflows):**
  - `SendLeaveRequestCreatedNotification` / `SendLeaveRequestDecisionNotification`.
  - `SendShiftSwapReceivedNotification` / `SendShiftSwapApprovedNotification`.
- **Alertas Sociales:** Notificaciones inmediatas por nuevos comentarios o menciones recibidas.

### 4. Encuestas e Inteligencia Participativa (Polls)

- **Toma de Decisiones:** Creación de encuestas con opciones múltiples.
- **Nudge de Participación:**
  - `SendExpiredPollRemindersAction`: Identifica automáticamente a los usuarios que no han votado y les envía un recordatorio antes de que la encuesta expire.

### 5. Taxonomías Polimórficas (Categorías y Tags)

- **Organización Flexible:** Sistema de categorías y etiquetas que puede ser aplicado a cualquier entidad del módulo (Noticias, Shoutouts, Encuestas).
- **CRUD Administrativo:** Gestión completa de nombres, slugs y descripciones de las taxonomías.

### 6. Automatización y Mantenimiento

- **Newsletter Automático:** Generación de resúmenes periódicos de noticias no leídas enviados por correo o notificación push.
- **Publicación Programada:** Las noticias pueden prepararse para publicarse automáticamente en una fecha futura (`scheduled_at`).
- **Archivado Automático:** Limpieza de la Home moviendo contenido antiguo al archivo basado en la fecha `archive_at`.

---

## 🛠 Arquitectura Técnica y Rutas

### Estructura Operativa (Admin)

- `admin/communications/moderation`: Panel central para moderadores.
- `admin/communications/news`: Gestión administrativa de noticias.
- `admin/communications/categories` & `tags`: Mantenimiento de taxonomías.
- `admin/communications/polls`: Creación y control de encuestas.

### Interacción de Usuario (Pública/Autenticada)

- `communications.home`: Dashboard principal que agrega todo el contenido.
- `communications.comments.store`: Endpoint para registrar comentarios.
- `communications.reactions.store`: Endpoint para alternar reacciones.

---

## ⚠️ [RIESGOS TÉCNICOS]

1. **Ruido de Notificaciones:** Centralizar todas las alertas puede llevar a una saturación del usuario. Es vital implementar preferencias de notificación en el futuro.
2. **Consumo de Almacenamiento:** El soporte para adjuntos en noticias puede agotar rápidamente el espacio en disco si no se establecen límites de tamaño por archivo.
3. **Privacidad en Menciones:** El motor de menciones debe validar que el usuario mencionando tiene permiso para "ver" al usuario mencionado en el contexto actual.
4. **Carga en la Home:** El componente `Home.php` realiza múltiples consultas pesadas (Eager Loading de categorías, tags, autores y reacciones). Se requiere optimización con caché de fragmentos.

---

## 📊 Matriz de Notificaciones y Estado de Implementación

A continuación se detalla la lógica de negocio requerida para las notificaciones y su estado actual de desarrollo en el código:

| Evento | Destinatarios Requeridos | Estado | Observaciones |
| :--- | :--- | :--- | :--- |
| **Horario publicado** | Agentes, Coordinadores, Supervisores y Jefes. | 🟢 **Completado** | Notifica a todos los roles operativos y de gestión activos. |
| **Permiso creado** | Creador (Confirmación) y Coordinador del equipo. | 🟡 **En proceso** | Solo notifica al supervisor; falta confirmación al creador. |
| **Permiso decidido** | Creador de la solicitud (aprobación/rechazo). | 🟢 **Completado** | Implementado y funcional. |
| **Intercambio recibido** | Creador, Receptor y Coordinadores de ambos. | 🟡 **En proceso** | Solo notifica al receptor. |
| **Intercambio aceptado** | Aceptador, Creador y Coordinadores de ambos. | 🔴 **Pendiente** | Listener intermedio no implementado. |
| **Intercambio aprobado** | Creador, Receptor y Coordinadores de ambos. | 🟡 **En proceso** | Notifica a los agentes; falta incluir coordinadores. |
| **Intercambio rechazado** | Creador, Receptor y Coordinadores de ambos. | 🔴 **Pendiente** | Listener de rechazo no implementado. |

---

## 📋 Ejemplo de Uso: Procesar Menciones

```php
// app/Modules/CommunicationsModule/Actions/ProcessMentionsAction.php
$action = app(ProcessMentionsAction::class);

// Detecta @user en el mensaje y dispara el evento MentionCreated
$action->execute($shoutout, $shoutout->message); 
```

## Notificaciones básicas del sistema
