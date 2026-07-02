# Especificación Técnica Detallada: ConnectModule (Módulo de Conectividad y CTI)

> Documento RUP Centrado en Arquitectura
> **Módulo:** ConnectModule
> **Ruta:** `app/Modules/ConnectModule`

## 1. Resumen Ejecutivo y Propósito del Módulo

El **ConnectModule** es la "Capa de Anticorrupción" (Anti-Corruption Layer) y motor de integración CTI (*Computer Telephony Integration*) del monolito. Su función es aislar al resto de la aplicación de las complejidades inherentes a la infraestructura telefónica de proveedores externos (específicamente Cisco UCCX, Cisco Finesse y Cisco CUIC).

El módulo orquesta dos tipos de flujo de datos críticos:
1. **Flujo Histórico (ETL):** Importación masiva, transformación y carga asíncrona de bitácoras de llamadas, interacciones de chat, y métricas de desempeño.
2. **Flujo en Tiempo Real (Real-time):** Monitoreo en vivo de los estados de los agentes de Call Center y de los indicadores de las colas de llamadas (CSQ), permitiendo que los supervisores observen la operación en caliente.

Toda esta data es luego vital (Upstream) para el módulo de Workforce Management (WFM) para el cálculo de nómina y adherencia.

---

## 2. Casos de Uso Detallados

### CU-CON-01: Extracción Histórica de Datos CUIC (ETL)
- **Actor:** Sistema (Scheduler).
- **Descripción:** Sincronización periódica de registros telefónicos desde Cisco CUIC hacia la base local.
- **Flujo Principal:**
  1. El Scheduler ejecuta el comando de consola que llama a `SyncCuicDataAction` cada X minutos (ej. cada 15 min).
  2. Este orquestador invoca Sub-Actions especialistas (ej. `ImportUccxInboundAction`, `ImportUccxTransitionsAction`).
  3. El Action envía peticiones HTTP/SOAP a la API de Cisco o consulta directamente la base de datos externa UCCX.
  4. Los datos crudos (Payload/XML) son mapeados a DTOs normalizados.
  5. Se insertan los registros en la base de datos usando `Upsert` (Insert On Duplicate Key Update) por lotes (*chunks*) para minimizar la carga de I/O.

### CU-CON-02: Monitoreo Real-time de Agentes y Colas
- **Actor:** Supervisor de Operaciones.
- **Descripción:** Visualizar el estado actual de sus agentes asignados y cuántas llamadas hay en espera.
- **Flujo Principal:**
  1. El módulo mantiene un proceso (*Daemon* o *Polling* de muy corta frecuencia) que extrae instantáneas de estado mediante `FetchCiscoAgentSnapshotAction`.
  2. Los datos alimentan las tablas `AgentRealtimeState` y `CsqRealtimeStat` usando una transacción rápida.
  3. El Supervisor ingresa al Dashboard (Livewire). El componente se refresca dinámicamente (`wire:poll.2s` o WebSockets/Echo) consumiendo estas métricas en caliente, marcando en rojo a los agentes que exceden el tiempo máximo en *Not Ready*.

### CU-CON-03: Sincronización de Identidades Finesse
- **Actor:** Sistema / Administrador.
- **Flujo Principal:** Al crear un empleado que pertenece a operaciones telefónicas, `SyncFinesseUsersAction` asegura que el ID telefónico (Peripheral Number) asignado en Cisco exista en la tabla `User` (o tabla pivote) local, permitiendo cruzar los datos de las llamadas con la persona real.

### CU-CON-04: Tipificación (Cierre) Manual de Llamada
- **Actor:** Agente Telefónico.
- **Flujo Principal:** Tras colgar una llamada manual (Outbound) o si el sistema de Cisco falla en tipificar, el Agente utiliza la interfaz del sistema para invocar `CompleteCallRecordAction` o `CreateManualCallRecordAction`, seleccionando el resultado de la llamada (usando la tabla de configuración `CaseSubtype`).

---

## 3. Requerimientos Funcionales (RF)

