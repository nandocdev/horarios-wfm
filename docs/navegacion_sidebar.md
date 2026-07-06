# Documentación de Navegación del Sidebar

> **Fuente:** `app/Helpers/MenuHelper.php` — generación centralizada con filtrado por permisos Spatie y Gates.
> **Render:** `resources/views/layouts/app/sidebar.blade.php` + `resources/views/layouts/app/partials/menu-item.blade.php`
> **Íconos:** FluxUI (Heroicons vía `flux:icon`)

---

## 1. Dashboard (Panel Principal)

- **Ruta:** `/dashboard`
- **Nombre de Ruta:** `dashboard`
- **Módulo:** `OperationsModule`
- **Componente:** `App\Modules\OperationsModule\Livewire\Dashboard`
- **Middleware de ruta:** `['auth', 'verified']`
- **Permiso requerido:** `null` (visible para todo usuario autenticado y verificado)
- **Roles con acceso:** Todos los roles autenticados

### Qué puedo hacer aquí
- **Seleccionar fecha** — ver KPIs y widgets de un día específico
- **Visualizar widgets** de KPIs operativos: volumen de llamadas, adherencia promedio, notificaciones recientes
- **Auto-refresh** cada 15 segundos — los widgets se actualizan solos

### Tablas / KPIs
No hay tabla — es un tablero con tarjetas de métricas y widgets gráficos.

### Vistas hijas
Ninguna. Es una vista única sin modales de creación/edición.

---

## 2. Blog (Comunicación Interna)

- **Ruta:** `/`
- **Nombre de Ruta:** `home`
- **Módulo:** `CommunicationsModule`
- **Componente:** `App\Modules\CommunicationsModule\Livewire\Home`
- **Middleware de ruta:** `web` (público — sin autenticación)
- **Permiso requerido:** `null` (público)

### Qué puedo hacer aquí
- **Ver noticias** — las 4 más recientes en formato de tarjetas
- **Abrir noticia completa** — modal con contenido completo y comentarios
- **Comentar noticia** — campo de texto inline (máx. 1000 caracteres)
- **Votar en encuesta activa** — seleccionar respuesta de la última encuesta publicada
- **Enviar reconocimiento** — modal para crear shoutout:
  - `employee_id` (seleccionar compañero)
  - `message` (texto, 5-200 caracteres)
  - `workflow_action` (default: `submit_review`)
- **Reaccionar a shoutout** — toggle de like/reacción
- **Ver notificaciones** — panel con notificaciones recientes no leídas

### Vistas hijas
Ninguna — todo ocurre en modales sobre la misma página.

---

## 3. Descargas

- **Ruta:** `/descargas`
- **Nombre de Ruta:** `filesystem.download-center`
- **Módulo:** `FilesystemModule`
- **Componente:** `App\Modules\FilesystemModule\Livewire\DownloadCenter`
- **Middleware de ruta:** `web` (público)
- **Permiso requerido:** `null` (público)
- **Estado:** ✅ Implementado

### Qué puedo hacer aquí
- **Buscar archivos públicos** por nombre
- **Descargar archivo** — clic para descargar

### Columnas de tabla
Nombre del archivo, ordenado alfabéticamente.

### Filtros
Búsqueda por texto sobre nombre de archivo.

### Vistas hijas
Ninguna.

---

## 4. Mi Espacio (Módulo Operador)

Sección orientada al agente de call center para su autogestión diaria. Visible para todo usuario autenticado.

### 4.1. Mi Día

- **Ruta:** `/schedules/my-day`
- **Nombre de Ruta:** `schedules.my-day`
- **Módulo:** `WfmModule`
- **Componente:** `App\Modules\WfmModule\Livewire\MyDay`
- **Permiso:** `null`

#### Qué puedo hacer aquí
- **Ver agenda del día** — línea de tiempo visual con mi horario, breaks, almuerzos y actividades intradía
- **Ver estadísticas del día** — tarjetas con tiempo total, tiempo productivo, % de ocupación
- **Filtrar por equipo** (solo supervisores/coordinadores) — selector de equipo
- **Filtrar por empleado** (solo supervisores/coordinadores) — ver el día de un subordinado

#### Vistas hijas
Ninguna — vista de solo lectura.

---

### 4.2. Mi Horario

- **Ruta:** `/schedules/my-schedule`
- **Nombre de Ruta:** `schedules.my-schedule`
- **Módulo:** `WfmModule`
- **Componente:** `App\Modules\WfmModule\Livewire\MySchedule`
- **Permiso:** `null`

#### Qué puedo hacer aquí
- **Seleccionar semana** — navegador de semanas (últimas 8)
- **Seleccionar día** — selector de día (lunes a domingo)
- **Ver turnos asignados** — grid semanal con mis horarios planificados
- **Ver excepciones** — lista de excepciones de horario para la semana
- **Ver actividades intradía** — actividades programadas para el día seleccionado

#### Vistas hijas
Ninguna — vista de solo lectura.

---

### 4.3. Mis Estadísticas

- **Ruta:** `/operations/performance`
- **Nombre de Ruta:** `operations.performance`
- **Módulo:** `OperationsModule`
- **Componente:** `App\Modules\OperationsModule\Livewire\PerformanceScorecard`
- **Permiso:** `null`

#### Qué puedo hacer aquí
- **Seleccionar fecha** — ver rendimiento de un día específico
- **Seleccionar equipo** — filtrar por equipo (si aplica)
- **Seleccionar empleado** — ver estadísticas de un agente en particular
- **Seleccionar período** — diario / semanal / mensual
- **Buscar empleado** por nombre
- **Ver métricas de rendimiento** — adherencia, AHT, tardanzas, nivel de servicio

#### Columnas de tabla
Empleado (nombre/avatar), métricas de rendimiento por día en el período seleccionado.

#### Vistas hijas
Ninguna — vista de dashboards y tablas.

---

### 4.4. Historial de Cambios

