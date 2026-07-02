# Catálogo e Inventario Completo de Vistas: app/Modules

Este documento cataloga **todas** las vistas (`.blade.php`) existentes en los distintos directorios de `app/Modules/`, clasificadas por módulo. Proporciona una visión exhaustiva de la interfaz de usuario legacy que será progresivamente puenteada y migrada a la capa de presentación de `app/Src/` bajo el patrón Strangler Fig.

---

## 1. Módulo: AuditModule

Módulo encargado de la visualización y auditoría forense de los logs de la plataforma.

| Tipo | Archivo / Componente | Descripción |
| :--- | :--- | :--- |
| Livewire | `livewire/list-audit-logs.blade.php` | Panel principal que lista las trazas de auditoría (logs de cambios en modelos), con filtros por entidad, usuario, acción y rango de fecha. |

---

## 2. Módulo: CommunicationsModule

Gestiona la comunicación interna, incluyendo boletines de noticias, reconocimientos (shoutouts), encuestas internas y foros moderados.

### Vistas Administrativas (Moderación de Contenido)

| Tipo | Archivo / Componente | Descripción |
| :--- | :--- | :--- |
| Blade | `admin/moderation/index.blade.php` | Consola de moderación para que supervisores aprueben o rechacen noticias o comentarios antes de ser publicados. |
| Blade | `admin/categories/index.blade.php` | Listado y administración de categorías de comunicación interna. |
| Blade | `admin/categories/create.blade.php` | Formulario para crear una nueva categoría de comunicación. |
| Blade | `admin/categories/edit.blade.php` | Formulario de edición para una categoría existente. |
| Blade | `admin/categories/show.blade.php` | Ficha detallada de una categoría de comunicación y sus artículos. |
| Blade | `admin/tags/index.blade.php` | Listado y administración de etiquetas (Tags) para organizar comunicados. |
| Blade | `admin/tags/create.blade.php` | Formulario de creación de etiquetas. |
| Blade | `admin/tags/edit.blade.php` | Formulario para modificar etiquetas existentes. |
| Blade | `admin/tags/show.blade.php` | Vista para visualizar las publicaciones asociadas a una etiqueta específica. |

### Componentes Livewire (Interactivos)

| Tipo | Archivo / Componente | Descripción |
| :--- | :--- | :--- |
| Livewire | `livewire/home.blade.php` | Portal de comunicación principal (Intranet) visible para los agentes. Muestra feeds, avisos y widgets. |
| Livewire | `livewire/news-form.blade.php` | Formulario interactivo para la redacción y publicación de noticias internas. |
| Livewire | `livewire/list-news.blade.php` | Listado dinámico de noticias publicadas en la intranet corporativa. |
| Livewire | `livewire/shoutout-form.blade.php` | Formulario para enviar un reconocimiento público (Shoutout) a un compañero de equipo. |
| Livewire | `livewire/list-shoutouts.blade.php` | Muro de reconocimientos acumulados por la operación. |
| Livewire | `livewire/poll-form.blade.php` | Creación de encuestas flash para los empleados de la campaña. |
| Livewire | `livewire/list-polls.blade.php` | Galería de encuestas activas y visualización en vivo de resultados de votación. |

---

## 3. Módulo: ConnectModule

Capa Anticorrupción (ACL) e integración con sistemas de telefonía CTI.

### Plantillas de Email

| Tipo | Archivo / Componente | Descripción |
| :--- | :--- | :--- |
| Email | `emails/import-error.blade.php` | Plantilla para notificar fallos graves durante la importación automática de grabaciones o CDRs. |
| Email | `emails/backfill-report.blade.php` | Informe resumen tras la ejecución de procesos de carga retroactiva (Backfill) de llamadas. |

### Componentes Livewire (CTI e Historial)