- **RF-CON-01 (Transformación de Datos):** El módulo debe traducir los estados nativos de Cisco (ej. "LOG_IN", "NOT_READY_AUX_1") a estados estándar comprensibles por la aplicación local mediante una matriz de mapeo.
- **RF-CON-02 (Cálculo de Transiciones):** Durante la importación de `AgentStateTransition`, el sistema debe ser capaz de encadenar el evento de inicio con el evento de fin para determinar la "duración" exacta (en segundos) de un estado.
- **RF-CON-03 (Administración de Catálogos):** Proveer CRUD para `CallQueue` (Colas) y `Channel` (ej. Voz, Chat, WhatsApp), permitiendo a los administradores mapear nombres crudos de Cisco a nombres de negocio.
- **RF-CON-04 (Consolidación de Métricas):** El `AgentCallPerformance` debe agregar datos diarios o por intervalo (Total de Llamadas, Tiempo de Habla, Tiempo de Espera) para reportes gerenciales, ejecutado como un proceso derivado del ETL.

---

## 4. Requerimientos No Funcionales (RNF)

- **RNF-CON-01 (Resiliencia ante Fallas):** Los Actions que se comunican con APIs de Cisco deben implementar un patrón de "Circuit Breaker" o control de excepciones estricto. Si el servidor de telefonía está caído, el Action debe abortar limpiamente, registrar el error (Log/Sentry) y reintentar en el siguiente ciclo sin romper el sistema central.
- **RNF-CON-02 (Rendimiento ETL - Upserts Masivos):** La importación de cientos de miles de registros diarios exige que `ImportUccxInboundAction` divida la data en lotes (`chunk`) de máximo 1000 registros y use inserción masiva (`upsert()`) de Eloquent. Los bucles de inserción de un solo registro (`User::create()`) dentro de foreachs están estrictamente prohibidos.
- **RNF-CON-03 (Mitigación de Bloqueos - Deadlocks):** Al actualizar tablas de `AgentRealtimeState` a muy alta frecuencia, las transacciones deben mantenerse lo más cortas posibles para no generar bloqueos (Lock Wait Timeout) cuando los componentes de Livewire intenten leer la misma tabla simultáneamente.

---

## 5. Modelos de Datos Detallados

La estructura soporta volumen masivo y lectura rápida (OLAP ligero):

| Atributo | Tipo / Cast | Descripción y Lógica Interna |
| :--- | :--- | :--- |
| **Entidad: `CallRecord`** | | **CDR (Call Detail Record) - Llamadas Individuales** |
| `contact_id` | `string` | ID único global de la interacción provisto por Cisco (UUID / Session ID). (Unique Index). |
| `agent_id` | `integer` (FK) | Relacionado a `User`, resuelto por el mapeo de IDs. |
| `ani` / `dnis` | `string` | Número de origen (quien llama) y destino. |
| `talk_time`, `hold_time` | `integer` | Duración en segundos (Cast a `int` para sumarizaciones). |
| `case_subtype_id` | `integer` (FK)| Para clasificar (Tipificar) de qué trató la llamada. |
| **Entidad: `AgentStateTransition`** | | **Bitácora de Estados del Agente** |
| `agent_id` | `integer` (FK) | El Agente en cuestión. |
| `state` / `reason_code`| `string` | Ej: `NOT_READY`, `LUNCH_BREAK` (Mapeado del proveedor). |
| `start_time` / `end_time`| `datetime` | Rango de vigencia. `end_time` puede ser nulo hasta el próximo cambio. |
| **Entidad: `AgentRealtimeState`** | | **Monitor Vivo** |
| `agent_id` | `integer` (PK/FK)| Un solo registro por agente activo. Se sobrescribe en cada pulso. |
| `current_state` | `string` | Estado actual de Finesse. |
| `duration_seconds` | `integer` | Tiempo transcurrido en el estado actual (actualizado vía Job). |

---

## 6. Roles y Permisos (Policies)

La seguridad está orientada a la visualización de datos confidenciales y la gestión del CTI:

- **Policy: `CallRecordPolicy`**
  - `view`: Los agentes pueden ver su propia historia de llamadas (filtrado automático en el scope por `user_id = auth()->id()`). Los Supervisores pueden ver las llamadas de los agentes bajo su mando jerárquico.
  - `update`: Restringido (sólo para tipificar `case_subtype_id`, los metadatos de duración o ANI son inmutables para el usuario).
- **Policy: `DashboardRealtimePolicy`**
  - `view`: Exclusivo para Coordinadores, Supervisores y Administradores (rol operativo alto). No accesible para Agentes rasos.
- **System Admin (`connect.settings`):** Permiso para ejecutar migraciones manuales (botones de "Forzar Sincronización") y administrar catálogos (`CallQueue`, `Channel`).

---

## 7. Eventos, Listeners y Notificaciones

