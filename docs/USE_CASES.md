# Catálogo de Módulos, Casos de Uso y Responsabilidades — HorariosWFM

### 1. CoreModule (Identidad y Configuración)
1.  **Autenticación y Seguridad Multi-factor (2FA)**
    *   **Responsabilidad:** Validar credenciales mediante Laravel Fortify, gestionar el flujo de tokens TOTP y asegurar que el usuario esté verificado antes de acceder.
2.  **Gestión de Usuarios (CRUD)**
    *   **Responsabilidad:** Crear y editar la identidad digital de los usuarios, vinculándolos 1:1 con la entidad Empleado.
3.  **Administración de Roles y Permisos (Matriz RBAC)**
    *   **Responsabilidad:** Sincronizar permisos granulares de Spatie y aplicar la jerarquía de roles (1-99) para el acceso a vistas y acciones.
4.  **Configuración Operativa (App Settings)**
    *   **Responsabilidad:** Persistir y proveer parámetros dinámicos (key-value) como metas de KPI y umbrales de alerta en la tabla `app_settings`.
5.  **Gestión de Sesiones y Auditoría de Acceso**
    *   **Responsabilidad:** Monitorear sesiones activas y registrar el último inicio de sesión para control de seguridad.

### 2. OrganizationModule (Estructura Institucional)
1.  **Mantenimiento de Direcciones y Departamentos**
    *   **Responsabilidad:** Definir la estructura macro de la institución asegurando que no existan eliminaciones si hay dependencias activas.
2.  **Gestión de Puestos y Cargos (Positions)**
    *   **Responsabilidad:** Administrar el catálogo de puestos con sus respectivos códigos, descripciones y rangos salariales base.
3.  **Visualización del Organigrama Institucional**
    *   **Responsabilidad:** Proveer la estructura jerárquica para filtros dinámicos en reportes y asignaciones de personal.

### 3. GeoModule (Catálogo Territorial)
1.  **Gestión de División Política (Provincias/Distritos/Corregimientos)**
    *   **Responsabilidad:** Mantener la integridad de la base de datos geográfica de Panamá para la ubicación domiciliaria de los empleados.
2.  **Suministro de Datos Cascada para Formularios**
    *   **Responsabilidad:** Proveer endpoints o componentes Livewire para la selección jerárquica de ubicación en el registro de empleados.

### 4. PersonnelModule (Gestión de Talento Humano)
1.  **Gestión de Ficha de Empleado (Perfil Maestro)**
    *   **Responsabilidad:** Centralizar datos personales, contacto, documentos y metadata flexible en la tabla `employees`.
2.  **Importación Masiva de Personal**
    *   **Responsabilidad:** Procesar archivos Excel/CSV mediante `EmployeeImportBatch`, validando datos y creando usuarios automáticamente en segundo plano.
3.  **Administración de Equipos (Teams)**
    *   **Responsabilidad:** Definir equipos de trabajo, asignar supervisores y mantener la sincronización con los nombres de equipo en Cisco Finesse.
4.  **Gestión de Historial Laboral y Puestos**
    *   **Responsabilidad:** Registrar cambios de cargo y estatus de contratación para mantener la trazabilidad de la carrera del empleado.
5.  **Control de Datos Sensibles (Salud y Dependientes)**
    *   **Responsabilidad:** Almacenar de forma segura información sobre enfermedades, discapacidades y cargas familiares para fines de RRHH.
6.  **Seguimiento de Carrera y Promociones (`employee_positions`)**
    *   **Responsabilidad:** Mantener un histórico de todos los cargos que ha ocupado un empleado, permitiendo auditorías de crecimiento interno y cambios salariales en el tiempo.
7.  **Gestión de Estatus Laborales Jerárquicos (`employment_statuses`)**
    *   **Responsabilidad:** Permitir estatus anidados (ej: "Incapacidad" -> "Incapacidad por Riesgo Profesional") mediante la relación `parent_id`.

### 5. WfmModule (Planificación de Fuerza Laboral)
1.  **Definición de Turnos Base (Schedules)**
    *   **Responsabilidad:** Crear plantillas de horarios que incluyan horas de entrada, salida, almuerzo y descansos (pagados o no).
2.  **Planificación Semanal (Weekly Planning)**
    *   **Responsabilidad:** Orquestar la creación de semanas de trabajo, permitiendo estados de borrador, publicación y archivado.
3.  **Asignación Masiva de Horarios**
    *   **Responsabilidad:** Vincular empleados a turnos específicos por día, permitiendo personalización intradía (horas de break específicas).
4.  **Gestión de Excepciones de Horario**
    *   **Responsabilidad:** Registrar inasistencias, permisos pagados, incapacidades o tardanzas justificadas mediante `schedule_exceptions`.
5.  **Gestión de Actividades Intradía**
    *   **Responsabilidad:** Asignar actividades fuera de línea (capacitaciones, reuniones) validando la capacidad máxima (`max_slots`) por equipo y periodo.