| Tipo | Archivo / Componente | Descripción |
| :--- | :--- | :--- |
| Livewire | `livewire/general-dashboard.blade.php` | Dashboard operacional CTI global con métricas en tiempo real de colas y llamadas en espera. |
| Livewire | `livewire/agent-dashboard.blade.php` | Panel CTI personal para el agente. Muestra su estado telefónico (Ready, Not Ready, Break) y llamadas activas. |
| Livewire | `livewire/team-performance-summary.blade.php` | Resumen operativo para supervisores, enfocado en llamadas tomadas y desviaciones de su equipo. |
| Livewire | `livewire/list-call-records.blade.php` | Tabla del histórico de llamadas telefónicas grabadas, permitiendo búsquedas rápidas. |
| Livewire | `livewire/create-call-record.blade.php` | Formulario para el registro manual de un contacto o llamada no capturada automáticamente. |
| Livewire | `livewire/edit-call-record.blade.php` | Formulario para editar metadatos o clasificaciones de una llamada grabada. |
| Livewire | `livewire/list-call-queues.blade.php` | Panel de gestión y configuración de las colas de llamadas de telefonía. |
| Livewire | `livewire/list-channels.blade.php` | Listado y estado técnico de los canales de telefonía integrados (Voz, Chat, Correo). |
| Livewire | `livewire/list-case-subtypes.blade.php` | Clasificación y tipificación de motivos de llamadas y casos de soporte telefónico. |

---

## 4. Módulo: CoreModule

Módulo base. Administra la seguridad, inicio de sesión corporativo, gestión de accesos y mantenimiento del sistema.

### Vistas de Autenticación

| Tipo | Archivo / Componente | Descripción |
| :--- | :--- | :--- |
| Blade | `auth/login.blade.php` | Pantalla de inicio de sesión estándar del sistema. |
| Blade | `auth/register.blade.php` | Pantalla de registro de nuevos usuarios (opcional/desactivada en entornos restrictivos). |
| Blade | `auth/forgot-password.blade.php` | Formulario para solicitar el enlace de restablecimiento de contraseña. |
| Blade | `auth/reset-password.blade.php` | Pantalla para ingresar la nueva contraseña usando el token de seguridad. |
| Blade | `auth/verify-email.blade.php` | Pantalla que solicita verificación de dirección de correo electrónico. |
| Blade | `auth/confirm-password.blade.php` | Bloqueo de seguridad que solicita confirmar contraseña antes de realizar acciones críticas. |
| Blade | `auth/two-factor-challenge.blade.php` | Formulario para el ingreso del código de autenticación de dos factores (2FA / TOTP). |

### Administración & Componentes Compartidos

| Tipo | Archivo / Componente | Descripción |
| :--- | :--- | :--- |
| Blade | `maintenance.blade.php` | Pantalla estática de "Sitio en Mantenimiento" para bloqueos totales del sistema. |
| Livewire | `livewire/system-maintenance.blade.php` | Panel administrativo para configurar logs, limpiezas y activar el modo mantenimiento. |
| Livewire | `livewire/users/list-users.blade.php` | Panel de control de usuarios. Permite ver, activar y suspender cuentas. |
| Livewire | `livewire/users/create-user.blade.php` | Formulario interactivo para dar de alta a un usuario en el sistema. |
| Livewire | `livewire/users/edit-user.blade.php` | Formulario para actualizar perfil, credenciales y estados de un usuario. |
| Livewire | `livewire/roles/list-roles.blade.php` | Visualizador y asignador de roles y permisos del monolito. |
| Livewire | `livewire/toast.blade.php` | Componente reutilizable para renderizar mensajes emergentes dinámicos (Toasts). |
| Livewire | `livewire/shared/notification-bell.blade.php` | Icono de campana de notificaciones con conteo de no leídos y dropdown dinámico. |

### Configuración del Perfil de Usuario

| Tipo | Archivo / Componente | Descripción |
| :--- | :--- | :--- |
| Blade | `settings/layout.blade.php` | Estructura base (layout) lateral para la zona de configuraciones del usuario. |
| Blade | `settings/⚡profile.blade.php` | Vista de edición de datos personales básicos del usuario (Nombre, foto, correo). |
| Blade | `settings/⚡security.blade.php` | Gestión de contraseñas y habilitación de 2FA. |
| Blade | `settings/two-factor/⚡recovery-codes.blade.php` | Códigos de respaldo de un solo uso para recuperación de 2FA. |
| Blade | `settings/⚡appearance.blade.php` | Configuración de preferencias visuales (Temas, dark/light mode). |
| Blade | `settings/⚡delete-user-form.blade.php` | Formulario para solicitar la baja o eliminación de la cuenta propia. |
| Blade | `settings/⚡delete-user-modal.blade.php` | Modal de confirmación final para la eliminación permanente de la cuenta. |
| Blade | `settings/⚡two-factor-setup-modal.blade.php` | Modal interactivo para escanear el código QR al configurar 2FA. |
| Blade | `settings/partials/heading.blade.php` | Cabecera reutilizable para las secciones de configuración. |

