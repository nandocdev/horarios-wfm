# Comandos de Sincronización Cisco — HorariosWFM

> Documento de referencia de todos los comandos Artisan relacionados con la sincronización de datos desde los sistemas Cisco (UCCX, Finesse, CUIC).
> Versión 1.0 — Julio 2026

## Arquitectura de Integración

```
 ┌─────────────────────────────────────────────────────────────┐
 │                      Cisco UCCX/Finesse                      │
 │  API REST XML (tiempo real) ← cisco:sync --loop --interval=5 │
 │  API REST XML (identidad)   ← finesse:sync                   │
 └──────────┬──────────────────┬────────────────────────────────┘
            │                  │
            ▼                  ▼
 ┌──────────────────┐  ┌──────────────────┐
 │ AgentRealtimeState│  │  Employee (name   │
 │  (UNLOGGED TABLE) │  │   sync)           │
 └──────────────────┘  └──────────────────┘

 ┌─────────────────────────────────────────────────────────────┐
 │                     Cisco CUIC                               │
 │  API REST con UUIDs (ETL histórico) ← cuic:sync             │
 │  API REST (CSQ realtime)           ← cuic:sync-realtime      │
 │  API REST (backfill masivo)        ← cuic:backfill           │
 └──────────┬──────────────────┬────────────────────────────────┘
            │                  │
            ▼                  ▼
 ┌──────────────────┐  ┌──────────────────┐
 │   CallRecords     │  │  CsqRealtimeStats│
 │ AgentPerformance  │  │                   │
 │ StateTransitions  │  └──────────────────┘
 └──────────────────┘

 ┌─────────────────────────────────────────────────────────────┐
 │                    Cisco UCCX (CSV)                          │
 │  Archivos CSV en disco      ← uccx:import / uccx:auto-import│
 └──────────┬──────────────────────────────────────────────────┘
            ▼
 ┌──────────────────┐
 │  CallRecords      │
 │  AgentPerformance │
 │  etc.             │
 └──────────────────┘
```

## Tabla Resumen

| Comando | Sistema | Tipo | Loop | Intervalo Default | Horario |
|---------|---------|------|------|-------------------|---------|
| `cisco:sync` | UCCX Finesse | Estados de agente en vivo | `--loop` | 5s | 05:00–19:00 |
| `cisco:test` | UCCX Finesse | Diagnóstico de conexión | No | — | — |
| `finesse:sync` | Finesse | Sincronización de identidad | No | — | — |
| `cuic:sync` | CUIC | ETL histórico (CDRs, transiciones, rendimiento) | `--loop` | 300s (5 min) | — |
| `cuic:sync-realtime` | CUIC | Métricas de cola (CSQ) en tiempo real | `--loop` | 10s | — |
| `cuic:backfill` | CUIC | Backfill histórico masivo | No | — | — |
| `cuic:test-agent-detail` | CUIC | Diagnóstico de reporte agent_detail | No | — | — |
| `uccx:import` | UCCX CSV | Importación manual de archivos CSV | No | — | — |
| `uccx:auto-import` | UCCX CSV | Importación automática de CSV | No (scheduled) | hourly vía scheduler | — |

---

## Comandos detallados

### 1. `cisco:sync` — Sincronización en Vivo con Finesse

Sincroniza los estados de los agentes desde Cisco UCCX Finesse API cada 5 segundos.

```bash
php artisan cisco:sync --loop --interval=5
```

| Opción | Default | Descripción |
|--------|---------|-------------|
| `--loop` | — | Ejecutar en bucle infinito |
| `--interval` | 5 | Segundos entre cada ciclo de sincronización |

**Comportamiento:**
1. Primera ejecución: sincroniza datos maestros (nombres, equipos) desde Finesse.
2. En cada ciclo: consulta `getAgentInfo()` por cada agente activo y actualiza `agent_realtime_states`.
3. Si el agente está en estado `TALKING`, obtiene datos de la llamada via `getAgentDialogs()`.
4. Opera solo dentro del horario 05:00–19:00. Fuera de ese horario, espera 60s y reintenta.
5. Implementa `Isolatable` para evitar ejecuciones concurrentes.

**Daemon:** `start-cisco-sync.sh`
**Log:** `/var/log/cisco-sync.log`
**Cliente HTTP:** `app/Shared/Infrastructure/Cisco/CiscoFinesseClient.php`

---

### 2. `cisco:test` — Prueba de Conexión Finesse

Diagnóstico rápido de conectividad con la API REST de Cisco Finesse.

```bash
php artisan cisco:test
php artisan cisco:test agente123
```

| Argumento | Default | Descripción |
|-----------|---------|-------------|
| `agentId` | `UCCX_USERNAME` de `.env` | ID del agente a consultar |

---

### 3. `finesse:sync` — Sincronización de Identidad Finesse

Sincroniza nombres de agentes desde Cisco Finesse a la tabla local de empleados.