6.  **Procesamiento de Intercambio de Turnos (Shift Swaps)**
    *   **Responsabilidad:** Validar solicitudes entre pares, capturar snapshots de los turnos originales y ejecutar el intercambio en la base de datos tras la aprobación.
7.  **Gestión de Solicitudes de Permiso (Leave Requests)**
    *   **Responsabilidad:** Controlar el saldo de minutos disponibles (permisos trimestrales) y tramitar la solicitud mediante el flujo de aprobación.
8.  **Versioning de Planificación por Equipo (`weekly_team_assignments`)**
    *   **Responsabilidad:** Definir una plantilla base de horarios para un equipo completo antes de individualizarla, optimizando la creación masiva de turnos.
9.  **Snapshot de Trazabilidad en Swaps (`requester/recipient_assignment_snapshot`)**
    *   **Responsabilidad:** Guardar el estado exacto del turno en formato JSON antes de que ocurra el cambio, para permitir reversiones o auditorías de "qué tenía el usuario originalmente".
10.  **Gestión de Definiciones de Actividad Programada (`scheduled_activity_definitions`)**
    *   **Responsabilidad:** Crear un catálogo de actividades recurrentes (ej: "Clínica de Ventas", "Feedback Semanal") con duraciones e instructores por defecto.

### 6. ConnectModule (Integración Cisco CTI)
1.  **Sincronización de Estados en Tiempo Real (Telemetry)**
    *   **Responsabilidad:** Consultar la API de Cisco Finesse cada 5 segundos y actualizar la tabla `UNLOGGED` para monitoreo en vivo.
2.  **Extracción de Registros de Llamada (ETL Histórico)**
    *   **Responsabilidad:** Importar CDRs desde CUIC hacia `call_records`, mapeando identificadores de Cisco con empleados internos.
3.  **Registro Manual de Llamadas y Ciudadanos**
    *   **Responsabilidad:** Proveer formularios para que el operador vincule una llamada activa con la cédula del ciudadano y el subtipo de caso.
4.  **Monitoreo de Colas (CSQ Stats)**
    *   **Responsabilidad:** Capturar métricas de nivel de servicio y llamadas en espera directamente desde la infraestructura de telefonía.
5.  **Capa de Resiliencia (Circuit Breaker)**
    *   **Responsabilidad:** Detectar caídas en los servicios de Cisco y evitar que el sistema se bloquee, reintentando la sincronización automáticamente.
6.  **Gestión de Interacciones por Chat (`chat_records`)**
    *   **Responsabilidad:** Sincronizar conversaciones de chat, incluyendo duración, tiempo de aceptación, autor y calificación de la fuente (Omnicanalidad).
7.  **Análisis de Performance Detallado por Llamada (`agent_call_performance`)**
    *   **Responsabilidad:** Desglosar cada llamada en `talk_time`, `hold_time` y `work_time` para un análisis forense de la productividad del agente.

### 7. OperationsModule (KPIs y Dashboards)
1.  **Cálculo de Adherencia y Cumplimiento**
    *   **Responsabilidad:** Comparar el estado real (Connect) vs el planificado (WFM) para determinar desviaciones en tiempo real.
2.  **Dashboard de Monitoreo Ejecutivo y Operativo**
    *   **Responsabilidad:** Renderizar widgets dinámicos con KPIs de volumen, AHT, Nivel de Servicio y disponibilidad.
3.  **Generación de Scorecards de Desempeño**
    *   **Responsabilidad:** Consolidar métricas diarias de llamadas, adherencia y calidad en el modelo `agent_daily_metrics`.
4.  **Visualización de Línea de Tiempo del Agente**
    *   **Responsabilidad:** Generar una vista gráfica (Gantt) que compare las actividades planificadas vs los estados reales de Cisco.
5.  **Reporte Diario de Operaciones**
    *   **Responsabilidad:** Compilar un resumen de cierre de día con todas las incidencias de asistencia y cumplimiento de metas.
6.  **Asignaciones Temporales de Liderazgo (`temporal_assignments`)**
    *   **Responsabilidad:** Gestionar empleados que cambian de supervisor o equipo por un periodo corto (ej: por un Swap o cobertura de vacaciones), afectando la visibilidad del supervisor temporal.
7.  **Tipificación de Incidentes de Asistencia (`incident_types`)**
    *   **Responsabilidad:** Diferenciar entre incidentes que afectan la disponibilidad (ej: fallo técnico) de los que requieren justificación administrativa (ej: tardanza).

### 8. CommunicationsModule (Engagement Interno)
1.  **Muro de Noticias y Anuncios Corporativos**
    *   **Responsabilidad:** Gestionar contenido con programación de publicación y fechas de expiración/archivado automático.
2.  **Gestión de Encuestas (Polls)**
    *   **Responsabilidad:** Administrar preguntas y opciones, capturando respuestas únicas por empleado y generando resultados en tiempo real.
