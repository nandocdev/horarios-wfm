/**
 * Definición de pasos para los tours interactivos con Driver.js
 */

export const tourDefinitions = {
    // 🗓 Planificación Semanal (matriz de horarios por equipo / empleado)
    'wfm-planning': {
        title: 'Planificación Semanal',
        version: 1,
        steps: [
            {
                element: '[data-tour="planning-header"]',
                popover: {
                    title: 'Planificación de Horarios',
                    description: 'Estás editando la malla de horarios de la semana seleccionada. Usa la flecha para volver al nivel anterior.',
                    side: 'bottom',
                    align: 'start',
                },
            },
            {
                element: '[data-tour="planning-bulk-assign"]',
                popover: {
                    title: 'Asignación Masiva',
                    description: 'Aplica un turno base a todos los miembros del equipo para la semana completa en una sola operación.',
                    side: 'bottom',
                    align: 'start',
                },
            },
            {
                element: '[data-tour="planning-grid"]',
                popover: {
                    title: 'Matriz de Horarios',
                    description: 'Visualiza y edita la asignación diaria de turnos, almuerzos y descansos por empleado o por día.',
                    side: 'top',
                    align: 'center',
                },
            },
            {
                element: '[data-tour="planning-actions"]',
                popover: {
                    title: 'Guardado',
                    description: 'Guarda los cambios del horario. La publicación de la semana se gestiona desde el listado de semanas.',
                    side: 'top',
                    align: 'start',
                },
            },
        ],
    },

    // 🗓 Mi Horario / Mi Trabajo
    'my-schedule': {
        title: 'Mi Horario y Jornada',
        version: 1,
        steps: [
            {
                element: '[data-tour="my-schedule-header"]',
                popover: {
                    title: 'Tu Horario Personal',
                    description: 'Consulta tus turnos asignados, pausas programadas y estado de solicitudes.',
                    side: 'bottom',
                    align: 'start',
                },
            },
            {
                element: '[data-tour="my-schedule-current-day"]',
                popover: {
                    title: 'Jornada del Día',
                    description: 'Revisa tu horario de entrada, salida y actividades programadas para hoy.',
                    side: 'bottom',
                    align: 'center',
                },
            },
            {
                element: '[data-tour="my-schedule-actions"]',
                popover: {
                    title: 'Solicitudes y Permisos',
                    description: 'Genera solicitudes de permiso o cambios de turno con compañeros de equipo.',
                    side: 'left',
                    align: 'start',
                },
            },
        ],
    },

    // 🔄 Operaciones / Torre de Control
    'operations.control-tower': {
        title: 'Torre de Control Operativa',
        version: 1,
        steps: [
            {
                element: '[data-tour="control-tower-header"]',
                popover: {
                    title: 'Torre de Control',
                    description: 'Vista centralizada de comando para el monitoreo de operaciones, alertas y rendimiento en tiempo real.',
                    side: 'bottom',
                    align: 'start',
                },
            },
            {
                element: '[data-tour="control-tower-kpis"]',
                popover: {
                    title: 'Métricas Clave (Hero KPIs)',
                    description: 'Monitorea el Nivel de Servicio (SLA), AHT, adherencia global y volumen de interacciones.',
                    side: 'bottom',
                    align: 'start',
                },
            },
            {
                element: '[data-tour="control-tower-queues"]',
                popover: {
                    title: 'Equipos y Colas',
                    description: 'Compara el desempeño de los equipos de trabajo y el estado del tráfico por cola de atención.',
                    side: 'top',
                    align: 'center',
                },
            },
            {
                element: '[data-tour="control-tower-ops-status"]',
                popover: {
                    title: 'Estado Operacional & Alertas',
                    description: 'Visualiza la distribución de agentes y recibe notificaciones de incidentes en tiempo real.',
                    side: 'top',
                    align: 'start',
                },
            },
            {
                element: '[data-tour="control-tower-charts"]',
                popover: {
                    title: 'Ocupación y Tiempos de Espera',
                    description: 'Analiza curvas de ocupación por intervalo y velocidad promedio de respuesta (ASA).',
                    side: 'top',
                    align: 'center',
                },
            },
            {
                element: '[data-tour="control-tower-forecast"]',
                popover: {
                    title: 'Forecast vs Real',
                    description: 'Contrasta la curva de llamadas reales frente al pronóstico modelado.',
                    side: 'top',
                    align: 'start',
                },
            },
        ],
    },

    // 📡 Operaciones / Monitoreo en Tiempo Real
    'operations.realtime': {
        title: 'Monitoreo en Tiempo Real',
        version: 1,
        steps: [
            {
                element: '[data-tour="realtime-header"]',
                popover: {
                    title: 'Estado de la Operación',
                    description: 'Resumen en vivo de agentes (total, listos, hablando, auxiliares, ausentes), adherencia y estado de la sincronización.',
                    side: 'bottom',
                    align: 'start',
                },
            },
            {
                element: '[data-tour="realtime-filters"]',
                popover: {
                    title: 'Filtros',
                    description: 'Busca por nombre, restringe a activos y filtra por equipo, cargo, cola, estado, motivo o turno esperado.',
                    side: 'bottom',
                    align: 'start',
                },
            },
            {
                element: '[data-tour="realtime-grid"]',
                popover: {
                    title: 'Agentes en Vivo',
                    description: 'Estado real y programado de cada agente, alertas operativas y duración. Haz clic en el ojo para ver el detalle del agente.',
                    side: 'top',
                    align: 'center',
                },
            },
        ],
    },

    // 📊 Operaciones / Disponibilidad Intradía
    'operations.availability': {
        title: 'Disponibilidad Intradía',
        version: 1,
        steps: [
            {
                element: '[data-tour="availability-header"]',
                popover: {
                    title: 'Operación en Tiempo Real',
                    description: 'Indicador de actualización automática de los datos de disponibilidad.',
                    side: 'bottom',
                    align: 'start',
                },
            },
            {
                element: '[data-tour="availability-kpis"]',
                popover: {
                    title: 'KPIs de Disponibilidad',
                    description: 'Adherencia real intradía, agentes conectados vs agendados, en llamada y disponibles.',
                    side: 'bottom',
                    align: 'center',
                },
            },
            {
                element: '[data-tour="availability-queues"]',
                popover: {
                    title: 'Colas en Atención (CSQs)',
                    description: 'Estado del tráfico por cola: llamadas en espera, SLA, abandonos y agentes atendiendo.',
                    side: 'top',
                    align: 'start',
                },
            },
        ],
    },

    // 📋 Operaciones / Reporte Diario
    'operations.daily-report': {
        title: 'Reporte Diario',
        version: 1,
        steps: [
            {
                element: '[data-tour="daily-report-header"]',
                popover: {
                    title: 'Navegación de Fecha',
                    description: 'Navega entre días o vuelve a hoy para consultar el reporte operativo de cada jornada.',
                    side: 'bottom',
                    align: 'start',
                },
            },
            {
                element: '[data-tour="daily-report-view-toggle"]',
                popover: {
                    title: 'Vista Operador / Equipo',
                    description: 'Alterna entre tu reporte individual y el consolidado del equipo. En vista de equipo puedes filtrar por equipo.',
                    side: 'bottom',
                    align: 'start',
                },
            },
            {
                element: '[data-tour="daily-report-content"]',
                popover: {
                    title: 'Detalle del Reporte',
                    description: 'Horarios programados vs reales (entrada, almuerzo, descanso), llamadas, actividades y desconexiones.',
                    side: 'top',
                    align: 'center',
                },
            },
        ],
    },

    // ☎️ Operaciones / Desempeño por Cola
    'operations.queue-performance': {
        title: 'Dashboard de Colas',
        version: 1,
        steps: [
            {
                element: '[data-tour="queue-performance-header"]',
                popover: {
                    title: 'Selección de Cola y Fecha',
                    description: 'Elige una cola específica o consulta el resumen general para la fecha seleccionada.',
                    side: 'bottom',
                    align: 'start',
                },
            },
            {
                element: '[data-tour="queue-performance-kpis"]',
                popover: {
                    title: 'Volumen y SLA',
                    description: 'Llamadas ofrecidas, atendidas, abandonadas y porcentaje de servicio (SLA) en el periodo.',
                    side: 'bottom',
                    align: 'start',
                },
            },
            {
                element: '[data-tour="queue-performance-table"]',
                popover: {
                    title: 'Rendimiento por Cola',
                    description: 'Detalle por cola: abandono, SLA, ASA y AHT real contra la meta.',
                    side: 'top',
                    align: 'center',
                },
            },
        ],
    },

    // 📈 Operaciones / Scorecard de Desempeño
    'operations.performance': {
        title: 'Scorecard de Desempeño',
        version: 1,
        steps: [
            {
                element: '[data-tour="performance-header"]',
                popover: {
                    title: 'Filtros del Scorecard',
                    description: 'Busca y selecciona empleado, equipo, periodo y fecha para personalizar el análisis.',
                    side: 'bottom',
                    align: 'start',
                },
            },
            {
                element: '[data-tour="performance-metrics"]',
                popover: {
                    title: 'Métricas Principales',
                    description: 'Productividad, adherencia, utilización, tiempo productivo, conexión y llamadas del periodo.',
                    side: 'bottom',
                    align: 'start',
                },
            },
            {
                element: '[data-tour="performance-detail"]',
                popover: {
                    title: 'Detalle por Día',
                    description: 'Asistencia y entrada, pausas programadas, tiempo por estado, motivos de auxiliar y rendimiento por cola.',
                    side: 'top',
                    align: 'center',
                },
            },
        ],
    },

    // 🧮 Operaciones / Analítica Avanzada
    'operations.advanced-analytics': {
        title: 'Analítica de Productividad Avanzada',
        version: 1,
        steps: [
            {
                element: '[data-tour="advanced-header"]',
                popover: {
                    title: 'Filtros de Analítica',
                    description: 'Selecciona el equipo y la fecha para consultar métricas del modelo WU/PWI.',
                    side: 'bottom',
                    align: 'start',
                },
            },
            {
                element: '[data-tour="advanced-kpis"]',
                popover: {
                    title: 'KPIs del Modelo WU/PWI',
                    description: 'PWI promedio, work units atendidos, capacidad teórica y brecha de capacidad (gap).',
                    side: 'bottom',
                    align: 'start',
                },
            },
            {
                element: '[data-tour="advanced-detail"]',
                popover: {
                    title: 'Rankings y Desglose',
                    description: 'Top PWI, mayores gaps y desglose por agente (availability, efficiency, capacidad, PWI). El diccionario de indicadores está al final.',
                    side: 'top',
                    align: 'center',
                },
            },
        ],
    },

    // 📞 Centro de Contacto / Registro de Llamadas
    'connect.calls': {
        title: 'Registro de Llamadas',
        version: 1,
        steps: [
            {
                element: '[data-tour="calls-header"]',
                popover: {
                    title: 'Gestión de Interacciones',
                    description: 'Historial y registro centralizado de llamadas atendidas en las colas.',
                    side: 'bottom',
                    align: 'start',
                },
            },
            {
                element: '[data-tour="calls-filters"]',
                popover: {
                    title: 'Filtros Avanzados',
                    description: 'Filtra por cola, agente, rango de fechas y estado de la llamada.',
                    side: 'bottom',
                    align: 'start',
                },
            },
            {
                element: '[data-tour="calls-table"]',
                popover: {
                    title: 'Detalle de Registros',
                    description: 'Revisa cada llamada: estado, cola, subtipo, agente que la atendió y acciones. Usa Editar para modificar un registro.',
                    side: 'top',
                    align: 'center',
                },
            },
        ],
    },

    // 📊 Centro de Contacto / Dashboard del Agente
    'connect.agent-dashboard': {
        title: 'Mis Datos',
        version: 1,
        steps: [
            {
                element: '[data-tour="agent-dashboard-header"]',
                popover: {
                    title: 'Desempeño Individual',
                    description: 'Monitorea tus métricas clave de operación. Cambia el rango de fechas (hoy, semana, mes) para ver tu evolución.',
                    side: 'bottom',
                    align: 'start',
                },
            },
            {
                element: '[data-tour="agent-dashboard-kpis"]',
                popover: {
                    title: 'Tus Métricas',
                    description: 'Llamadas atendidas, TMO promedio, AHT y llamadas fallidas de tu actividad.',
                    side: 'bottom',
                    align: 'start',
                },
            },
            {
                element: '[data-tour="agent-dashboard-calls"]',
                popover: {
                    title: 'Registro de tus Llamadas',
                    description: 'Detalle de tus interacciones recientes: hora, cola, número, tiempos de conversación y trabajo.',
                    side: 'top',
                    align: 'center',
                },
            },
        ],
    },

    // 📈 Centro de Contacto / Dashboard General
    'connect.general-dashboard': {
        title: 'Dashboard General',
        version: 1,
        steps: [
            {
                element: '[data-tour="general-dashboard-header"]',
                popover: {
                    title: 'Monitoreo de la Operación',
                    description: 'Visión macro de la operación: volumen, SLA, abandono y llamadas atendidas.',
                    side: 'bottom',
                    align: 'start',
                },
            },
            {
                element: '[data-tour="general-dashboard-kpis"]',
                popover: {
                    title: 'Métricas de Servicio',
                    description: 'Volumen inbound, service level (SLA), tasa de abandono y llamadas atendidas del período.',
                    side: 'bottom',
                    align: 'start',
                },
            },
            {
                element: '[data-tour="general-dashboard-performers"]',
                popover: {
                    title: 'Top Performers',
                    description: 'Agentes con mayor volumen de atención en el período seleccionado.',
                    side: 'top',
                    align: 'start',
                },
            },
        ],
    },

    // ➕ Centro de Contacto / Nuevo Registro de Llamada
    'connect.call-create': {
        title: 'Nuevo Registro de Llamada',
        version: 1,
        steps: [
            {
                element: '[data-tour="call-create-header"]',
                popover: {
                    title: 'Alta de Llamada',
                    description: 'Ingresa los detalles de la interacción recibida para registrarla en el sistema.',
                    side: 'bottom',
                    align: 'start',
                },
            },
            {
                element: '[data-tour="call-create-form"]',
                popover: {
                    title: 'Datos de la Llamada',
                    description: 'Completa teléfono, asegurado, cola, subtipo y detalles de la interacción.',
                    side: 'bottom',
                    align: 'start',
                },
            },
            {
                element: '[data-tour="call-create-history"]',
                popover: {
                    title: 'Historial del Cliente',
                    description: 'Revisa el historial del número o asegurado para contextualizar la atención.',
                    side: 'left',
                    align: 'start',
                },
            },
        ],
    },

    // ⭐ Calidad / Evaluaciones
    'quality.evaluations': {
        title: 'Evaluaciones de Calidad',
        version: 1,
        steps: [
            {
                element: '[data-tour="quality-header"]',
                popover: {
                    title: 'Módulo de Calidad',
                    description: 'Consulta las auditorías de servicio y el desempeño cualitativo de las llamadas.',
                    side: 'bottom',
                    align: 'start',
                },
            },
            {
                element: '[data-tour="quality-new-btn"]',
                popover: {
                    title: 'Nueva Evaluación',
                    description: 'Inicia una nueva pauta de evaluación seleccionando la cola y el agente auditado.',
                    side: 'left',
                    align: 'center',
                },
            },
        ],
    },

    // 🎯 Calidad / Formulario de Evaluación
    'quality.evaluation-form': {
        title: 'Nueva Evaluación',
        version: 1,
        steps: [
            {
                element: '[data-tour="quality-form-header"]',
                popover: {
                    title: 'Nueva Evaluación de Calidad',
                    description: 'Registra una evaluación de calidad para una llamada del agente seleccionado.',
                    side: 'bottom',
                    align: 'start',
                },
            },
            {
                element: '[data-tour="quality-form-context"]',
                popover: {
                    title: 'Contexto de la Llamada',
                    description: 'Confirma la cola, el empleado y la fecha/hora de la llamada a evaluar.',
                    side: 'bottom',
                    align: 'start',
                },
            },
            {
                element: '[data-tour="quality-form-criteria"]',
                popover: {
                    title: 'Criterios de Evaluación',
                    description: 'Marca cada criterio como cumplido o no. Los criterios dependen de la cola seleccionada.',
                    side: 'top',
                    align: 'center',
                },
            },
            {
                element: '[data-tour="quality-form-redflags"]',
                popover: {
                    title: 'Red Flags',
                    description: 'Selecciona las incidencias graves aplicables; restan puntos automáticamente del score.',
                    side: 'top',
                    align: 'start',
                },
            },
            {
                element: '[data-tour="quality-form-obs"]',
                popover: {
                    title: 'Observaciones y Guardado',
                    description: 'Agrega comentarios y guarda la evaluación cuando hayas completado los criterios.',
                    side: 'top',
                    align: 'start',
                },
            },
        ],
    },

    // 📋 Calidad / Detalle de Evaluación
    'quality.evaluation-detail': {
        title: 'Detalle de Evaluación',
        version: 1,
        steps: [
            {
                element: '[data-tour="quality-detail-header"]',
                popover: {
                    title: 'Detalle de la Evaluación',
                    description: 'Resumen completo de la evaluación: cola, empleado, evaluador, estado y score.',
                    side: 'bottom',
                    align: 'start',
                },
            },
            {
                element: '[data-tour="quality-detail-scores"]',
                popover: {
                    title: 'Puntajes por Criterio',
                    description: 'Desglose del score por criterio evaluado y el total alcanzado.',
                    side: 'top',
                    align: 'center',
                },
            },
            {
                element: '[data-tour="quality-detail-actions"]',
                popover: {
                    title: 'Feedback y Calibración',
                    description: 'Agrega feedback al agente o calibra el score cuando la evaluación está activa.',
                    side: 'left',
                    align: 'start',
                },
            },
        ],
    },

    // 💬 Calidad / Feedback
    'quality.feedback': {
        title: 'Agregar Feedback',
        version: 1,
        steps: [
            {
                element: '[data-tour="quality-feedback-header"]',
                popover: {
                    title: 'Feedback al Agente',
                    description: 'Escribe la retroalimentación para el agente evaluado.',
                    side: 'bottom',
                    align: 'start',
                },
            },
            {
                element: '[data-tour="quality-feedback-form"]',
                popover: {
                    title: 'Observaciones',
                    description: 'Completa las observaciones de la retroalimentación y guárdala.',
                    side: 'bottom',
                    align: 'start',
                },
            },
        ],
    },

    // ⚖️ Calidad / Calibración
    'quality.calibration': {
        title: 'Calibrar Evaluación',
        version: 1,
        steps: [
            {
                element: '[data-tour="quality-calibration-header"]',
                popover: {
                    title: 'Calibración de Score',
                    description: 'Ajusta el score de la evaluación con el nuevo valor y un motivo.',
                    side: 'bottom',
                    align: 'start',
                },
            },
            {
                element: '[data-tour="quality-calibration-form"]',
                popover: {
                    title: 'Nuevo Score y Motivo',
                    description: 'Ingresa el nuevo score y la observación que justifique la calibración.',
                    side: 'bottom',
                    align: 'start',
                },
            },
        ],
    },

    // 👥 Personal / Asignación de Equipos
    'personnel.team-assignments': {
        title: 'Asignación de Equipos',
        version: 1,
        steps: [
            {
                element: '[data-tour="team-assign-unassigned"]',
                popover: {
                    title: 'Personal Disponible',
                    description: 'Colaboradores sin equipo asignado listos para ser reubicados.',
                    side: 'right',
                    align: 'start',
                },
            },
            {
                element: '[data-tour="team-assign-boards"]',
                popover: {
                    title: 'Empleados en el Equipo',
                    description: 'Usa las casillas para seleccionar y los botones centrales para asignar o desasignar empleados del equipo seleccionado.',
                    side: 'left',
                    align: 'start',
                },
            },
        ],
    },

    // 📥 Personal / Importación de Empleados
    'personnel.import-employees': {
        title: 'Importar Empleados',
        version: 1,
        steps: [
            {
                element: '[data-tour="import-employees-header"]',
                popover: {
                    title: 'Importación Masiva',
                    description: 'Carga un archivo CSV con datos de empleados. El sistema valida por filas, importa por chunks y procesa en cola.',
                    side: 'bottom',
                    align: 'start',
                },
            },
            {
                element: '[data-tour="import-employees-form"]',
                popover: {
                    title: 'Archivo y Chunk',
                    description: 'Selecciona el CSV (máx. 20MB) y define el tamaño de lote (100-1000 registros) para el procesamiento.',
                    side: 'bottom',
                    align: 'start',
                },
            },
            {
                element: '[data-tour="import-employees-history"]',
                popover: {
                    title: 'Historial de Importaciones',
                    description: 'Consulta el estado de cada lote: procesadas, importadas, rechazadas y quién lo ejecutó.',
                    side: 'top',
                    align: 'center',
                },
            },
        ],
    },
};