---

## 5. Módulo: DocumentationModule

Visualizador de manuales públicos y artículos del sistema orientados al cliente o agente de primera línea.

| Tipo | Archivo / Componente | Descripción |
| :--- | :--- | :--- |
| Livewire | `livewire/public/article-index.blade.php` | Portal de ayuda público para búsqueda de manuales. |
| Livewire | `livewire/public/article-detail.blade.php` | Visualizador de un artículo de ayuda individual en formato de lectura rápida. |
| Livewire | `livewire/admin/manage-articles.blade.php` | Panel administrativo simplificado para la moderación del material público. |

---

## 6. Módulo: FilesystemModule

Gestor de archivos y documentos adjuntos de la plataforma (ej. justificaciones médicas, contratos).

| Tipo | Archivo / Componente | Descripción |
| :--- | :--- | :--- |
| Blade | `partials/tree-node.blade.php` | Vista parcial recursiva para pintar estructuras de carpetas en árbol. |
| Livewire | `livewire/file-browser.blade.php` | Explorador interactivo de archivos y directorios almacenados localmente o en Amazon S3. |
| Livewire | `livewire/download-center.blade.php` | Bandeja de descargas para recuperar masivamente reportes programados previamente. |
| Livewire | `livewire/quota-manager.blade.php` | Panel de administración de límites de almacenamiento y cuotas por módulo. |

---

## 7. Módulo: HelpdeskModule

Módulo para el soporte interno corporativo (IT y Facilities).

| Tipo | Archivo / Componente | Descripción |
| :--- | :--- | :--- |
| Livewire | `livewire/my-tickets.blade.php` | Bandeja del empleado donde visualiza y gestiona las solicitudes de soporte que ha creado. |
| Livewire | `livewire/manage-tickets.blade.php` | Panel para el equipo técnico de soporte; permite asignar, priorizar y resolver incidentes. |
| Livewire | `livewire/ticket-detail.blade.php` | Detalle del ticket de soporte, incluyendo la cronología del chat o logs de resolución. |

---

## 8. Módulo: KnowledgeModule

Base de conocimiento avanzada y FAQs internas utilizadas por los agentes durante llamadas de servicio al cliente.

| Tipo | Archivo / Componente | Descripción |
| :--- | :--- | :--- |
| Livewire | `livewire/operator-view.blade.php` | Vista rápida de búsqueda de manuales internos optimizada para agentes en llamada (diseño compacto). |
| Livewire | `livewire/manage-articles.blade.php` | Consola de administración de la base de conocimiento interna. |
| Livewire | `livewire/upsert-article.blade.php` | Formulario con editor enriquecido (WYSIWYG) para crear y editar artículos. |
| Livewire | `livewire/article-detail.blade.php` | Lectura de artículos internos de la base de conocimientos con marcas de verificación. |

---

## 9. Módulo: OperationsModule

El núcleo de monitoreo operacional del negocio. Mide adherencias, calcula KPIs y expone dashboards integrados.

### Dashboards e Interfaces Principales

| Tipo | Archivo / Componente | Descripción |
| :--- | :--- | :--- |
| Livewire | `livewire/dashboard.blade.php` | Panel maestro operativo. Consolida el estado general de la campaña en el día de hoy. |
| Livewire | `livewire/advanced-productivity-dashboard.blade.php` | Monitor avanzado de productividad cruzando llamadas vs. horarios. |
| Livewire | `livewire/realtime-monitoring.blade.php` | Consola en tiempo real de supervisores para monitorizar a qué colas están asignados los agentes. |
| Livewire | `livewire/performance-scorecard.blade.php` | Tarjeta de puntuación individual del agente (Calidad, Adherencia, Productividad). |
| Livewire | `livewire/reporting-framework-index.blade.php` | Centro de generación y exportación de reportes históricos de llamadas y horas. |
| Livewire | `livewire/queue-performance-report.blade.php` | Analizador específico del comportamiento operativo de colas de llamadas. |
| Livewire | `livewire/intraday-availability.blade.php` | Visualización en vivo del volumen de agentes programados vs reales por intervalo. |
| Livewire | `livewire/agent-timeline.blade.php` | Línea de tiempo interactiva (Gantt) que muestra los estados diarios del agente. |
| Livewire | `livewire/agent-realtime-card.blade.php` | Tarjeta de estado individual en cuadrícula que indica si el agente está en llamada o excedido en break. |
| Livewire | `livewire/team-performance-summary.blade.php` | Resumen operativo agregador de KPIs por Supervisor. |

