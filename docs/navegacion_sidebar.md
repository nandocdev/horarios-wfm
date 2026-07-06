# Documentación de Navegación del Sidebar

Este documento detalla cada una de las entradas del menú de navegación del sidebar del sistema **Antigravity**, especificando su nombre, ruta física en el sistema, utilidad funcional y detalles técnicos del monolito modular.

---

## 1. Dashboard (Panel Principal)
*   **Ruta:** `/dashboard`
*   **Nombre de Ruta:** `dashboard`
*   **Módulo:** `OperationsModule`
*   **Componente Técnico:** `App\Modules\OperationsModule\Livewire\Dashboard`
*   **Utilidad Funcional:** Panel gerencial general. Presenta widgets de KPIs operativos consolidados, volumen de llamadas, adherencia promedio y notificaciones recientes al iniciar sesión.
*   **Detalles Técnicos:** Carga widgets asíncronos en Livewire de forma diferida (Lazy Loading) para optimizar la velocidad de respuesta inicial.

---

## 2. Blog (Comunicación Interna)
*   **Ruta:** `/`
*   **Nombre de Ruta:** `home`
*   **Módulo:** `CommunicationsModule`
*   **Componente Técnico:** `App\Modules\CommunicationsModule\Livewire\Home`
*   **Utilidad Funcional:** Portal de comunicación donde los agentes y personal administrativo visualizan noticias, reconocimientos, publicaciones internas y encuestas activas.
*   **Detalles Técnicos:** Renderiza componentes de FluxUI e integra interactividad en tiempo real (comentarios y reacciones).

---

## 3. Descargas
*   **Ruta:** `/downloads` (Pendiente de implementación)
*   **Módulo:** `CoreModule` / `Shared`
*   **Utilidad Funcional:** Repositorio centralizado para descargar manuales de usuario, instaladores de software de CTI, VPNs o guías corporativas.

---

## 4. Mi Espacio (Módulo Operador)
Sección orientada al agente de call center para su autogestión diaria.

*   **Mi Dia:**
    *   **Ruta:** `/schedules/my-day`
    *   **Nombre de Ruta:** `schedules.my-day`
    *   **Módulo:** `WfmModule`
    *   **Componente Técnico:** `App\Modules\WfmModule\Livewire\MyDay`
    *   **Utilidad Funcional:** Agenda visual cronológica para el día de hoy del operador, mostrando descansos (breaks), horas de almuerzo y actividades planificadas.
*   **Mi Horario:**
    *   **Ruta:** `/schedules/my-schedule`
    *   **Nombre de Ruta:** `schedules.my-schedule`
    *   **Módulo:** `WfmModule`
    *   **Componente Técnico:** `App\Modules\WfmModule\Livewire\MySchedule`
    *   **Utilidad Funcional:** Calendario mensual completo del operador con turnos planificados publicados.
*   **Mis Estadísticas:**
    *   **Ruta:** `/schedules/my-metrics`
    *   **Nombre de Ruta:** `schedules.my-metrics`
    *   **Módulo:** `WfmModule`
    *   **Componente Técnico:** `App\Modules\WfmModule\Livewire\MyMetrics`
    *   **Utilidad Funcional:** Dashboard individual del agente que detalla su adherencia acumulada, AHT histórico, tardanzas y nivel de servicio de llamadas atendidas.
*   **Historial de Cambio:**
    *   **Ruta:** `/schedules/swap-history`
    *   **Nombre de Ruta:** `schedules.swap-history`
    *   **Módulo:** `WfmModule`
    *   **Componente Técnico:** `App\Modules\WfmModule\Livewire\SwapRequestHistory`
    *   **Utilidad Funcional:** Bitácora personal del agente con el estado de sus solicitudes de intercambio de turnos (`Shift Swaps`) enviadas y recibidas.
