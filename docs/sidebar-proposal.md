# Propuesta de Estructura — Sidebar Menu

Basado en el análisis funcional de los 13 módulos y sus ~90 componentes Livewire.

---

## Principios de organización

1. **El usuario no piensa en módulos** — piensa en tareas (ver mi horario, pedir permiso, revisar métricas)
2. **Máximo 2 niveles de profundidad** — colapsado todo por defecto, visible con hover/click
3. **Grupos funcionales**, no técnicos: "Mi Trabajo", "Gestión", "Administración"
4. **Sidebar colapsable** — iconos + etiqueta cuando expandido, solo iconos cuando colapsado

---

## Propuesta de Árbol

```
📊 Dashboard                          # Aterrizaje principal — tarjetas KPI + feed de novedades
─────────────────────────────────────

🗓 Mi Trabajo                         # Grupo: Autogestión del agente
├── Mi Horario                        # my-schedule: vista semanal de turnos
├── Mi Día                            # my-day: cronograma del día actual + adherencia
├── Mis Métricas                      # my-metrics: productividad, llamadas, tiempos
├── Solicitar Permiso                 # request-leave: formulario de solicitud
├── Solicitar Cambio de Turno         # request-shift-swap: swap con compañero
└── Mis Solicitudes                   # request-summary + leave-request-history + swap-request-history

👥 Mi Equipo                          # Grupo: Supervisión y coordinación
├── Mi Equipo                         # my-team: grid semanal del equipo + novedades
├── Aprobar Permisos                  # manager-approvals: bandeja de solicitudes pendientes
└── Resumen de Solicitudes            # request-summary: consolidado del equipo

📋 Planificación                      # Grupo: Gestión WFM de horarios
├── Planificación Semanal             # weekly-planning: crear/publicar semanas
├── Planificación por Equipos         # weekly-planning-teams: asignar turnos a equipos
├── Planificación de Equipo           # team-weekly-planning: editar miembros
├── Planificación por Empleado        # employee-weekly-planning: edición individual
├── Importar Horario                  # import-weekly-schedule: carga CSV
├── Turnos Base                       # manage-schedules: definición de jornadas
├── Actividades Intradía              # manage-intraday-activities: reuniones, coaching
├── Actividades Programadas           # manage-scheduled-activities: catálogo de actividades
├── Excepciones de Horario            # manage-schedule-exceptions: incidencias
├── Tipos de Actividad                # manage-activity-types
├── Motivos de Ausencia               # manage-absence-reasons
└── Estados de Agente                 # manage-agent-states

🔄 Operaciones                        # Grupo: Monitoreo en tiempo real
├── Monitoreo en Tiempo Real          # realtime-monitoring: estados de agentes en vivo
├── Disponibilidad Intradía           # intraday-availability: cobertura vs programado
├── Desempeño por Cola                # queue-performance-report: estadísticas de CSQ
├── Scorecard de Desempeño            # performance-scorecard: métricas por agente
├── Dashboard de Agente               # agent-performance-dashboard: detalle individual
├── Dashboard de Productividad        # advanced-productivity-dashboard: WU, PWI, adherencia
├── Resumen por Equipo                # team-performance-summary: comparativas
└── Marco de Reportes                 # reporting-framework-index: reportes PDF

📞 Centro de Contacto                 # Grupo: Datos de plataforma telefónica
├── Llamadas                          # call-records: historial de llamadas
├── Colas                             # call-queues: configuración de colas
├── Canales                           # channels: tipos de canal
└── Subtipos de Caso                  # case-subtypes: clasificación

📢 Comunicaciones                     # Grupo: Comunicación interna
├── Inicio                            # home: feed de novedades, shoutouts
├── Noticias                          # news: listar/crear/editar
├── Encuestas                         # polls: listar/crear
└── Reconocimientos                   # shoutouts: listar/crear

🎫 Soporte                            # Grupo: Mesa de ayuda
├── Mis Tickets                       # my-tickets: autogestión
├── Bandeja de Soporte                # manage-tickets: gestión de tickets
└── Base de Conocimiento              # knowledge: artículos de ayuda

📚 Documentación                      # Grupo: Wiki institucional
├── Artículos                         # documentation: wiki pública
└── Administrar Artículos             # manage-articles: gestión

🗃 Archivos                           # Grupo: Gestión documental
├── Explorador de Archivos            # file-browser: navegación por carpetas
├── Centro de Descargas               # download-center
└── Cuotas de Almacenamiento          # quota-manager

⚙️ Administración                     # Grupo: Configuración del sistema
├── Empleados                         # Grupo: Gestión de personal
│   ├── Listar Empleados              # employees.index
│   ├── Crear Empleado                # employees.create
│   └── Importar Empleados            # employees.import
├── Organigrama                       # Grupo: Estructura organizacional
│   ├── Direcciones                   # directorates
│   ├── Departamentos                 # departments
│   └── Cargos                        # positions
├── Equipos                           # teams: gestión de equipos
├── Ubicaciones                       # locations: provincias, distritos
├── Usuarios                          # users: cuentas de acceso
├── Roles y Permisos                  # roles
├── Configuración Operativa           # operational-settings: umbrales, metas KPI
├── Auditoría                         # audit-logs
├── Categorías y Etiquetas            # communications categories/tags
├── Moderación de Contenido           # content-moderation
├── Reportes de Personal              # staffing-summary
└── Mantenimiento del Sistema         # system-maintenance
```

---

## Agrupación por perfiles (visualización condicional)

Cada grupo se muestra u oculta según roles/permissions del usuario autenticado:

| Perfil | Grupos visibles |
|--------|-----------------|
| **Agente** | Dashboard, Mi Trabajo, Comunicaciones, Soporte |
| **Supervisor** | Dashboard, Mi Trabajo, Mi Equipo, Comunicaciones, Soporte |
| **WFM / Planner** | Dashboard, Planificación, Operaciones, Centro de Contacto |
| **Soporte / Helpdesk** | Dashboard, Soporte |
| **Admin** | Todo |

---

## Beneficios de esta propuesta

- **De 13 módulos plano a 11 grupos jerárquicos** con sentido funcional
- **El agente encuentra su autogestión en "Mi Trabajo"** — no tiene que navegar por "WfmModule"
- **Separación clara** entre planificación (WFM) y operaciones (monitoreo)
- **Administración consolidada** — todo lo que es configurar el sistema en un solo lugar
- **Escalable** — nuevos módulos se agregan como items dentro del grupo correspondiente
