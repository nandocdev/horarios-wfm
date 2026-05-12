# WfmModule

## 🎯 Propósito
El `WfmModule` es el cerebro del sistema WFM (Workforce Management). Su objetivo es la planificación, ejecución y monitoreo de los horarios de trabajo, asegurando que la operación cuente con la cobertura necesaria mientras se gestionan las necesidades de flexibilidad de los colaboradores.

---

## 🚀 Funcionalidades Principales

### 1. Planificación Semanal (Weekly Planning)
- **Turnos Base:** Definición de turnos estándar con tiempos de inicio/fin, almuerzo y descansos.
- **Asignación Masiva:** Capacidad de asignar turnos a equipos completos para semanas específicas, con propagación automática a niveles individuales.
- **Grid de Planificación:** Interfaz interactiva para que los gerentes de WFM y supervisores visualicen y editen la malla de turnos de sus equipos.
- **Publicación Controlada:** Los horarios pasan por un estado de borrador antes de ser publicados para que los empleados los visualicen.

### 2. Autoservicio del Colaborador (ESS)
- **Mi Horario:** Vista personalizada para que el agente consulte su jornada semanal y diaria.
- **Intercambios de Turno (Shift Swaps):** Proceso formal para solicitar cambios de turno entre compañeros, incluyendo flujo de validación de reglas (mismo equipo, no sobrelapamiento) y aprobación por supervisores.
- **Solicitudes de Permiso (Leave Requests):** Gestión de vacaciones, permisos remunerados y ausencias médicas.

### 3. Monitoreo y Gestión Intradiaria
- **Real-time Monitoring:** Dashboard avanzado para el seguimiento en vivo del estado de los agentes vs. su horario programado (Adherencia).
- **Timeline de Agente:** Visualización detallada de la jornada de un agente, mostrando actividades programadas (llamadas, breaks, capacitaciones).
- **Actividades Intradiarias:** Capacidad de insertar actividades específicas dentro de un turno ya asignado para ajustes de última hora.

### 4. Gestión de Excepciones y Catálogos
- **Excepciones de Horario:** Registro de eventos que modifican el horario original (llegadas tardías justificadas, horas extra, fallos técnicos).
- **Catálogos Operativos:** Administración de códigos de ausencia, tipos de actividad y estados de telefonía.

---

## 🛠 Estructura Técnica

### Modelos Clave
- `Schedule`: Plantilla de turno.
- `WeeklySchedule`: Cabecera de la planificación de una semana.
- `WeeklyScheduleAssignment`: La unidad mínima de asignación (Empleado + Turno + Día).
- `IntradayActivity`: Actividades específicas dentro de un turno.
- `ScheduleException`: Modificaciones aprobadas al plan original.

### Actions Destacadas
- `AssignTeamWeeklyScheduleAction`: Lógica masiva de asignación con manejo de transacciones.
- `ProcessShiftSwapAction`: Ejecuta el intercambio físico de asignaciones entre dos colaboradores, manejando la trazabilidad de "reemplazos".
- `UpdateEmployeeDayAssignmentAction`: Permite ajustes quirúrgicos a un día específico de un empleado.

### UI (Livewire)
- `RealtimeMonitoring`: Componente de alta complejidad para la supervisión en vivo.
- `AgentTimeline`: Renderizado visual de la jornada laboral.
- `TeamWeeklyPlanning`: Grid administrativo para la gestión de turnos.

---

## ⚠️ [RIESGOS]
1. **Solapamientos de Turno:** La lógica de asignación debe ser extremadamente rigurosa para evitar que un empleado tenga dos turnos en el mismo rango horario.
2. **Performance del Grid:** Cargar la malla de turnos para cientos de empleados simultáneamente puede impactar el rendimiento. Se requiere el uso extensivo de `lazy loading` y optimización de consultas Eloquent.
3. **Reglas de Negocio Complejas:** Los intercambios de turno tienen múltiples validaciones (horas de descanso entre jornadas, límites semanales) que deben ser consistentes tanto en la UI como en el Backend.
4. **Sincronización de Estados:** La adherencia en tiempo real depende de la rapidez con la que se procesen los eventos de `ConnectModule`.

---

## 📋 Ejemplo de Uso

### Intercambiar turnos entre dos empleados
```php
use App\Modules\WfmModule\Actions\ProcessShiftSwapAction;

$action = app(ProcessShiftSwapAction::class);
$action->execute($swapRequestId); // Realiza el swap y marca asignaciones previas como replaced
```

### Consultar el turno de hoy de un empleado
```php
$assignment = WeeklyScheduleAssignment::where('employee_id', $id)
    ->whereHas('weeklySchedule', fn($q) => $q->where('week_start_date', '<=', $today))
    ->where('day_of_week', $today->dayOfWeekIso)
    ->first();
```