*   **Historial de Permisos:**
    *   **Ruta:** `/schedules/leave-history`
    *   **Nombre de Ruta:** `schedules.leave-history`
    *   **Módulo:** `WfmModule`
    *   **Componente Técnico:** `App\Modules\WfmModule\Livewire\LeaveRequestHistory`
    *   **Utilidad Funcional:** Historial de solicitudes de permisos o vacaciones (`Leaves`) emitidas por el operador.
*   **Archivos:**
    *   **Ruta:** `/my-files` (Pendiente de implementación)
    *   **Módulo:** `PersonnelModule`
    *   **Utilidad Funcional:** Subida de justificaciones médicas, contratos u hojas de vida personales firmadas por el agente.

---

## 5. Equipo (Gestión de Supervisores)
Orientado a los directores de equipo o coordinadores.

*   **Desempeño:**
    *   **Ruta:** `/operations/team-performance`
    *   **Nombre de Ruta:** `operations.team-performance`
    *   **Módulo:** `OperationsModule`
    *   **Componente Técnico:** `App\Modules\OperationsModule\Livewire\TeamPerformanceSummary`
    *   **Utilidad Funcional:** Reporte consolidado del supervisor con el desempeño general y KPIs de sus agentes directos en la campaña.
*   **Analítica Avanzada:**
    *   **Ruta:** `/operations/advanced-analytics`
    *   **Nombre de Ruta:** `operations.advanced-analytics`
    *   **Módulo:** `OperationsModule`
    *   **Componente Técnico:** `App\Modules\OperationsModule\Livewire\AdvancedProductivityDashboard`
    *   **Utilidad Funcional:** Filtros de productividad, cruces complejos de adherencia y reportes gerenciales multidimensionales.
*   **Vista de Equipo:**
    *   **Ruta:** `/schedules/my-team`
    *   **Nombre de Ruta:** `schedules.my-team`
    *   **Módulo:** `WfmModule`
    *   **Componente Técnico:** `App\Modules\WfmModule\Livewire\MyTeam`
    *   **Utilidad Funcional:** Grid con los turnos de todos los miembros del equipo para facilitar la planeación visual interna.
*   **Solicitudes:**
    *   **Ruta:** `/schedules/swap-request`
    *   **Nombre de Ruta:** `schedules.swap-request`
    *   **Módulo:** `WfmModule`
    *   **Componente Técnico:** `App\Modules\WfmModule\Livewire\RequestShiftSwap`
    *   **Utilidad Funcional:** Formulario para que el operador (o supervisor en su nombre) solicite formalmente un cambio de turnos.
*   **Aprobaciones:**
    *   **Ruta:** `/schedules/manager-approvals`
    *   **Nombre de Ruta:** `schedules.manager-approvals`
    *   **Módulo:** `WfmModule`
    *   **Componente Técnico:** `App\Modules\WfmModule\Livewire\ManagerApprovals`
    *   **Utilidad Funcional:** Panel del supervisor (firmas L1/L2) para aprobar o rechazar de manera ágil los permisos y cambios de su personal a cargo.

---

## 6. Planificación (WFM Engine)
Sección restringida a analistas de Workforce Management (WFM) y SuperAdmins.

*   **Planificación Semanal:**
    *   **Ruta:** `/schedules/planning`
    *   **Nombre de Ruta:** `schedules.planning`
    *   **Módulo:** `WfmModule`
    *   **Componente Técnico:** `App\Modules\WfmModule\Livewire\WeeklyPlanning`
    *   **Utilidad Funcional:** Interfaz para crear mallas horarias, importar asignaciones mediante CSV y publicar horarios masivos para los equipos.
*   **Actividades Intradía:**
    *   **Ruta:** `/schedules/intraday-activities/manage`
    *   **Nombre de Ruta:** `schedules.intraday-activities.manage`
    *   **Módulo:** `WfmModule`
    *   **Componente Técnico:** `App\Modules\WfmModule\Livewire\ManageIntradayActivities`
    *   **Utilidad Funcional:** Editor de micro-actividades (breaks, baños, almuerzos, capacitaciones) sobre la línea de tiempo diaria de los agentes.