### Widgets Específicos (Sub-componentes)

| Tipo | Archivo / Componente | Descripción |
| :--- | :--- | :--- |
| Livewire | `livewire/widgets/queue-stats-widget.blade.php` | Indicador compacto de llamadas atendidas, abandonadas y nivel de servicio actual. |
| Livewire | `livewire/widgets/volume-comparison-widget.blade.php` | Gráfico de barras de llamadas ingresadas hoy comparado con el promedio histórico. |
| Livewire | `livewire/widgets/hero-kpi-widget.blade.php` | Widget destacado para mostrar el KPI principal del día (ej. TMO acumulado). |
| Livewire | `livewire/widgets/recent-incidents-widget.blade.php` | Lista dinámica de las incidencias de asistencia de agentes ocurridas en la última hora. |
| Livewire | `livewire/widgets/critical-alerts-widget.blade.php` | Alertas rojas críticas del sistema (ej. colas caídas o excesos de tiempo no listos). |
| Livewire | `livewire/widgets/state-distribution-widget.blade.php` | Gráfico circular con la distribución del personal activo en llamadas, breaks y auxiliares. |

---

## 10. Módulo: PersonnelModule

Gestor del organigrama, jerarquías de equipos, perfiles de personal e importación de nómina.

### Vistas Tradicionales (Controladores Clásicos)

| Tipo | Archivo / Componente | Descripción |
| :--- | :--- | :--- |
| Blade | `index.blade.php` | Página principal de RRHH para la administración general del organigrama. |
| Blade | `show.blade.php` | Ficha consolidada con toda la información del empleado. |
| Blade | `create.blade.php` | Formulario estándar de registro de un nuevo empleado. |
| Blade | `edit.blade.php` | Formulario de modificación de los datos principales del empleado. |
| Blade | `import.blade.php` | Interfaz de carga masiva de personal mediante plantillas de Excel. |
| Blade | `location_index.blade.php` | Gestión de ubicaciones físicas y sedes geográficas de la organización. |
| Blade | `manage-team-assignments.blade.php` | Panel visual para la asignación masiva de agentes a diferentes grupos de supervisión. |

### Componentes Livewire

| Tipo | Archivo / Componente | Descripción |
| :--- | :--- | :--- |
| Livewire | `livewire/list-directorates.blade.php` | Administración del nivel más alto de la jerarquía (Direcciones / Gerencias). |
| Livewire | `livewire/create-directorate.blade.php` | Formulario de creación de una Gerencia. |
| Livewire | `livewire/edit-directorate.blade.php` | Formulario de modificación de una Gerencia. |
| Livewire | `livewire/show-directorate.blade.php` | Detalle de la Gerencia con sus Departamentos asignados. |
| Livewire | `livewire/list-departments.blade.php` | Listado y gestión de Departamentos organizacionales. |
| Livewire | `livewire/create-department.blade.php` | Formulario para dar de alta un Departamento. |
| Livewire | `livewire/edit-department.blade.php` | Formulario de edición de un Departamento. |
| Livewire | `livewire/show-department.blade.php` | Detalle del Departamento y listado de sus Equipos. |
| Livewire | `livewire/list-teams.blade.php` | Listado general de los Equipos operativos. |
| Livewire | `livewire/create-team.blade.php` | Formulario de creación de un Equipo (asociando Supervisor). |
| Livewire | `livewire/edit-team.blade.php` | Formulario de modificación de un Equipo. |
| Livewire | `livewire/show-team.blade.php` | Vista detallada del Equipo mostrando a todos sus agentes activos. |
| Livewire | `livewire/manage-team-members.blade.php` | Panel interactivo para agregar o quitar empleados de un equipo. |
| Livewire | `livewire/team-member-transfer.blade.php` | Formulario de traspaso formal de un empleado a otro equipo, requiriendo justificación. |
| Livewire | `livewire/list-positions.blade.php` | Listado de puestos y cargos de trabajo autorizados en el organigrama. |
| Livewire | `livewire/create-position.blade.php` | Formulario para registrar un nuevo puesto laboral. |
| Livewire | `livewire/edit-position.blade.php` | Formulario para cambiar requerimientos o nombres de puestos. |
| Livewire | `livewire/show-position.blade.php` | Detalle del puesto y nómina de agentes que lo ostentan. |
| Livewire | `livewire/list-employees.blade.php` | Vista de tabla paginada con el maestro general de empleados. |
| Livewire | `livewire/create-employee.blade.php` | Componente paso a paso para el registro manual de un empleado. |
| Livewire | `livewire/edit-employee.blade.php` | Componente de actualización de ficha de empleado. |
| Livewire | `livewire/import-employees.blade.php` | Panel de carga masiva por lotes con reportes en vivo de progreso de inserción. |
| Livewire | `livewire/staffing-summary.blade.php` | Cuadro resumen de personal activo, inactivo y vacaciones contratadas en la campaña. |