- **Ruta:** `/schedules/swap-history`
- **Nombre de Ruta:** `schedules.swap-history`
- **Módulo:** `WfmModule`
- **Componente:** `App\Modules\WfmModule\Livewire\SwapRequestHistory`
- **Permiso:** `null`

#### Qué puedo hacer aquí
- **Ver solicitudes** — listado paginado de cambios de turno donde soy solicitante o destinatario
- **Ver detalle** — modal con turnos involucrados y aprobaciones
- **Cancelar** solicitud propia si está pendiente
- **Aceptar** solicitud recibida si está pendiente (solo destinatario)
- **Rechazar** solicitud recibida si está pendiente (solo destinatario)

#### Columnas de tabla
Solicitante, Destinatario, Equipo, Fecha inicio, Estado (pending/accepted/rejected/cancelled), Creado el.

#### Vistas hijas
- **Modal de detalle** (`swap-details`): muestra turnos del solicitante y destinatario, historial de aprobaciones.

---

### 4.5. Historial de Permisos

- **Ruta:** `/schedules/leave-history`
- **Nombre de Ruta:** `schedules.leave-history`
- **Módulo:** `WfmModule`
- **Componente:** `App\Modules\WfmModule\Livewire\LeaveRequestHistory`
- **Permiso:** `null`

#### Qué puedo hacer aquí
- **Ver solicitudes** — listado paginado de mis permisos/vacaciones
- **Ver detalle** — modal con cadena de aprobaciones
- **Cancelar** solicitud propia si está pendiente

#### Columnas de tabla
Inicio, Fin, Estado, ordenado por inicio descendente.

#### Vistas hijas
- **Modal de detalle** (`leave-details`): muestra la cadena de aprobación de la solicitud.

---

### 4.6. Archivos

- **Ruta:** `/filesystem`
- **Nombre de Ruta:** `filesystem.index`
- **Módulo:** `FilesystemModule`
- **Componente:** `App\Modules\FilesystemModule\Livewire\FileBrowser`
- **Permiso:** `null`
- **Estado:** ✅ Implementado

#### Qué puedo hacer aquí
- **Navegar carpetas** — exploración jerárquica con breadcrumbs
- **Buscar archivos/carpetas** por nombre
- **Alternar vista** — `Mis Archivos` / `Compartidos`
- **Crear carpeta**
- **Subir archivos** — múltiples archivos, hasta 100MB cada uno
- **Descargar archivo**
- **Eliminar archivo o carpeta**
- **Compartir** archivo/carpeta con otro usuario (selector + nivel de acceso)
- **Alternar acceso público** de un archivo

#### Modales
- **Crear carpeta:** `newFolderName` (requerido, máx. 255 caracteres)
- **Compartir:** `shareTargetUserId` (buscar y seleccionar usuario), `shareAccessLevel` (view/...)

#### Columnas de tabla
Carpetas (nombre, cant. hijos, cant. archivos), Archivos (nombre, tamaño, compartido, público).

#### Vistas hijas
Ninguna — todo en una vista con tabs `my_files` / `shared`.

---

## 5. Equipo (Gestión de Supervisores)

Orientado a directores de equipo, coordinadores y jefes de operación.

- **Permiso padre:** `menu.team`
- **Coordinator Override:** Se concede automáticamente si `hasCoordinatorRights()`.

### 5.1. Desempeño

- **Ruta:** `/operations/team-performance?view=compliance`
- **Nombre de Ruta:** `operations.team-performance`
- **Componente:** `App\Modules\OperationsModule\Livewire\TeamPerformanceSummary`
- **Permiso:** `reports.compliance`

#### Qué puedo hacer aquí
- **Seleccionar equipo** — ver KPIs de un equipo específico
- **Seleccionar fecha** — métricas de un día concreto
- **Ordenar** por operador / productividad / utilización
- **Alternar vista** — resumen vs. detalle
- **Ver totales del equipo** — utilización, productividad, ausentismo

#### Columnas de tabla
Empleado (nombre/avatar), Productividad %, Utilización %, Minutos productivos totales, Minutos conectados totales, Llamadas, Estado de asistencia.

#### Vistas hijas
- **Vista resumen:** tarjetas con totales del equipo.
- **Vista detalle:** desglose por empleado con métricas individuales.

---

### 5.2. Analítica Avanzada

- **Ruta:** `/operations/advanced-analytics`
- **Nombre de Ruta:** `operations.advanced-analytics`
- **Componente:** `App\Modules\OperationsModule\Livewire\AdvancedProductivityDashboard`
- **Permiso:** `menu.team`

#### Qué puedo hacer aquí
- **Seleccionar fecha**
- **Seleccionar equipo**
- **Ver métricas agregadas** — PWI promedio, disponibilidad, eficiencia, unidades de trabajo totales, brecha de capacidad
- **Ver top 5** mejores desempeños por PWI
- **Ver top 5** menor desempeño por brecha de capacidad

#### Columnas de tabla
Empleado, Equipo, Posición, PWI %, Disponibilidad %, Eficiencia %, Unidades de trabajo, Llamadas capacidad, Llamadas reales, Brecha.

#### Vistas hijas
Ninguna.

---

### 5.3. Vista de Equipo

- **Ruta:** `/schedules/my-team`
- **Nombre de Ruta:** `schedules.my-team`
- **Componente:** `App\Modules\WfmModule\Livewire\MyTeam`
- **Permiso:** `schedules.view_team`

#### Qué puedo hacer aquí
- **Seleccionar equipo**
- **Navegar semanas** — ver la planificación del equipo
- **Ver grid semanal** — matriz de días vs. miembros del equipo con asignaciones
- **Ver excepciones** en el grid
- **Ver solicitudes de permiso pendientes** en el grid
- **Registrar incidencia** — modal para crear excepción de horario
- **Editar incidencia** — modificar excepción existente
- **Eliminar incidencia**
- **Ver intercambios recientes** — panel lateral de actividad reciente
- **Ver próximas excepciones** — panel lateral de permisos próximos