*   **Definiciones de Actividades:**
    *   **Ruta:** `/schedules/scheduled-activities`
    *   **Nombre de Ruta:** `schedules.scheduled-activities`
    *   **Módulo:** `WfmModule`
    *   **Componente Técnico:** `App\Modules\WfmModule\Livewire\ManageScheduledActivities`
    *   **Utilidad Funcional:** Maestro para definir tipos de micro-actividades programables y sus reglas pre-cargadas de duración y adherencia.
*   **Excepciones Masivas:**
    *   **Ruta:** `/schedules/exceptions`
    *   **Nombre de Ruta:** `schedules.exceptions`
    *   **Módulo:** `WfmModule`
    *   **Componente Técnico:** `App\Modules\WfmModule\Livewire\ManageScheduleExceptions`
    *   **Utilidad Funcional:** Asignación masiva de excepciones horarias (capacitaciones corporativas, vacaciones colectivas) sobre la malla de turnos.

---

## 7. Operación (Supervisión Intra-Día)
*   **Realtime:**
    *   **Ruta:** `/operations/realtime`
    *   **Nombre de Ruta:** `operations.realtime`
    *   **Módulo:** `OperationsModule`
    *   **Componente Técnico:** `App\Modules\OperationsModule\Livewire\RealtimeMonitoring`
    *   **Utilidad Funcional:** Tablero crítico con polling en caliente o WebSockets. Muestra a los agentes conectados en Cisco Finesse, sus estados reales ( Ready, Not Ready, Break) y alertas de desadherencia.
*   **Disponibilidad:**
    *   **Ruta:** `/operations/availability`
    *   **Nombre de Ruta:** `operations.availability`
    *   **Módulo:** `OperationsModule`
    *   **Componente Técnico:** `App\Modules\OperationsModule\Livewire\IntradayAvailability`
    *   **Utilidad Funcional:** Gráfico de cobertura que superpone la oferta de personal programado contra la demanda requerida por horas del día.

---

## 8. Reportes (Centro Analítico)
*   **Centro de Reportes:**
    *   **Ruta:** `/operations/reports`
    *   **Nombre de Ruta:** `operations.reports`
    *   **Módulo:** `OperationsModule`
    *   **Componente Técnico:** `App\Modules\OperationsModule\Livewire\ReportingFrameworkIndex`
    *   **Utilidad Funcional:** Hub para programar, generar y descargar reportes históricos crudos en formato Excel/CSV.
*   **Adherencia y Cobertura:**
    *   **Ruta:** Administrada dentro del Reporting Hub mediante filtros dinámicos del módulo de operaciones.
*   **Productividad Operativa:**
    *   **Ruta:** `/operations/advanced-analytics` (Direcciona al `AdvancedProductivityDashboard`).
*   **Performance por Cola:**
    *   **Ruta:** `/operations/queue-performance`
    *   **Nombre de Ruta:** `operations.queue-performance`
    *   **Módulo:** `OperationsModule`
    *   **Componente Técnico:** `App\Modules\OperationsModule\Livewire\QueuePerformanceReport`
    *   **Utilidad Funcional:** Desglose del nivel de servicio (SLA) y tiempos medios de atención (AHT) segmentados por colas de CTI.
*   **Gestión de Solicitudes:**
    *   **Ruta:** `/schedules/reports/requests`
    *   **Nombre de Ruta:** `schedules.request-summary`
    *   **Módulo:** `WfmModule`
    *   **Componente Técnico:** `App\Modules\WfmModule\Livewire\RequestSummary`
    *   **Utilidad Funcional:** Reporte consolidado de todas las aprobaciones, rechazos y solicitudes pendientes procesadas en la plataforma.