---

## 11. Módulo: WfmModule

El motor de planificación de turnos de la operación (Workforce Management).

### Componentes Livewire

| Tipo | Archivo / Componente | Descripción |
| :--- | :--- | :--- |
| Livewire | `livewire/my-schedule.blade.php` | Calendario mensual individual del agente para la visualización de sus turnos. |
| Livewire | `livewire/my-day.blade.php` | Cronograma del día actual para el agente detallando sus breaks e intraday. |
| Livewire | `livewire/my-team.blade.php` | Vista del equipo del agente, útil para coordinar intercambios de turnos. |
| Livewire | `livewire/my-metrics.blade.php` | Cuadro de autogestión de métricas de puntualidad y horas laboradas del agente. |
| Livewire | `livewire/request-shift-swap.blade.php` | Formulario interactivo para proponer un intercambio de turno a un compañero. |
| Livewire | `livewire/swap-request-history.blade.php` | Historial personal de solicitudes de cambio de turno enviadas y recibidas. |
| Livewire | `livewire/wfm-swap-approvals.blade.php` | Panel del planificador WFM para dar la autorización final a los cambios de turno autorizados. |
| Livewire | `livewire/request-leave.blade.php` | Formulario para solicitar permisos remunerados, vacaciones o licencias médicas. |
| Livewire | `livewire/leave-request-history.blade.php` | Historial y estado de las solicitudes de vacaciones del empleado. |
| Livewire | `livewire/manager-approvals.blade.php` | Bandeja de los Supervisores para autorizar o rechazar solicitudes de su propio equipo. |
| Livewire | `livewire/request-summary.blade.php` | Resumen ejecutivo y conteo de días tomados por concepto de licencias. |
| Livewire | `livewire/manage-schedules.blade.php` | Panel maestro para la administración individual de turnos de trabajo diarios. |
| Livewire | `livewire/weekly-planning.blade.php` | Vista de cuadrícula horizontal para la planificación visual semanal del personal. |
| Livewire | `livewire/weekly-planning-teams.blade.php` | Planificación semanal agrupada a nivel de equipos. |
| Livewire | `livewire/team-weekly-planning.blade.php` | Visualizador rápido de la malla del equipo completo para coordinaciones internas. |
| Livewire | `livewire/employee-weekly-planning.blade.php` | Vista detallada del planificador para ajustar los turnos diarios de un agente específico. |
| Livewire | `livewire/manage-absence-reasons.blade.php` | Configuración de los motivos válidos de ausencia (Vacaciones, Licencia, etc.). |
| Livewire | `livewire/manage-activity-types.blade.php` | Configuración de los sub-estados del turno (Break, Lunch, Backoffice). |
| Livewire | `livewire/manage-agent-states.blade.php` | Configuración de los estados de conexión en telefonía homologados. |
| Livewire | `livewire/manage-intraday-activities.blade.php` | Consola para ajustar las actividades intra-día de los agentes en tiempo real. |
| Livewire | `livewire/manage-scheduled-activities.blade.php` | Administrador de las plantillas de actividades repetitivas asociadas a turnos. |
| Livewire | `livewire/manage-schedule-exceptions.blade.php` | Gestión de excepciones al turno planificado (Tardanzas justificadas, emergencias). |
| Livewire | `livewire/operational-settings.blade.php` | Configuración operativa del WFM (Parámetros Erlang, holguras de tardanza). |
| Livewire | `livewire/import-weekly-schedule.blade.php` | Panel para procesar y validar el archivo Excel de mallas horarias semanales. |

---

*Nota técnica: Los módulos `QualityModule`, `SupportModule` y `WorkflowsModule` no poseen archivos de vistas en la carpeta `Resources/Views` del legado, ya que su funcionalidad se ejecuta a nivel de API backend o sus vistas se encuentran encapsuladas en otros componentes transversales.*