#### Modal de Incidencia
- `employee_id` (oculto, del contexto)
- `date` (fecha)
- `reason_id` (select desde AbsenceReasonCode)
- `start_time` (time, default 08:00)
- `end_time` (time, default 17:00)
- `is_full_day` (checkbox, default true)
- `remarks` (texto, nullable)

#### Vistas hijas
- **Panel de incidencias:** CRUD completo de excepciones sobre el grid semanal.
- **Paneles laterales:** intercambios recientes y próximas excepciones.

---

### 5.4. Solicitudes (Permisos)

- **Ruta:** `/schedules/manager-approvals`
- **Nombre de Ruta:** `schedules.manager-approvals`
- **Componente:** `App\Modules\WfmModule\Livewire\ManagerApprovals`
- **Permiso:** `wfm.leaves.manage`
- **Badge:** Contador de `pending_leaves`

#### Qué puedo hacer aquí
- **Ver solicitudes pendientes** de permisos del personal a cargo
- **Aprobar** solicitud de permiso
- **Rechazar** solicitud de permiso

#### Columnas de tabla
Empleado, Posición, Equipo, Inicio/Fin, Tipo, Estado.

#### Vistas hijas
Ninguna — las acciones de aprobar/rechazar ocurren inline.

---

### 5.5. Aprobaciones (Cambios de Turno)

- **Ruta:** `/schedules/wfm-approvals`
- **Nombre de Ruta:** `schedules.wfm-approvals`
- **Componente:** `App\Modules\WfmModule\Livewire\WfmSwapApprovals`
- **Permiso:** `wfm.swaps.manage`
- **Badge:** Contador de `pending_swaps`

#### Qué puedo hacer aquí
- **Alternar tabs** — `pendientes` / `historial`
- **Ver detalle** — modal con turnos del solicitante y destinatario, historial de aprobaciones
- **Aprobar** intercambio
- **Rechazar** intercambio (con motivo)

#### Columnas de tabla
Solicitante, Destinatario, Equipo, Fecha, Estado, Creado el.

#### Vistas hijas
- **Tab Pendientes:** solicitudes que requieren acción.
- **Tab Historial:** solicitudes ya procesadas.
- **Modal de detalle** (`swap-details`): turnos y aprobaciones.

---

## 6. Planificación (WFM Engine)

Sección restringida a analistas de Workforce Management.

- **Permiso padre:** `menu.planning`
- **Roles con acceso:** wfm, admin

### 6.1. Planificación Semanal

- **Ruta:** `/schedules/planning`
- **Nombre de Ruta:** `schedules.planning`
- **Componente:** `App\Modules\WfmModule\Livewire\WeeklyPlanning`
- **Permiso:** `wfm.planning.manage`

#### Qué puedo hacer aquí
- **Ver semanas planificadas** — listado paginado de semanas
- **Crear siguiente semana** — genera contenedor de planificación semanal
- **Confirmar creación** — modal de confirmación con fechas auto-calculadas
- **Publicar semana** — cambia estado de draft a published

#### Modal de creación
Confirmación de fechas `nextWeekStart` y `nextWeekEnd` (auto-calculadas, sin campos de usuario).

#### Columnas de tabla
Inicio de semana, Fin de semana, Estado (draft/published), Cant. asignaciones por equipo.

#### Vistas hijas
Ninguna — CRUD mínimo en lista + modal.

---

### 6.2. Actividades Intradía

- **Ruta:** `/schedules/intraday-activities/manage`
- **Nombre de Ruta:** `schedules.intraday-activities.manage`
- **Componente:** `App\Modules\WfmModule\Livewire\ManageIntradayActivities`
- **Permiso:** `wfm.intraday.manage`

#### Qué puedo hacer aquí
- **Seleccionar fecha** — ver actividades del día
- **Seleccionar equipo** (WFM) — filtrar por equipo
- **Ver períodos aprobados** para la fecha/equipo
- **Crear período** (WFM) — modal con:
  - `periodTeamId` (select equipo)
  - `periodActivityDefinitionId` (select definición)
  - `periodDate` (fecha)
  - `periodStartTime` (hora inicio)
  - `periodEndTime` (hora fin)
  - `periodMaxSlots` (entero 1-100)
  - `periodNotes` (texto, nullable)
- **Editar período** (WFM)
- **Eliminar período** (WFM)
- **Asignar actividad a empleados** — modal con:
  - `selectedEmployeeIds` (multi-select checkboxes)
  - `startTime` (hora, dentro del rango del período)
  - `endTime` (hora, dentro del rango del período)
  - `assignNotes` (texto, nullable)
- **Eliminar asignación** de actividad

#### Columnas de tabla (períodos)
Equipo, Definición de actividad, Inicio/Fin, Cupo máximo, Asignaciones, Acciones.

#### Columnas de tabla (actividades)
Empleado, Tipo actividad, Rango horario, Notas.

#### Vistas hijas
- **CRUD de períodos:** crear/editar/eliminar períodos aprobados.
- **CRUD de asignaciones:** asignar/desasignar empleados a períodos.

---

### 6.3. Definiciones de Actividad

- **Ruta:** `/schedules/scheduled-activities`
- **Nombre de Ruta:** `schedules.scheduled-activities`
- **Componente:** `App\Modules\WfmModule\Livewire\ManageScheduledActivities`
- **Permiso:** `wfm.catalogs.shifts`

#### Qué puedo hacer aquí
- **Crear definición** — modal con:
  - `name` (requerido, máx. 150)
  - `activity_type_id` (select desde ActivityType)
  - `default_duration_minutes` (entero, nullable)
  - `default_location` (texto, nullable)
  - `default_instructor` (texto, nullable)
  - `is_active` (booleano)
- **Editar definición**
- **Eliminar definición**