Como capa transaccional, es un importante emisor de eventos:

### Eventos de Dominio (Emitidos)
- `CtiAgentStateChanged`: Emitido cuando el Job detecta una variación en `AgentRealtimeState`. (Crucial para que otros módulos ejecuten reglas de negocio, como alertas de excedencia de *break*).
- `CallImported` / `CallCompleted`: Emitido tras el cierre e inserción de un `CallRecord` (Permite disparar encuestas de calidad o notificar a CRM externo si lo hubiese).
- `SyncFailed`: Evento interno de alerta técnica. Emitido si Cisco CUIC no responde tras "N" reintentos, desencadenando una alerta a IT/Desarrollo.

---

## 8. Servicios y Acciones Detallados (Actions)

La complejidad del módulo reside en la orquestación de datos:

### `ImportUccxInboundAction`
- **Ubicación:** `App\Modules\ConnectModule\Actions\ImportUccxInboundAction`
- **Responsabilidad:** Transformar y persistir llamadas Inbound.
- **Lógica:** 
  1. Recibe una colección cruda (`Collection` / array de arreglos asociativos) originaria de la base UCCX remota.
  2. Mapea la colección a DTOs (`CallRecordDTO`) validados.
  3. Procesa el cruce de identificadores: Toma el `resource_id` de Cisco, busca en caché el `user_id` local equivalente. Si no existe, lo deja huérfano temporalmente o lanza log.
  4. Agrupa en chunks de 500 registros y ejecuta `CallRecord::upsert()`, basándose en el `contact_id` único para no duplicar datos si un proceso corre superpuesto.

### `FetchCiscoFinesseResourceAction`
- **Responsabilidad:** Adaptador HTTP (Guzzle/Laravel Http Client) que dialoga en XML o JSON con la API de Cisco Finesse. Incluye inyección del Token de Finesse (Basic Auth), cabeceras requeridas, validación del `status_code` de respuesta (200 OK), y parseo del XML (usando `simplexml_load_string`) a arrays asociativos, atrapando excepciones `ConnectionException`.

### `SyncAgentRealtimeStateAction`
- Recibe un `AgentStateDTO` con el estado captado en el instante *T*. Si es diferente al almacenado en DB, actualiza `AgentRealtimeState` y dispara `CtiAgentStateChanged`.

---

## 9. Endpoints o Rutas Detalladas (Livewire / Console)

Este módulo tiene más interacción de sistema a sistema que rutas Web:

### Consola (CLI / Scheduler) - El corazón del módulo
- `php artisan connect:sync-cuic`
- `php artisan connect:sync-realtime` (Programado para correr en ciclos infinitos cortos, o manejado como un daemon worker `php artisan queue:work --queue=realtime`).

### Monitores Web (Livewire)
- **`GET /connect/monitors/agents`**
  - Componente: `ConnectModule\Livewire\AgentRealtimeMonitor`
  - Lógica: Consulta `AgentRealtimeState::with('user')`. Se actualiza reactivamente (`wire:poll.3s`). Colorea filas en rojo usando directivas Blade si el `duration_seconds` > Umbral configurado en `AppSetting`.
- **`GET /connect/reports/calls`**
  - Componente: `ConnectModule\Livewire\CallRecordReport`
  - Lógica: Data Table interactiva con filtrado avanzado (Daterange picker, Agent dropdown, Queue dropdown). Descarga delegada a Excel.

---

## 10. Dependencias con otros Módulos

El `ConnectModule` es un proveedor fundamental (Upstream) en el flujo de negocio:

*   **Dependencia Downstream Estricta (`CoreModule`):** Requiere `User` para vincular métricas de rendimiento y configuraciones de conexión.
*   **Fuente Crítica para `WfmModule` (Workforce Management):** El módulo WFM escucha los `AgentStateTransition` de este módulo para compararlos contra el horario planificado y calcular la "Adherencia" y "Tardanzas". Sin `ConnectModule`, el WFM no puede verificar si el empleado realmente se conectó a operar.
*   **Fuente Crítica para `OperationsModule`:** El módulo de operaciones consolida los KPIs (AHT - Average Handle Time, Hold Time) importados (`CallRecord`, `AgentCallPerformance`) para emitir evaluaciones de desempeño y bonos.

---

## 11. Estructura de Carpetas