```bash
php artisan finesse:sync
```

**Sin opciones.** Consulta todos los usuarios en Finesse y actualiza `first_name`/`last_name` de los empleados locales por coincidencia de `username`.

---

### 4. `cuic:sync` — ETL Histórico CUIC

Importa datos históricos desde Cisco CUIC (CDRs, transiciones de estado, rendimiento de agente, chats).

```bash
php artisan cuic:sync                                          # Últimos 60 minutos
php artisan cuic:sync --minutes=120                            # Últimos 120 minutos
php artisan cuic:sync --from="2026-07-01 06:00" --to="2026-07-01 18:00"  # Rango explícito
php artisan cuic:sync --loop --interval=300                    # Continuo cada 5 minutos
```

| Opción | Default | Descripción |
|--------|---------|-------------|
| `--minutes` | 60 | Ventana hacia atrás en minutos |
| `--from` | — | Fecha/hora de inicio (Y-m-d H:i:s) |
| `--to` | — | Fecha/hora de fin (Y-m-d H:i:s) |
| `--loop` | — | Ejecutar en bucle infinito |
| `--interval` | 300 | Segundos entre ciclos en modo loop |

**Tipos de datos sincronizados:**
- `transitions` — Transiciones de estado de agente
- `performance` — Rendimiento por llamada (talk_time, hold_time, work_time)
- `calls` — Registros de llamadas (CDRs)
- `chats` — Registros de chat

**Post-sincronización:** Si se importaron transiciones, ejecuta `ReconcileEmployeeAttendanceAction` para hoy y ayer.

**Daemon:** `start-cuic-sync.sh`
**Servicio:** `app/Modules/ConnectModule/Services/CuicReportService.php`

---

### 5. `cuic:sync-realtime` — CSQ en Tiempo Real

Sincroniza métricas de colas (CSQ) desde CUIC en tiempo real.

```bash
php artisan cuic:sync-realtime --loop --interval=10
```

| Opción | Default | Descripción |
|--------|---------|-------------|
| `--loop` | — | Ejecutar en bucle infinito |
| `--interval` | 10 | Segundos entre cada sincronización |

**Datos sincronizados:** `csq_realtime_stats` — agentes por estado, llamadas en espera, nivel de servicio, etc.

**Daemon:** `start-cuic-sync.sh`

---

### 6. `cuic:backfill` — Backfill Histórico Masivo CUIC

Sincronización histórica masiva para recuperar datos retrospectivos. Procesa en intervalos configurables con barra de progreso.

```bash
php artisan cuic:backfill --months=1 --chunk=30 --delay=1          # Último mes
php artisan cuic:backfill --days=7 --chunk=60 --delay=2            # Últimos 7 días
php artisan cuic:backfill --months=3 --chunk=15 --delay=1 --unattended  # 3 meses, desatendido
```

| Opción | Default | Descripción |
|--------|---------|-------------|
| `--months` | 1 | Meses hacia atrás (1-6) |
| `--days` | — | Días hacia atrás (prioridad sobre months) |
| `--chunk` | 30 | Tamaño del intervalo en minutos |
| `--delay` | 1 | Segundos de espera entre peticiones |
| `--unattended` | — | Ignorar errores y continuar sin intervención |

**Comportamiento:**
- Usa `set_time_limit(0)` para evitar timeout en procesos largos.
- Envía reporte diario por email a `ferncastillo@css.gob.pa` con estadísticas del backfill.
- En modo interactivo, pregunta si continuar tras cada error.
- Procesa 4 tipos de datos (transitions, performance, calls, chats).

---

### 7. `cuic:test-agent-detail` — Diagnóstico CUIC

Ejecuta el reporte `agent_detail` de CUIC con rango horario y muestra resultados en consola.

```bash
php artisan cuic:test-agent-detail
php artisan cuic:test-agent-detail --date=2026-07-20 --start=08:00 --end=09:00
php artisan cuic:test-agent-detail --date=2026-07-20 --agent="Amalia Renteria"
```

| Opción | Default | Descripción |
|--------|---------|-------------|
| `--report` | `agent_detail` | Clave del reporte en `config/contact-center.php` |
| `--date` | Ayer | Fecha en formato Y-m-d |
| `--start` | `06:00` | Hora de inicio |
| `--end` | `07:00` | Hora de fin |
| `--agent*` | Todos | Nombre(s) de agente a filtrar (repetible) |

---

### 8. `uccx:import` — Importación Manual de CSV UCCX

Importa datos históricos desde archivos CSV generados por Cisco UCCX.

```bash
php artisan uccx:import /ruta/al/archivo.csv
php artisan uccx:import /ruta/al/directorio/
php artisan uccx:import --all
```

| Argumento | Descripción |
|-----------|-------------|
| `path` | Archivo o directorio a importar |
| `--all` | Importar todos los archivos en `UCCX_DATA_PATH` |