3.  **Reconocimientos entre Pares (Shoutouts)**
    *   **Responsabilidad:** Permitir el envío de agradecimientos públicos sujetos a moderación administrativa.
4.  **Interacciones Sociales (Comments/Reactions)**
    *   **Responsabilidad:** Gestionar comentarios e hilos de conversación, además de reacciones polimórficas en noticias y reconocimientos.
5.  **Menciones y Notificaciones Push/Broadcast**
    *   **Responsabilidad:** Detectar menciones `@usuario` y disparar eventos de WebSockets (Reverb) para alertas instantáneas.

### 9. QualityModule (Evaluación de Servicio)
1.  **Administración de Rúbricas y Criterios**
    *   **Responsabilidad:** Gestionar catálogos de criterios de evaluación con versionado histórico para no afectar evaluaciones pasadas.
2.  **Ejecución de Evaluaciones de Llamada**
    *   **Responsabilidad:** Proveer la interfaz de calificación donde se puntúan criterios y se levantan "Red Flags" (errores críticos).
3.  **Calibración QA**
    *   **Responsabilidad:** Permitir a los supervisores re-evaluar llamadas para asegurar la objetividad, guardando el log de cambios de puntaje.
4.  **Gestión de Feedback al Operador**
    *   **Responsabilidad:** Formalizar las recomendaciones post-evaluación y permitir al operador visualizar sus resultados detallados.
5.  **Control de Versiones de Criterios (`quality_criteria_versions`)**
    *   **Responsabilidad:** Garantizar que si una rúbrica cambia hoy, las evaluaciones hechas el mes pasado mantengan el texto y puntaje que tenían en ese momento.
6.  **Penalizaciones por Errores Críticos (`quality_red_flag_criteria`)**
    *   **Responsabilidad:** Definir criterios de "Pérdida Total" o penalizaciones severas que restan puntos directamente del score global por fallas graves (ej: no validar identidad).

### 10. AuditModule (Trazabilidad)
1.  **Registro de Auditoría Automatizado**
    *   **Responsabilidad:** Escuchar eventos de Eloquent para capturar cambios en JSON (`before`/`after`) de todas las entidades críticas.
2.  **Consulta y Exportación de Logs**
    *   **Responsabilidad:** Proveer una interfaz de búsqueda para auditores y generar reportes inmutables de actividad sospechosa o administrativa.

### 11. WorkflowsModule (Motor de Aprobaciones)
1.  **Orquestación de Aprobaciones Multinivel**
    *   **Responsabilidad:** Controlar la máquina de estados de solicitudes, asegurando que pasen por el orden jerárquico configurado (`step_order`).
2.  **Delegación de Firmas/Aprobaciones**
    *   **Responsabilidad:** Permitir la asignación temporal de un aprobador sustituto en caso de vacaciones o ausencia del jefe inmediato.

### 12. HelpdeskModule (Soporte Técnico Interno)
1.  **Gestión de Tickets de Incidencias**
    *   **Responsabilidad:** Permitir a los operadores reportar fallas técnicas o dudas, asignando niveles de prioridad y categorías.
2.  **Seguimiento de Conversación en Soporte**
    *   **Responsabilidad:** Mantener un hilo de comentarios entre el solicitante y el agente de soporte, incluyendo notas internas ocultas.

### 13. KnowledgeModule (Base de Conocimiento)
1.  **Administración de Artículos de Ayuda**
    *   **Responsabilidad:** Crear guías rápidas y artículos técnicos vinculados a colas de atención específicas (`queues`).
2.  **Búsqueda Rápida para Operadores**
    *   **Responsabilidad:** Proveer un motor de búsqueda eficiente para que el operador resuelva dudas del ciudadano durante la llamada.

### 14. DocumentationModule (Wiki del Sistema)
1.  **Documentación de Usuario y Administrador**
    *   **Responsabilidad:** Alojar los manuales de uso del sistema **HorariosWFM**, facilitando el onboarding de nuevos colaboradores.

### 15. FilesystemModule (Gestión de Archivos)
1.  **Explorador de Archivos y Carpetas**
    *   **Responsabilidad:** Proveer una interfaz tipo "Drive" para subir, organizar y descargar documentos institucionales o personales.
2.  **Compartición Segura de Documentos**
    *   **Responsabilidad:** Gestionar permisos de acceso (lectura/escritura) entre usuarios y controlar las cuotas de almacenamiento por empleado.
3.  **Gestión de Niveles de Acceso en Compartición (`file_shares`)**
    *   **Responsabilidad:** Diferenciar permisos entre `view`, `edit` y `admin` al compartir documentos o carpetas entre empleados.
4.  **Control de Cuotas por Entidad (`storage_quotas`)**
    *   **Responsabilidad:** Limitar el espacio de disco de forma polimórfica (ej: un límite para un Usuario y otro límite mayor para un Equipo/Departamento).