#### Columnas de tabla
Nombre, Tipo actividad, Duración default, Ubicación, Instructor, Activo.

#### Vistas hijas
Ninguna — CRUD completo con modal único.

---

### 6.4. Excepciones Masivas

- **Ruta:** `/schedules/exceptions`
- **Nombre de Ruta:** `schedules.exceptions`
- **Componente:** `App\Modules\WfmModule\Livewire\ManageScheduleExceptions`
- **Permiso:** `wfm.exceptions.manage`

#### Qué puedo hacer aquí
- **Buscar** excepciones por nombre de empleado
- **Crear excepción** — modal con:
  - `employee_id` (select empleado)
  - `absence_reason_code_id` (select motivo)
  - `start_at` (datetime-local)
  - `end_at` (datetime-local)
  - `is_full_day` (booleano, default true)
  - `remarks` (texto, nullable, máx. 500)
- **Editar excepción**
- **Eliminar excepción**

#### Columnas de tabla
Empleado, Motivo, Inicio, Fin, Día completo, Creado por.

#### Vistas hijas
Ninguna — CRUD completo con modal único + búsqueda.

---

## 7. Operación (Supervisión Intra-Día)

Monitoreo en tiempo real del piso de operaciones.

- **Permiso padre:** `wfm.realtime.view`
- **Coordinator Override:** Sí.

### 7.1. Realtime

- **Ruta:** `/operations/realtime`
- **Nombre de Ruta:** `operations.realtime`
- **Componente:** `App\Modules\OperationsModule\Livewire\RealtimeMonitoring`
- **Permiso:** `wfm.realtime.view`

#### Qué puedo hacer aquí
- **Auto-refresh** — polling periódico de estados en tiempo real
- **Filtrar por equipo**
- **Filtrar por posición**
- **Filtrar por cola**
- **Filtrar por estado actual** (Ready, Not Ready, Break, etc.)
- **Filtrar por código de razón**
- **Filtrar por estado esperado** (SHIFT, INTRADAY, EXCEPTION, ABSENT, OFF)
- **Alternar solo activos** — ocultar agentes desconectados
- **Buscar empleado** por nombre
- **Ordenar** por cualquier columna
- **Limpiar todos los filtros**
- **Ver detalle de agente** — modal con estado actual, esperado, alertas y últimas 10 transiciones
- **Ver estadísticas operativas** — totales de: adherentes, no adherentes, ready, talking, not_ready, ausentes, desconectados

#### Columnas de tabla
Empleado, Equipo, Posición, Estado actual, Duración del estado, Razón, Cola, Estado esperado, Indicador de adherencia, Alertas.

#### Vistas hijas
- **Modal de detalle de agente** (`agent-details-modal`): estado actual, esperado, adherencia, alertas, últimas 10 transiciones de estado.

---

### 7.2. Disponibilidad

- **Ruta:** `/operations/availability`
- **Nombre de Ruta:** `operations.availability`
- **Componente:** `App\Modules\OperationsModule\Livewire\IntradayAvailability`
- **Permiso:** `wfm.availability.view`

#### Qué puedo hacer aquí
- **Ver métricas en tiempo real** (auto-refresh cada 10s):
  - Totales: programados, conectados, % adherencia
  - Estados: talking, ready, not_ready
  - Desglose not_ready por razón
- **Ver resumen de colas (CSQ)**:
  - Llamadas en espera, llamada más antigua, nivel de servicio, agentes en talking

#### KPIs visuales
Tarjetas: Programados, Conectados, Ausentes, Excepciones, % Adherencia, Talking, Ready, Not Ready.

#### Columnas de tabla (CSQ)
Nombre cola, Llamadas esperando, Llamada más larga, Nivel servicio, Agentes talking.

#### Vistas hijas
Ninguna — tablero de solo lectura con auto-refresh.

---

## 8. Reportes (Centro Analítico)

- **Permiso padre:** `menu.reports`
- **Roles con acceso:** chief, wfm, director, admin

### 8.1. Centro de Reportes

- **Ruta:** `/operations/reports`
- **Nombre de Ruta:** `operations.reports`
- **Componente:** `App\Modules\OperationsModule\Livewire\ReportingFrameworkIndex`
- **Permiso:** `reports.reports`
- **Badge:** `HUB`

#### Qué puedo hacer aquí
- **Navegar a reportes** — hub que agrupa y redirige a:
  - Capa 1: Adherencia y Cobertura, Disponibilidad Intradía
  - Capa 2: Analítica PWI/Work Units, Scorecard de Desempeño
  - Capa 3: Reporte de Colas/SLA
  - Capa 4: Resumen de Solicitudes, Inventario de Staffing
  - Capa 5: Dashboard de Operación (Executive)

#### Vistas hijas
Este componente es en sí mismo un hub de navegación hacia los demás reportes.

---

### 8.2. Adherencia y Cobertura

- **Ruta:** `/operations/team-performance?view=attendance`
- **Nombre de Ruta:** `operations.team-performance`
- **Componente:** `App\Modules\OperationsModule\Livewire\TeamPerformanceSummary`
- **Permiso:** `reports.attendance`

#### Qué puedo hacer aquí
Mismas acciones que Desempeño (sección 5.1) pero con vista inicial en `attendance`.
- Ver métricas de asistencia y cumplimiento de horarios
- Totales de equipo en asistencia

---

### 8.3. Productividad Operativa

- **Ruta:** `/operations/advanced-analytics`
- **Nombre de Ruta:** `operations.advanced-analytics`
- **Permiso:** `reports.scorecard`

Mismas acciones que Analítica Avanzada (sección 5.2), pero accesible desde el menú de Reportes con permiso específico `reports.scorecard`.

---

### 8.4. Performance por Cola

- **Ruta:** `/operations/queue-performance`
- **Nombre de Ruta:** `operations.queue-performance`
- **Componente:** `App\Modules\OperationsModule\Livewire\QueuePerformanceReport`
- **Permiso:** `reports.reports`