**Tipos detectados automáticamente por directorio:**
| Directorio | Tipo | Action |
|------------|------|--------|
| `inbound/` | Llamadas entrantes | `ImportUccxInboundAction` |
| `not_ready/` | Transiciones no disponible | `ImportUccxTransitionsAction` |
| `aht/` | Rendimiento (AHT) | `ImportUccxPerformanceAction` |
| `chat/` | Chats | `ImportUccxChatAction` |

---

### 9. `uccx:auto-import` — Importación Automática de CSV UCCX

Barrido automático programado de los directorios UCCX. Se ejecuta cada hora via el scheduler de Laravel.

```bash
php artisan uccx:auto-import
```

**Comportamiento:**
- Escanea los 4 subdirectorios de `UCCX_DATA_PATH`.
- Omite archivos modificados hace menos de 30 segundos (evita importar durante escritura).
- Por cada archivo: importa, y si es de tipo `not_ready`, ejecuta reconciliación de asistencia.
- Elimina el archivo CSV tras importación exitosa.
- Envía notificación por email en caso de error.

**Programación:** `routes/console.php` — `Schedule::command('uccx:auto-import')->hourly()->withoutOverlapping()`

---

## Scripts de Daemon (Producción)

| Script | Comandos que ejecuta | Gestión de procesos |
|--------|---------------------|---------------------|
| `start-cisco-sync.sh` | `cisco:sync --loop --interval=5` | `nohup` + PID file |
| `start-cuic-sync.sh` | `cuic:sync-realtime --loop --interval=15` + `cuic:sync --loop --interval=300` | `nohup` + PID file |
| `worker-cron.sh` | `cisco:sync --loop` (con guardia de horario) | Shell loop |

> **⚠️ Riesgo:** Los scripts usan `nohup` + archivos PID. No hay supervisord/systemd para auto-recovery en caso de crash. Ver `docs/ARCHITECTURE.md` §8.5 para la matriz completa de riesgos.

---

## Mapa de Acciones Subyacentes

| Comando | Action principal | DTO/Modelo de salida |
|---------|-----------------|---------------------|
| `cisco:sync` | `SyncFinesseAgentStatesAction` | `AgentRealtimeState` (UNLOGGED) |
| `finesse:sync` | `SyncFinesseUsersAction` | `Employee` (name update) |
| `cuic:sync` | `SyncCuicDataAction` | `CallRecord`, `AgentCallPerformance`, `AgentStateTransition`, `ChatRecord` |
| `cuic:sync-realtime` | `SyncCsqRealtimeStatsAction` | `CsqRealtimeStat` |
| `cuic:backfill` | `SyncCuicDataAction` (en bucle) | Mismos que `cuic:sync` |
| `uccx:import` / `uccx:auto-import` | `ImportUccxInboundAction`, `ImportUccxTransitionsAction`, `ImportUccxPerformanceAction`, `ImportUccxChatAction` | Mismos que `cuic:sync` |

---

## Configuración

Toda la configuración de los endpoints Cisco está centralizada en:

```
config/contact-center.php
```

Variables de entorno requeridas:
```
UCCX_URL_BASE, UCCX_USERNAME, UCCX_PASSWORD
CUIC_URL, CUIC_USERNAME, CUIC_PASSWORD
UCCX_DATA_PATH (para importación CSV)
```

Reportes CUIC pre-configurados (7 tipos con UUIDs específicos del entorno CSS Panamá):

| Reporte | UUID | Propósito |
|---------|------|-----------|
| `agent_state_transitions` | `DAB08939...` | Transiciones de estado de agente |
| `agent_detail` | `8827A5EA...` | Detalle de agente por período |
| `agent_performance_detail` | `E33ED4BF...` | Rendimiento de agente |
| `agent_csq_detail` | `E3294184...` | Detalle por cola |
| `voice_csq_summary` | `F42547F5...` | Resumen de colas de voz |
| `agent_chat_detail` | `E35B32C7...` | Detalle de chat |
| `agent_realtime_detail` | `5D411E70...` | Snapshot en tiempo real |

---

## Referencias en el Código

| Componente | Ubicación |
|------------|-----------|
| Cliente HTTP Finesse | `app/Shared/Infrastructure/Cisco/CiscoFinesseClient.php` |
| Servicio CUIC | `app/Modules/ConnectModule/Services/CuicReportService.php` |
| Sync Finesse (Job legacy) | `app/Jobs/CiscoSync.php` |
| Comandos globales | `app/Console/Commands/CiscoSyncCommand.php`, `TestCiscoConnection.php` |
| Comandos del módulo Connect | `app/Modules/ConnectModule/Console/Commands/` |
| Configuración | `config/contact-center.php` |
| Scripts daemon | `start-cisco-sync.sh`, `start-cuic-sync.sh`, `worker-cron.sh` |
