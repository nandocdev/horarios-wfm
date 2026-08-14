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

    // 🔄 Torre de Control / Operaciones
    'control-tower': {
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