#### Qué puedo hacer aquí
- **Seleccionar fecha**
- **Ver estadísticas de colas:**
  - Ofrecidas, Atendidas, Abandonadas
  - AHT promedio, ASA promedio, Espera máxima
  - Nivel de servicio (SLA)

#### Columnas de tabla
Nombre cola, Meta AHT, Total ofrecidas, Atendidas, Abandonadas, AHT prom., ASA prom., Espera máxima, Nivel servicio.

#### Vistas hijas
Ninguna.

---

### 8.5. Gestión de Solicitudes

- **Ruta:** `/schedules/reports/requests`
- **Nombre de Ruta:** `schedules.request-summary`
- **Componente:** `App\Modules\WfmModule\Livewire\RequestSummary`
- **Permiso:** `reports.requests`
- **Middleware adicional:** `->can('reports.requests')`

#### Qué puedo hacer aquí
- **Ver resumen de solicitudes de permiso:** totales, pendientes, aprobados, rechazados
- **Ver resumen de cambios de turno:** totales, pendientes, aprobados, rechazados
- **Ver distribución por tipo de permiso**

#### Vistas hijas
Ninguna — vista de solo lectura con tarjetas de KPIs y gráficos de distribución.

---

### 8.6. Executive Dashboard

- **Ruta:** `/operations/dashboard`
- **Nombre de Ruta:** `operations.dashboard`
- **Componente:** `App\Modules\OperationsModule\Livewire\Dashboard`
- **Permiso:** `reports.reports`

#### Qué puedo hacer aquí
Mismas acciones que Dashboard principal (sección 1) pero orientado a perfil ejecutivo/gerencial, sin widgets operativos pesados.

---

### 8.7. Inventario de Staffing

- **Ruta:** `/personnel/reports/staffing`
- **Nombre de Ruta:** `personnel.staffing-summary`
- **Componente:** `App\Modules\PersonnelModule\Livewire\StaffingSummary`
- **Permiso:** `reports.staffing`
- **Middleware adicional:** `->can('reports.staffing')`

#### Qué puedo hacer aquí
- **Ver totales de personal:** contratados, activos, inactivos, managers
- **Ver desglose por equipo** (nombre + activos)
- **Ver desglose por posición** (nombre + activos)
- **Ver desglose por estado laboral**

#### Vistas hijas
Ninguna — vista de solo lectura con tarjetas KPIs y listados de distribución.

---

## 9. Configuración (Catálogos y Administración del Sistema)

- **Permiso padre:** `menu.admin`
- **Roles con acceso:** admin, wfm

### 9.1. Documentación (Gestor)

- **Ruta:** `/admin/documentation/articles`
- **Nombre de Ruta:** `documentation.admin.articles`
- **Componente:** `App\Modules\DocumentationModule\Livewire\Admin\ManageArticles`
- **Permiso:** `articles.manage`
- **Middleware de ruta:** `can:articles.manage`

#### Qué puedo hacer aquí
- **Buscar artículos** por título
- **Crear artículo** — modal con:
  - `title` (requerido, máx. 255)
  - `content` (requerido, texto enriquecido)
  - `is_published` (booleano)
  - `selectedCategories` (multi-select categorías)
  - `sort_order` (entero)
- **Editar artículo**
- **Eliminar artículo**

#### Columnas de tabla
Título, Autor, Categorías, Publicado, Orden, Creado el.

#### Vistas hijas
Ninguna — CRUD completo con modal único + búsqueda.

---

### 9.2. Base de Conocimiento (Gestor)

- **Ruta:** `/admin/knowledge`
- **Nombre de Ruta:** `knowledge.admin`
- **Componente:** `App\Modules\KnowledgeModule\Livewire\ManageArticles`
- **Permiso:** `knowledge.manage`
- **Middleware de ruta:** `['auth', 'can:knowledge.manage']`

#### Qué puedo hacer aquí
- **Buscar artículos** por título o contenido
- **Filtrar por estado** (draft/review/published/archived)
- **Filtrar por categoría**
- **Eliminar artículo**

#### Columnas de tabla
Título, Categoría, Estado, Colas asociadas, Creador, Creado el.

#### Vistas hijas (externas)
- **Crear/Editar artículo:** redirige a un componente `ArticleForm` separado (no incluido en este listado).

---

### 9.3. Usuarios

- **Ruta:** `/admin/users`
- **Nombre de Ruta:** `users.index`
- **Componente padre:** `App\Modules\CoreModule\Livewire\Users\ListUsers`
- **Permiso:** `users.view`
- **Middleware de ruta:** `can:users.view`

#### Qué puedo hacer aquí (Listado)
- **Buscar usuarios** por nombre o email
- **Filtrar por rol**
- **Activar/Desactivar** usuario (toggle)
- **Eliminar usuario** (soft delete)

#### Columnas de tabla (listado)
Nombre, Email, Roles, Activo, Creado el.

#### Vistas hijas
- **Crear Usuario** — `/admin/users/create` → `App\Modules\CoreModule\Livewire\Users\CreateUser`
  - `name` (requerido)
  - `email` (requerido, único)
  - `password` (requerido)
  - `is_active` (booleano)
  - `force_password_change` (booleano)
  - `roles` (multi-select roles)

- **Editar Usuario** — `/admin/users/{user}/edit` → `App\Modules\CoreModule\Livewire\Users\EditUser`
  - Mismos campos que creación (excepto password, opcional)
  - `name`, `email`, `is_active`, `force_password_change`, `roles`

---

### 9.4. Roles y Permisos

- **Ruta:** `/admin/roles`
- **Nombre de Ruta:** `roles.index`
- **Componente padre:** `App\Modules\CoreModule\Livewire\Roles\ListRoles`
- **Permiso:** `roles.view`
- **Middleware de ruta:** `can:roles.view`

