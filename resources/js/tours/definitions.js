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
    'contact-center-calls': {
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
                    description: 'Haz clic en cualquier llamada para ver información ampliada, grabaciones o notas.',
                    side: 'top',
                    align: 'center',
                },
            },
        ],
    },

    // ⭐ Calidad / Evaluaciones
    'quality-evaluations': {
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

    // 👥 Personal / Asignación de Equipos
    'team-assignments': {
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
};
