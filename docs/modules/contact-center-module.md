# ConnectModule

## 🎯 Propósito
El `ConnectModule` es el núcleo operativo de la plataforma. Su función principal es la ingesta, procesamiento y visualización de datos provenientes de los sistemas de telefonía y omnicanalidad de Cisco (UCCX, Finesse y CUIC). Actúa como el puente entre la actividad telefónica real y la gestión de fuerza de trabajo (WFM).

---

## 🚀 Funcionalidades Principales

### 1. Integración con Cisco UCCX (CUIC)
- **Sincronización de Datos Históricos:** Comandos programados (`CuicSyncCommand`) que extraen reportes de CUIC para alimentar las tablas de desempeño y registros de llamadas.
- **Importación de Chat y Omnicanalidad:** Acciones específicas para procesar registros de interacciones no-voz (`ImportUccxChatAction`).
- **Backfill:** Capacidad de recuperar datos históricos de fechas pasadas mediante `CuicBackfillCommand`.

### 2. Monitoreo en Tiempo Real
- **Estados de Agentes:** Sincronización constante con Cisco Finesse para conocer el estado actual de los agentes (Ready, Not Ready, Talking, etc.).
- **Estadísticas de Colas (CSQ):** Seguimiento en tiempo real de llamadas en espera, tiempo máximo de espera y nivel de servicio por cola.
- **Dashboards Operativos:** Visualizaciones para WFM y Supervisores que consolidan la salud de la operación en vivo.

### 3. Gestión de Registros de Llamada (Call Records)
- **Tipificación de Llamadas:** Los agentes pueden registrar y categorizar cada interacción mediante Canales, Colas de entrada y Subtipos de caso.
- **Flujo de Trabajo:** Soporta la creación manual o automática de registros, con estados de "Completado" y "Cerrado".

### 4. Análisis de Desempeño (Performance)
- **Scorecards de Agente:** Cálculo de KPIs críticos como AHT (Average Handle Time), Ocupación, Adherencia y Calidad.
- **Resúmenes de Equipo:** Reportes agregados para supervisores que permiten comparar el desempeño de diferentes grupos de trabajo.
- **Transiciones de Estado:** Registro detallado de cada cambio de estado de los agentes para análisis de productividad y "shrinkage".

---

## 🛠 Estructura Técnica

### Modelos Clave
- `CallRecord`: Registro individual de una interacción.
- `AgentCallPerformance`: Métricas agregadas de desempeño por agente y día.
- `AgentStateTransition`: Log cronológico de estados de telefonía.
- `CallQueue` / `Channel` / `CaseSubtype`: Catálogos operativos.
- `CsqRealtimeStat`: Almacenamiento temporal de métricas en tiempo real.

### Actions Destacadas
- `SyncCuicDataAction`: Lógica compleja de orquestación para la ingesta de múltiples reportes de Cisco.
- `GetEmployeePerformanceAction`: Motor de cálculo de KPIs que consolida datos de llamadas, chats y estados.
- `FetchCiscoAgentSnapshotAction`: Consulta directa a las APIs de Finesse para obtener el estado actual de un agente.

### Componentes Livewire
- `RealtimeOperationDashboard`: Panel de control para la gestión minuto a minuto.
- `PerformanceScorecard`: Vista detallada de métricas para el colaborador.
- `CreateCallRecord`: Interfaz de tipificación para el agente.

---

## ⚠️ [RIESGOS]
1. **Dependencia de Terceros:** La estabilidad del módulo depende totalmente de la disponibilidad y performance de las bases de datos de Cisco CUIC y las APIs de Finesse.
2. **Volumen de Datos:** La ingesta de transiciones de estado genera millones de registros. Es vital contar con índices optimizados y una estrategia de particionamiento o purga.
3. **Resolución de Identidad:** El mapeo entre `login_id` de Cisco y `employee_id` interno debe ser exacto. Cualquier error en este mapeo causará que las métricas se asignen al empleado incorrecto.
4. **Latencia de Red:** Las consultas en tiempo real a Finesse pueden experimentar timeouts si la latencia de red es alta o el servidor de Finesse está bajo carga.

---

## 📋 Ejemplo de Uso

### Sincronización manual de datos del día
```bash
php artisan contact-center:cuic-sync --date=2026-05-07
```

### Consultar desempeño de un empleado desde otra Action
```php
use App\Modules\ConnectModule\Actions\GetEmployeePerformanceAction;

$action = app(GetEmployeePerformanceAction::class);
$stats = $action->execute($employeeId, $startDate, $endDate);
```
