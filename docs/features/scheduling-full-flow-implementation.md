# 📋 PLAN DE IMPLEMENTACIÓN: [Módulo de Horarios - SchedulingModule Completo]

## 1. [Turnos de trabajo]

- [x] Define los turnos de trabajo programados por empleados[entrada, salida, duración, permite break, permite lunch]
- [x] Define el tiempo de descanso y almuerzo [minutos]
- [x] Define si el tiempo de descanso y almuerzo es pagado o no, si es pagado descuenta del tiempo de trabajo

---

## 2. [Planificación Semanal]

- [x] [PLANIFICADOR DE HORARIOS] En esta pantalla permite asignar horarios a los equipos por semana (`WeeklyPlanning.php`)
- [x] [Detalle de Asignacion Por Equipo]: Permite ajustar horarios de lunch y break de forma individual, asi como realizar cambios en dias especificos (`TeamWeeklyPlanning.php`)
- [x] [Detalle de asignacion Individual]: Permite asignar o modificar el horario de un empleado en un dia especifico (`EmployeeWeeklyPlanning.php`)
- [x] [Acción de Asignación]: Lógica de negocio para propagar horarios de equipo a individuos (`AssignTeamWeeklyScheduleAction.php`)

[Asignacion global por semana] -> [Detalle de Asignacion Por Equipo] -> [Detalle de asignacion Individual] ✅

---

## 3. [Asignaciones Intraday]

- [x] Define tipos de actividades (`ManageActivityTypes.php`) y plantillas/definiciones (`ManageScheduledActivities.php`).
- [x] [GESTIÓN INTRADÍA]: Interfaz para WFM que permite asignar actividades individuales o masivas a operadores (`ManageIntradayActivities.php`).
- [x] Permite definir tipo de actividad y programa su duración/rango horario usando `TSTZRANGE`.
- [x] Las actividades programadas pueden solaparse con los turnos de trabajo (visualización soportada en el dashboard).

---

## 4. [Monitoreo en Tiempo Real]

- [x] un worker se conectará al api de cisco y cada *n* segundos obtebdra el estado de los agentes, contrastando con el horario y actividades programado (`php artisan cisco:sync`)
- [x] [Coordinador] Pantalla de monitoreo en tiempo real (`RealtimeMonitoring.php`)
- [x] [Coordinador] documenta incidencias de forma manual [ausencias, tardanzas]
- [ ] debe permitir al coordinador modificar o validar el horario de los agentes segun codgos establecidos.
- [x] debe contar con la definicion de estados de agentes [ejemplo: disponible, no disponible, en llamada, etc].

### Estrategia de Poll para el Monitoreo en Tiempo Real

```text
1. El "Long-Running Worker" (Daemon) con Supervisor
Es el método más robusto para polling de alta frecuencia (cada 5-15 segundos).

Cómo funciona: Creas un comando de Artisan (php artisan cisco:sync) que corre en un bucle infinito.
Implementación:
Usa sleep(n) dentro del bucle para controlar la frecuencia.
Se gestiona con Supervisor para asegurar que si el proceso falla, se reinicie automáticamente.
Ventaja: Centralizas la lógica y las credenciales en un solo lugar. Los resultados se guardan en Redis o una tabla UNLOGGED en Postgres para acceso ultra-rápido desde los clientes.
```

---

## 5. [Autogestión del Operador]

- [x] **Mi Horario:** Vista interactiva semanal con detalle de turnos, almuerzos y descansos (`MySchedule.php`).
- [x] **Actividades Programadas:** Visualización de reuniones y capacitaciones asignadas por WFM en el dashboard diario.
- [x] **Cambio de Turno (Swap):** Flujo completo de solicitud de intercambio con operadores de otros equipos (`RequestShiftSwap.php`) e historial (`SwapRequestHistory.php`).
- [x] **Permisos (Trimestral/Compensatorio):** Formulario unificado con control de saldo de 8h trimestrales (`RequestLeave.php`) e historial de aprobación (`LeaveRequestHistory.php`).
- [x] **Mi Jornada Detallada:** Vista de cumplimiento diario (`MyDay.php`).
- [x] **Métricas:** Indicadores de cumplimiento y adherencia (`MyMetrics.php`).
- [ ] El operador puede solicitar justificaciones de ausencias [adjuntar justificante] (PENDIENTE)

---

## [Roles y Permisos]

- **operator** / **supervisor**
  - [x] Consulta de horario y actividades programadas.
  - [x] Solicitud de intercambio de turnos (Swap) con validación de cargo (Operador I).
  - [x] Solicitud de permisos trimestrales (8h/trimestre) y compensatorios.
  - [x] Seguimiento de estados de aprobación en tiempo real mediante notificaciones y Toasts.
- **coordinator** / **chief**
  - [x] **Visto Bueno (VB):** Bandeja de entrada para aprobar o rechazar permisos de sus subordinados directos (`ManagerApprovals.php`).
  - [x] Monitoreo de equipo y documentación de incidencias.
- **wfm**
  - [x] **Visto Bueno Final:** Aprobación definitiva de Swaps e impacto automático en el grid de horarios (`WfmSwapApprovals.php`).
  - [x] **Gestión Intradía:** Programación masiva de actividades (`ManageIntradayActivities.php`).
  - [x] Administración de catálogos (Turnos, Tipos de Actividad, Motivos de Ausencia).
- **admin**
  - [x] Acceso total y configuración de infraestructura organizacional.



---