#### Qué puedo hacer aquí
- **Ver todos los roles** del sistema
- **Crear rol** — inline form con:
  - `name` (requerido)
  - `code` (requerido, ej: OP, SUP)
  - `hierarchy_level` (entero)
- **Editar permisos** — modal con checkboxes de todos los permisos disponibles
- **Guardar permisos** — sincroniza permisos seleccionados al rol

#### Columnas de tabla
Nombre rol, Código, Nivel jerarquía, Lista de permisos.

#### Vistas hijas
- **Modal de edición de permisos** (`role-permissions`): `selectedPermissions` (array de nombres de permiso, checkboxes).

---

### 9.5. Catálogos WFM

- **Permiso padre:** `wfm.catalogs.shifts`
- **Roles con acceso:** wfm, admin

#### 9.5.1. Turnos

- **Ruta:** `/schedules/shifts`
- **Nombre de Ruta:** `schedules.shifts`
- **Componente:** `App\Modules\WfmModule\Livewire\ManageSchedules`

**Qué puedo hacer aquí:**
- Crear, editar y eliminar turnos base
- **Modal con formulario:**
  - `name` (requerido, máx. 100)
  - `start_time` (time)
  - `end_time` (time)
  - `total_minutes` (auto-calculado)
  - `break_minutes` (entero, default 30)
  - `lunch_minutes` (entero, default 60)
  - `allowed_days` (array multi-select días)
  - `is_lunch_paid` (booleano)
  - `is_break_paid` (booleano, default true)
  - `is_active` (booleano, default true)

**Columnas:** Nombre, Inicio, Fin, Total min., Break, Almuerzo, Activo, Días permitidos.

#### 9.5.2. Tipos de Actividad

- **Ruta:** `/schedules/activity-types`
- **Nombre de Ruta:** `schedules.activity-types`
- **Componente:** `App\Modules\WfmModule\Livewire\ManageActivityTypes`