*   **Executive Dashboard:**
    *   **Ruta:** `/operations/dashboard` (Configurado con perfil ejecutivo/gerencial sin widgets operativos pesados).
*   **Inventario de Staffing:**
    *   **Ruta:** `/personnel/reports/staffing`
    *   **Nombre de Ruta:** `personnel.staffing-summary`
    *   **Módulo:** `PersonnelModule`
    *   **Componente Técnico:** `App\Modules\PersonnelModule\Livewire\StaffingSummary`
    *   **Utilidad Funcional:** Conteo consolidado del personal contratado y activo vs inactivo por unidades de negocio y gerencias.

---

## 9. Configuración (Catálogos y Administración del Sistema)
*   **Documentación (Gestor):**
    *   **Ruta:** `/admin/documentation/articles`
    *   **Nombre de Ruta:** `documentation.admin.articles`
    *   **Módulo:** `DocumentationModule`
    *   **Componente Técnico:** `App\Modules\DocumentationModule\Livewire\Admin\ManageArticles`
    *   **Utilidad Funcional:** CRUD para la administración interna de los manuales del sistema.
*   **Base de Conocimiento (Gestor):**
    *   **Ruta:** `/admin/knowledge`
    *   **Nombre de Ruta:** `knowledge.admin`
    *   **Módulo:** `KnowledgeModule`
    *   **Componente Técnico:** `App\Modules\KnowledgeModule\Livewire\ManageArticles`
    *   **Utilidad Funcional:** CRUD para gestionar artículos de procedimientos de atención.
*   **Usuarios:**
    *   **Ruta:** `/admin/users`
    *   **Nombre de Ruta:** `users.index`
    *   **Módulo:** `CoreModule`
    *   **Componente Técnico:** `App\Modules\CoreModule\Livewire\ListUsers`
    *   **Utilidad Funcional:** Gestión de usuarios del sistema (CRUD de credenciales y asignación de roles).
*   **Roles y Permisos:**
    *   **Ruta:** `/admin/roles`
    *   **Nombre de Ruta:** `roles.index`
    *   **Módulo:** `CoreModule`
    *   **Componente Técnico:** `App\Modules\CoreModule\Livewire\ListRoles`
    *   **Utilidad Funcional:** Configuración de la matriz Spatie de roles y permisos.
*   **Catálogo WFM:**
    *   **Rutas Asociadas:**
        *   **Turnos base:** `/schedules/shifts` -> `ManageSchedules`
        *   **Tipos de Actividad:** `/schedules/activity-types` -> `ManageActivityTypes`
        *   **Razones de Ausencia:** `/schedules/absence-reasons` -> `ManageAbsenceReasons`
        *   **Estados CTI:** `/schedules/agent-states` -> `ManageAgentStates`
    *   **Utilidad Funcional:** Catálogos base requeridos para configurar turnos y adherencia.
*   **Empleados:**
    *   **Ruta:** `/employees`
    *   **Nombre de Ruta:** `employees.index`
    *   **Módulo:** `PersonnelModule`
    *   **Controlador:** `App\Modules\PersonnelModule\Http\Controllers\EmployeeController`
    *   **Utilidad Funcional:** CRUD del expediente laboral de los agentes de la compañía (nombres, username, supervisor L1/L2, fecha de alta).
*   **Estructura:**
    *   **Ruta:** `/organization/teams`, `/organization/departments`, `/organization/positions`
    *   **Módulo:** `PersonnelModule`
    *   **Utilidad Funcional:** CRUD de las direcciones, departamentos, puestos y equipos jerárquicos de la empresa.
*   **Parámetros Operativos:**
    *   **Ruta:** `/schedules/operational-settings`
    *   **Nombre de Ruta:** `schedules.operational-settings`
    *   **Módulo:** `WfmModule`
    *   **Componente Técnico:** `App\Modules\WfmModule\Livewire\OperationalSettings`
    *   **Utilidad Funcional:** Configuración de tolerancias de retraso, tiempos máximos y configuraciones del motor Erlang.