```tree
app/Modules/ConnectModule
├── Actions
│   ├── CloseCallRecordAction.php
│   ├── CompleteCallRecordAction.php
│   ├── CreateCallQueueAction.php
│   ├── CreateCallRecordAction.php
│   ├── CreateCaseSubtypeAction.php
│   ├── CreateChannelAction.php
│   ├── CreateManualCallRecordAction.php
│   ├── DeleteCallQueueAction.php
│   ├── DeleteCaseSubtypeAction.php
│   ├── DeleteChannelAction.php
│   ├── FetchAgentDetailAction.php
│   ├── FetchAgentStateTransitionsAction.php
│   ├── FetchCiscoAgentSnapshotAction.php
│   ├── FetchCiscoFinesseResourceAction.php
│   ├── ImportUccxChatAction.php
│   ├── ImportUccxInboundAction.php
│   ├── ImportUccxPerformanceAction.php
│   ├── ImportUccxTransitionsAction.php
│   ├── SyncAgentRealtimeStateAction.php
│   ├── SyncCsqRealtimeStatsAction.php
│   ├── SyncCuicDataAction.php
│   ├── SyncFinesseUsersAction.php
│   ├── UpdateCallQueueAction.php
│   ├── UpdateCaseSubtypeAction.php
│   └── UpdateChannelAction.php
├── Console
│   └── Commands
│       ├── AutoImportUccxCommand.php
│       ├── CuicBackfillCommand.php
│       ├── CuicRealtimeSyncCommand.php
│       ├── CuicSyncCommand.php
│       ├── FinesseSyncCommand.php
│       ├── ImportUccxDataCommand.php
│       └── TestCuicAgentDetailCommand.php
├── Database
│   └── Migrations
│       └── 2026_05_04_132836_create_csq_realtime_stats_table.php
├── DTOs
│   ├── CallCloseDTO.php
│   ├── CallCompleteDTO.php
│   ├── CallQueueDTO.php
│   ├── CallStartDTO.php
│   ├── CaseSubtypeDTO.php
│   ├── ChannelDTO.php
│   ├── ManualCallRecordDTO.php
│   └── UccxCallDataDTO.php
├── Emails
│   ├── CuicBackfillReport.php
│   └── ImportErrorNotification.php
├── Http
│   ├── Controllers
│   │   ├── CallRecordController.php
│   │   └── CiscoFinesseController.php
│   └── Requests
│       ├── CloseCallRequest.php
│       ├── CompleteCallRequest.php
│       ├── CreateCallRequest.php
│       └── FetchCiscoAgentSnapshotRequest.php
├── Livewire
│   ├── AgentDashboard.php
│   ├── CreateCallRecord.php
│   ├── EditCallRecord.php
│   ├── Forms
│   │   ├── CallQueueForm.php
│   │   ├── CaseSubtypeForm.php
│   │   ├── ChannelForm.php
│   │   ├── CompleteCallRecordForm.php
│   │   └── CreateCallRecordForm.php
│   ├── GeneralDashboard.php
│   ├── ListCallQueues.php
│   ├── ListCallRecords.php
│   ├── ListCaseSubtypes.php
│   └── ListChannels.php
├── Models
│   ├── AgentCallPerformance.php
│   ├── AgentRealtimeState.php
│   ├── AgentStateTransition.php
│   ├── CallQueue.php
│   ├── CallRecord.php
│   ├── CaseSubtype.php
│   ├── Channel.php
│   ├── ChatRecord.php
│   └── CsqRealtimeStat.php
├── Policies
│   ├── CallQueuePolicy.php
│   ├── CallRecordPolicy.php
│   ├── CaseSubtypePolicy.php
│   └── ChannelPolicy.php
├── Providers
│   └── ModuleServiceProvider.php
├── Resources
│   └── Views
│       ├── emails
│       │   ├── backfill-report.blade.php
│       │   └── import-error.blade.php
│       └── livewire
│           ├── agent-dashboard.blade.php
│           ├── create-call-record.blade.php
│           ├── edit-call-record.blade.php
│           ├── general-dashboard.blade.php
│           ├── list-call-queues.blade.php
│           ├── list-call-records.blade.php
│           ├── list-case-subtypes.blade.php
│           ├── list-channels.blade.php
│           └── team-performance-summary.blade.php
├── Routes
│   └── web.php
└── Services
    ├── CiscoFinesseService.php
    ├── CitizenValidationService.php
    ├── CuicReportService.php
    ├── FinesseService.php
    └── TelemetryService.php

```