**Qué puedo hacer aquí:**
- Crear, editar y eliminar tipos de actividad
- **Modal:** `name` (req, máx 50), `color` (hex, default #cbd5e1), `is_productive` (bool), `is_paid` (bool, default true)

**Columnas:** Nombre, Color, Productivo, Pagado.

#### 9.5.3. Motivos de Ausencia

- **Ruta:** `/schedules/absence-reasons`
- **Nombre de Ruta:** `schedules.absence-reasons`
- **Componente:** `App\Modules\WfmModule\Livewire\ManageAbsenceReasons`

**Qué puedo hacer aquí:**
- Crear, editar y eliminar motivos de ausencia
- **Modal:** `name` (req, máx 100), `short_code` (req, máx 10), `requires_attachment` (bool), `is_excused` (bool, default true)

**Columnas:** Nombre, Código, Requiere adjunto, Es justificado.

#### 9.5.4. Estados de Agente

- **Ruta:** `/schedules/agent-states`
- **Nombre de Ruta:** `schedules.agent-states`
- **Componente:** `App\Modules\WfmModule\Livewire\ManageAgentStates`

**Qué puedo hacer aquí:**
- Crear, editar y eliminar estados de agente (mapeo Cisco)
- **Modal:** `external_code` (req, máx 50), `display_name` (req, máx 100), `is_productive` (bool), `color_hex` (req, hex, default #cbd5e1)

**Columnas:** Código externo, Nombre mostrado, Productivo, Color.

---

### 9.6. Empleados

- **Ruta:** `/employees`
- **Nombre de Ruta:** `employees.index`
- **Módulo:** `PersonnelModule`
- **Controlador:** `App\Modules\PersonnelModule\Http\Controllers\EmployeeController`
- **Permiso:** Gate `viewAny, Employee::class`

#### Qué puedo hacer aquí
- **Buscar empleados** por nombre, número o email
- **Filtrar por departamento**
- **Filtrar por posición**
- **Filtrar por estado laboral**
- **Filtrar por activos/inactivos**
- **Filtrar por es manager**
- **Filtrar por rango de fecha de contratación**
- **Limpiar todos los filtros**
- **Seleccionar todos** en página (checkbox masivo)
- **Exportar seleccionados a CSV**
- **Exportar seleccionados a Excel**
- **Sincronizar con Cisco** — importa datos desde Cisco UCCX

#### Columnas de tabla
Nombre, Apellido, Número empleado, Email, Departamento, Posición, Equipo, Estado, Fecha contratación, Es manager.

#### Vistas hijas
- **Importar empleados** — `/employees/import` (permiso `can:import`)
- **Exportar empleados** — `/employees/export` (permiso `can:export`)
- **Ver/Editar empleado** — vista detalle individual (vía controlador)

---

### 9.7. Estructura Organizacional

- **Permiso padre:** `departments.viewAny`

#### 9.7.1. Departamentos

- **Ruta:** `/organization/departments`
- **Nombre de Ruta:** `organization.departments.index`
- **Componente:** `App\Modules\PersonnelModule\Livewire\ListDepartments`

**Qué puedo hacer aquí:**
- Buscar por nombre o descripción
- Filtrar por dirección
- Cambiar registros por página

**Columnas:** Nombre, Descripción, Dirección.

#### 9.7.2. Direcciones

- **Ruta:** `/organization/directorates`
- **Nombre de Ruta:** `organization.directorates.index`
- **Componente:** `App\Modules\PersonnelModule\Livewire\ListDirectorates`

**Qué puedo hacer aquí:**
- Buscar por nombre o descripción
- Filtrar por activo/inactivo
- Cambiar registros por página

**Columnas:** Nombre, Descripción, Activo.

#### 9.7.3. Posiciones

- **Ruta:** `/organization/positions`
- **Nombre de Ruta:** `organization.positions.index`
- **Componente:** `App\Modules\PersonnelModule\Livewire\ListPositions`

**Qué puedo hacer aquí:**
- Buscar por nombre o descripción
- Filtrar por departamento
- Filtrar por activo/inactivo
- Cambiar registros por página

**Columnas:** Nombre, Descripción, Dpto/Dirección, Cant. empleados, Activo.

#### 9.7.4. Equipos

- **Ruta:** `/organization/teams`
- **Nombre de Ruta:** `organization.teams.index`
- **Componente:** `App\Modules\PersonnelModule\Livewire\ListTeams`

**Qué puedo hacer aquí:**
- Buscar por nombre o descripción
- Filtrar por activo/inactivo
- Ordenar por columna
- Cambiar registros por página
- Sincronizar con Cisco — importa equipos y asignaciones desde Cisco Finesse

**Columnas:** Nombre, Descripción, Supervisor, Cant. empleados, Activo.

---

### 9.8. Parámetros Operativos

- **Ruta:** `/schedules/operational-settings`
- **Nombre de Ruta:** `schedules.operational-settings`
- **Componente:** `App\Modules\WfmModule\Livewire\OperationalSettings`
- **Permiso:** `wfm.settings.manage`

#### Qué puedo hacer aquí
- **Ver/editar thresholds** (umbrales de tiempo):
  - `personal_time_threshold`, `stuck_reserved_threshold`, etc.
  - Conversión automática de unidades (segundos/minutos)
- **Ver/editar metas KPI** (porcentajes)
- **Agregar nueva meta KPI** — clave + etiqueta
- **Eliminar meta KPI**
- **Editar metas AHT por cola**
- **Guardar todo** — guardado masivo en una transacción

#### Secciones
- Thresholds (umbrales)
- KPI Goals (metas)
- Queue AHT Goals (metas AHT por cola)

#### Vistas hijas
Ninguna — formulario inline con todas las secciones en una página.

---

### 9.9. Mantenimiento

- **Ruta:** `/admin/system/maintenance`
- **Nombre de Ruta:** `admin.system.maintenance`
- **Componente:** `App\Modules\CoreModule\Livewire\SystemMaintenance`
- **Permiso:** `admin.system`

#### Qué puedo hacer aquí
- **Activar/Desactivar** modo mantenimiento del sistema
- **Mensaje personalizado** para mostrar durante mantenimiento
- **Notificar a todos los usuarios** al activar mantenimiento

#### Vistas hijas
Ninguna — página única con toggle y campo de mensaje.

---

### 9.10. Archivos (Admin)

#### 9.10.1. Gestión de Cuotas

- **Ruta:** `/filesystem/quotas`
- **Nombre de Ruta:** `filesystem.quotas`
- **Componente:** `App\Modules\FilesystemModule\Livewire\QuotaManager`
- **Permiso:** `admin.system`

**Qué puedo hacer aquí:**
- Seleccionar tipo (usuario o rol)
- Buscar usuarios por nombre
- Seleccionar destino de cuota
- Definir límite en MB
- Guardar cuota
- Eliminar cuota

**Columnas:** Tipo destino (usuario/rol), Nombre destino, Límite cuota.

---

## 10. Contact Center (CTI Core)

- **Permiso padre:** `menu.contact_center`
- **Roles con acceso:** wfm, admin, director

### 10.1. Registro de Llamadas

- **Ruta:** `/contact-center/calls`
- **Nombre de Ruta:** `contact-center.calls.index`
- **Componente:** `App\Modules\ConnectModule\Livewire\ListCallRecords`
- **Permiso:** `call_records.update`

#### Qué puedo hacer aquí
- **Buscar** por número telefónico o identificador de ciudadano
- **Filtrar por empleado**
- **Filtrar por estado**
- **Filtrar por rango de fechas**
- **Cambiar registros por página**

#### Columnas de tabla
Teléfono, Ciudadano, Empleado, Cola, Estado, Duración, Inicio IVR.

#### Vistas hijas
Ninguna — visualización y búsqueda de bitácora histórica.

---

### 10.2. Colas de Atención

- **Ruta:** `/contact-center/catalogs/queues`
- **Nombre de Ruta:** `contact-center.admin.queues.index`
- **Componente:** `App\Modules\ConnectModule\Livewire\ListCallQueues`
- **Permiso:** `call_queues.manage`

#### Qué puedo hacer aquí
- **Crear cola** — inline form:
  - `name` (requerido, máx. 255)
  - `description` (nullable, máx. 500)
  - `is_active` (booleano, default true)
- **Editar cola**
- **Eliminar cola**

#### Columnas de tabla
Nombre, Descripción, Activo, Cant. subtipos.

#### Vistas hijas
Ninguna — CRUD completo inline (sin modal).

---

## 11. Comunicaciones (Gestión de Contenido)

- **Permiso padre:** `menu.communications`
- **Roles con acceso:** director, admin, wfm

### 11.1. Noticias

- **Ruta:** `/admin/communications/news`
- **Nombre de Ruta:** `communications.news.index`
- **Componente:** `App\Modules\CommunicationsModule\Livewire\ListNews`
- **Permiso:** `news.viewAny`

#### Qué puedo hacer aquí
- **Buscar noticias** por título o extracto
- **Eliminar noticia**

#### Columnas de tabla
Título, Autor, Publicado el.

#### Vistas hijas
- **Crear/Editar noticia:** formulario separado (no incluido en este listado).

---

### 11.2. Moderación

- **Ruta:** `/admin/communications/moderation`
- **Nombre de Ruta:** `communications.moderation.index`
- **Controlador:** `ContentModerationController`
- **Permiso:** `news.moderate`

#### Qué puedo hacer aquí
- **Aprobar comentarios** de usuarios en noticias
- **Archivar comentarios**

#### Vistas hijas
Pendiente de exploración del controlador.

---

### 11.3. Categorías

- **Ruta:** `/admin/communications/categories`
- **Nombre de Ruta:** `communications.admin.categories.index`
- **Componente:** `App\Modules\CommunicationsModule\Livewire\ManageCategories`
- **Permiso:** `news.viewAny`

#### Qué puedo hacer aquí
- CRUD de categorías para clasificar contenido del blog

---

### 11.4. Etiquetas

- **Ruta:** `/admin/communications/tags`
- **Nombre de Ruta:** `communications.admin.tags.index`
- **Componente:** `App\Modules\CommunicationsModule\Livewire\ManageTags`
- **Permiso:** `news.viewAny`

#### Qué puedo hacer aquí
- CRUD de etiquetas (tags) para contenido

---

### 11.5. Encuestas

- **Ruta:** `/admin/communications/polls`
- **Nombre de Ruta:** `communications.polls.index`
- **Componente:** `App\Modules\CommunicationsModule\Livewire\ListPolls`
- **Permiso:** `news.viewAny`

#### Qué puedo hacer aquí
- **Buscar encuestas** por pregunta
- **Eliminar encuesta**
- **Archivar encuesta** (cerrar)

#### Columnas de tabla
Pregunta, Cant. respuestas, Estado, Creado el.

---

### 11.6. Reconocimientos

- **Ruta:** `/admin/communications/shoutouts`
- **Nombre de Ruta:** `communications.shoutouts.index`
- **Componente:** `App\Modules\CommunicationsModule\Livewire\ListShoutouts`
- **Permiso:** `news.viewAny`

#### Qué puedo hacer aquí
- **Buscar reconocimientos** por mensaje
- **Eliminar reconocimiento**

#### Columnas de tabla
Empleado (destinatario), Mensaje, Estado, Creado el.

---

## Footer del Sidebar

### Documentación (Pública)

- **Ruta:** `/docs`
- **Nombre de Ruta:** `documentation.index`
- **Componente:** `App\Modules\DocumentationModule\Livewire\Public\ArticleIndex`
- **Permiso:** `null` (todo autenticado)

#### Qué puedo hacer aquí
- **Buscar artículos** por título o contenido
- **Filtrar por categoría**
- **Restablecer filtros**
- **Ver artículos publicados** en grid paginado (12 por página)

#### Vistas hijas
- **Ver artículo:** página de detalle del artículo (`/docs/{slug}`)

---

### Base de Conocimiento (Pública)

- **Ruta:** `/knowledge`
- **Nombre de Ruta:** `knowledge.index`
- **Componente:** `App\Modules\KnowledgeModule\Livewire\OperatorView`
- **Middleware de ruta:** `['auth']`
- **Permiso:** `null` (todo autenticado)

#### Qué puedo hacer aquí
- **Seleccionar cola** — filtra artículos por cola, agrupa automáticamente por categoría
- **Seleccionar categoría**
- **Seleccionar etiqueta**
- **Buscar artículos** por título, resumen, contenido o etiquetas
- **Restablecer filtros**
- **Ver artículos** agrupados por categoría

#### Vistas hijas
- **Ver artículo:** página de detalle del artículo (`/knowledge/{slug}`)

---

### Soporte

- **Ruta:** `/helpdesk/my-tickets`
- **Nombre de Ruta:** `helpdesk.my-tickets`
- **Módulo:** `HelpdeskModule`
- **Permiso:** `helpdesk.view`

#### Qué puedo hacer aquí
- Ver mis tickets de soporte
- Crear nuevo ticket
- Dar seguimiento

Pendiente de exploración del componente.

---

### Bandeja Soporte

- **Ruta:** `/helpdesk/manage`
- **Nombre de Ruta:** `helpdesk.manage`
- **Módulo:** `HelpdeskModule`
- **Permiso:** `helpdesk.manage`

#### Qué puedo hacer aquí
- Gestionar todos los tickets de soporte del sistema
- Asignar, responder, cerrar tickets

Pendiente de exploración del componente.

---

## Apéndice: Mecanismo de Filtrado por Roles y Permisos

El sidebar se construye en `MenuHelper::getSidebarItems()` y cada elemento pasa por `MenuHelper::canView()`:

1. **Submenú:** Si tiene hijos, se requiere al menos un hijo visible.
2. **Sin permiso:** Visible para todo autenticado.
3. **Coordinator Override:** `menu.team`, `schedules.view_team`, `wfm.leaves.manage`, `reports.compliance`, `wfm.realtime.view` se conceden si `hasCoordinatorRights()`.
4. **Permiso Spatie:** `$user->can($item['permission'])`.
5. **Roles:** `$user->hasAnyRole()`.
6. **Gate:** `$user->can($ability, $model)`.

### Matriz de Roles vs Secciones

| Sección | OP | SUP | COOR | JEF | WFM | DIR | ADM |
|---|---|---|---|---|---|---|---|
| Dashboard | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Blog | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Descargas | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Mi Espacio | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Equipo | — | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Planificación | — | — | ✅* | — | ✅ | — | ✅ |
| Operación | — | — | ✅ | ✅ | ✅ | ✅ | ✅ |
| Reportes | — | — | — | ✅ | ✅ | ✅ | ✅ |
| Configuración | — | — | — | — | ✅ | — | ✅ |
| Contact Center | — | — | — | — | ✅ | ✅ | ✅ |
| Comunicaciones | — | — | — | — | ✅ | ✅ | ✅ |

> *COOR tiene acceso parcial vía permisos específicos.

### Ubicación de Archivos Clave

| Recurso | Ruta |
|---|---|
| Definición del menú | `app/Helpers/MenuHelper.php` |
| Render del sidebar | `resources/views/layouts/app/sidebar.blade.php` |
| Render de items | `resources/views/layouts/app/partials/menu-item.blade.php` |
| Rutas principales | `routes/web.php` |
| Rutas de módulos | `app/Modules/*/Routes/web.php` |
| Seed de permisos | `database/seeders/RolesAndPermissionsSeeder.php` |
| Config permisos | `config/permission.php` |
| Config módulos | `config/modules.php` |
| Bypass admin | `app/Providers/AppServiceProvider.php` |