*   **Mantenimiento:**
    *   **Ruta:** `/admin/system/maintenance`
    *   **Módulo:** `CoreModule`
    *   **Componente Técnico:** `App\Modules\CoreModule\Livewire\SystemMaintenance`
    *   **Utilidad Funcional:** Verificación de colas de trabajos pendientes (Jobs), estado de caché de Redis y logs del sistema.
*   **Archivos:**
    *   **Ruta:** `/admin/files` (Pendiente de implementación).

---

## 10. Contact Center (CTI Core)
*   **Registro de Llamadas:**
    *   **Ruta:** `/contact-center/calls`
    *   **Módulo:** `ConnectModule`
    *   **Componente Técnico:** `App\Modules\ConnectModule\Livewire\ListCallRecords`
    *   **Utilidad Funcional:** Bitácora histórica con el detalle de las llamadas telefónicas ingresadas, duración, agente que atendió y tipificación.
*   **Colas de Atención:**
    *   **Ruta:** `/contact-center/catalogs/queues`
    *   **Módulo:** `ConnectModule`
    *   **Componente Técnico:** `App\Modules\ConnectModule\Livewire\ListCallQueues`
    *   **Utilidad Funcional:** Maestro de configuración de las colas de llamadas de Cisco Finesse asociadas al enrutamiento del ACD.

---

## 11. Comunicaciones (Gestión de Contenido)
*   **Noticias:**
    *   **Ruta:** `/admin/communications/news`
    *   **Módulo:** `CommunicationsModule`
    *   **Componente Técnico:** `App\Modules\CommunicationsModule\Livewire\ListNews`
    *   **Utilidad Funcional:** Gestor para redactar y publicar anuncios y boletines informativos.
*   **Moderación:**
    *   **Ruta:** `/admin/communications/moderation`
    *   **Controlador:** `ContentModerationController`
    *   **Utilidad Funcional:** Bandeja para aprobar o archivar comentarios de usuarios en las noticias.
*   **Categorías / Etiquetas:**
    *   **Ruta:** `/admin/communications/categories` / `tags`
    *   **Utilidad Funcional:** Taxonomía de contenido para organizar el blog interno.
*   **Encuestas:**
    *   **Ruta:** `/admin/communications/polls`
    *   **Componente:** `ListPolls`
    *   **Utilidad Funcional:** Creación de encuestas rápidas de clima laboral.
*   **Reconocimientos:**
    *   **Ruta:** `/admin/communications/shoutouts`
    *   **Componente:** `ListShoutouts`
    *   **Utilidad Funcional:** Envío de reconocimientos públicos (Shoutouts) entre compañeros de equipo.

---

## 12. Documentación (Pública)
*   **Ruta:** `/docs`
*   **Nombre de Ruta:** `documentation.index`
*   **Módulo:** `DocumentationModule`
*   **Componente Técnico:** `App\Modules\DocumentationModule\Livewire\Public\ArticleIndex`
*   **Utilidad Funcional:** Portal de manuales del sistema accesible para todos los agentes de la operación.

---

## 13. Base de Conocimiento (Pública)
*   **Ruta:** `/knowledge`
*   **Nombre de Ruta:** `knowledge.index`
*   **Módulo:** `KnowledgeModule`
*   **Componente Técnico:** `App\Modules\KnowledgeModule\Livewire\OperatorView`
*   **Utilidad Funcional:** Biblioteca técnica para que los agentes consulten procedimientos y respuestas ante llamadas de clientes.

---

## 14. Soporte y Bandeja de Soporte
*   **Ruta:** `/support`
*   **Módulo:** `SupportModule`
*   **Utilidad Funcional:** Apertura y seguimiento de tickets de ayuda técnica por parte de los usuarios.
*   **Detalles Técnicos:** Estructura inicial a nivel de BD; lógica e interfaces visuales pendientes de implementación.
