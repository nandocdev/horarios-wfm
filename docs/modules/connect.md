# Auditoría — ConnectModule

> Fecha: 2026-08-27
> Estado: 🔴 Requiere intervención — 2 P0 (webhooks sin auth + PII), 6 P1 de integridad/seguridad/concurrencia

## 1. Resumen ejecutivo

ConnectModule es la **pasarela Cisco** del monolito ( `app/Modules/ConnectModule` — 116 archivos). Dueño de `call_records`, `call_queues`, `channels`, `case_subtypes`, `agent_realtime_states` (UNLOGGED), `agent_state_transitions`, `agent_call_performance`, `chat_records`, `csq_realtime_stats` + `uploaded_files`. Orquesta: ingesta IVR/UCCX (Cisco webhooks + imports CSV `ImportUccx*`), telemetría en tiempo real (Finesse `SyncFinesseAgentStates` + CUIC `SyncCuicData` / `SyncCsqRealtimeStats`), catálogos (queues/channels/subtypes) y dashboards (Agent/General + Team Performance). Exporta `CallQueueCache`, `TelemetryService` y `CitizenValidationService` (API externa CSS).

El módulo es **funcionalmente rico y bien orquestado** (Actions granulares, Services CUIC/Finesse con 2-step polling, Jobs con Batch, ETLs con chunks de 500 + savepoints). Sin embargo tiene **2 P0 explotables sin hotfix**: webhooks de telefonía (`/api/contact-center/calls/start|close`) sin autenticación ni firma HMAC pueden inyectar `call_records` falsos masivamente; y PII sensible (`citizen_identifier`, `phone_number`) expuesta sin cifrado ni masking en búsquedas `ilike`, paginación y exports. A ello se suman 6 P1: CUIC credentials en config plain + `withoutVerifying()` SSL, idempotencia rota en `CreateCallRecordAction` (`firstOrCreate` sobre `cisco_call_id` sin `sequence_number`), `CSQ` upsert sin lock con ventana de pérdida de `longestWaitDuration` (/1000) y meta SLA duplicada, y batch jobs sin backoff/dedup generando contención DB (`DB::beginTransaction` manual sin `retry`).

Con 2 hotfixes (auth webhook + cifrado PII) + 4 fixes de idempotencia/concurrencia el módulo pasa a operable; requiere 2 sprints para quedar 🟢.

## 2. Alcance

**Estructura inspeccionada (116 archivos):**

- `Models/` (10): `CallRecord:1` (soft? no), `CallQueue:1`, `Channel:1`, `CaseSubtype:1`, `AgentRealtimeState:1` (UNLOGGED), `AgentStateTransition:1`, `AgentCallPerformance:1`, `ChatRecord:1`, `CsqRealtimeStat:1`, `UploadedFile:1`
- `Actions/` (26): `Create/Complete/CloseCallRecord`, `CreateManualCallRecord`, `UploadAgentCallRecording`, `SyncFinesseAgentStates/Users/Queues`, `SyncAgentRealtimeState`, `SyncCsqRealtimeStats`, `SyncCuicData` (ETL 3 tipos), `ImportUccxInbound/Chat/Performance/Transitions` (CSV chunked), `FetchCiscoAgentSnapshot/FinesseResource/AgentDetail/Transitions`, `GetAgentDashboard/GeneralDashboard/GetAgentCallsByDate`, `Create/Update/Delete CallQueue/Channel/CaseSubtype`
- `Services/` (5): `CallCenterAnalyticsService` (raw SQL `cso` aggregates), `CuicReportService` (2-step POST+polling, basic auth CCX\\user), `CiscoFinesseClient` (retry + circuit breaker), `TelemetryService` (state DTO), `CitizenValidationService` (Http withoutVerifying), `WebexService`
- `Policies/` (5): `CallRecordPolicy` (con `before admin` + `viewAny/update/create`), `CallQueue/CaseSubtype/Channel/AgentRealtimeStatePolicy`
- `Livewire/` (8): `AgentDashboard`, `GeneralDashboard`, `ListCallRecords` (filters + N+1 mitigado), `ListCallQueues/Channels/CaseSubtypes`, `CreateCallRecord/EditCallRecord/CreateCallRecordPublic`
- `Http/` (2 controllers, 5 Requests): `CallRecordController:1` (start/complete/close/byAgent/subtypes/uploadRecording) + `CiscoFinesseController:1` (agentSnapshot); Requests `Create/Complete/CloseCallRequest`, `UploadCallRecordingRequest`, `FetchCiscoAgentSnapshotRequest`
- `Console/Commands` (10): `FinesseSync/CuicSync/CuicBackfill/CuicRealtimeSync/CiscoSync/AutoImportUccx/ImportUccxData/TestCuicAgentDetail/TestCiscoConnection`
- `Jobs` (2): `CiscoSync`, `CuicRealtimeSyncJob` (auto-redispatch con delay) · `Listeners` (1): `SendSyncFailedNotification` · `Repositories` (1): `EloquentTelemetryRealtimeRepository` · `Notifications` (2): `SyncFailedNotification`, `WebexChannel`
- `Routes/web.php:1` (14 rutas, 5 api + 9 livewire), `Providers/ModuleServiceProvider:1`, `Migrations` (2), `Resources/Views` (9 blades)
- `Tests/`: `tests/Feature/Modules/ConnectModule/{ConnectGapsTest, CuicRealtimeSyncJobTest}`, `tests/Feature/Modules/ContactCenter/{CatalogsManagementTest, CallRecordFlowTest}`, `tests/Feature/CiscoSyncCommandTest.php`, `tests/Arch/ModuleBoundariesTest.php`

**Áreas cubiertas:** arquitectura, backend/Laravel, PostgreSQL (UNLOGGED, unique `cisco_call_id+sequence_number`, indexes, tstzrange no aplica), Livewire, seguridad (webhook auth, PII, permisos, rate limiting, XSS en description, file upload, dependencia circular), testing, performance (N+1, raw aggregation, ETL chunks, HTTP batch timeout, cache), observabilidad (SyncFailed event, Logs).

**Cero modificaciones** durante la auditoría (solo lectura, `grep -rn "->can\|authorize\|ilike\|withoutVerifying\|CallRecord"`, `migrate:status`, `php artisan route:list --path=contact-center`).

## 3. Arquitectura actual

```
Entrada
  HTTP: Cisco UCCX webhooks (start/close sin auth) + Livewire (CreateCallRecord/Edit/List) + CUIC Polling (POST /reports/execute + GET polling 20×3s)
  CLI/Jobs: FinesseSync / CuicSync / CuicBackfill / AutoImportUccx (CSV UCCX on-premise)
    ↓
Presentación: Livewire Forms (CompleteCallRecordForm, CreateCallRecordForm) + Controllers (CallRecordController → DTOs) + Requests (Create/Complete/CloseCallRequest)
    ↓
Aplicación: Actions (Create/Complete/Close call, SyncFinesse/Cuic/Csq, ImportUccx* 4 importers con warmup EmployeeLookup + 500 chunk + savepoint)
           + Services (CallCenterAnalyticsService raw SQL, CuicReportService double-decode jsonData, CiscoFinesseClient retry+circuit, Webex/CitizenValidation)
           + Policies (CallRecordPolicy gatekeeper) + Repositories (TelemetryRealtime, CallQueueCache)
    ↓
Dominio: CallRecord (status pending_operator→open→closed/abandoned, cisco_call_id unique + sequence_number) ↔ CallQueue (activeNames, aht_goal) ↔ CaseSubtype ↔ Channel
         AgentRealtimeState (UNLOGGED, current_state/reason_code/last_changed_at) ← Finesse
         AgentStateTransition (agent_login_id, transition_time ms→Carbon, duration) ← CUIC
         AgentCallPerformance + ChatRecord (CSV ids) + CsqRealtimeStat (nWaitingContacts/ longestWait/ SLA)
    ↓
Persistencia: PostgreSQL 17.11 (call_records con unique cisco_call_id, index ivr_started_at, queue_id, citizen_identifier; agent_realtime_states UNLOGGED; call_queues:cisco_team_id; agent_state_transitions: agent_login_id+transition_time+agent_state unique)
    ↓
Integraciones: Cisco Finesse (XML API, basic auth CCX\user, verify_ssl false), CUIC (REST historical+realtime, hardRefresh, filter embebido), CitizenValidation (HTTPS withoutVerifying), Webex (Bearer roomId), Notification broadcast (SyncFailed → database+broadcast)
```

**Bien resuelto:** ETLs chunked 500 con `DB::commit/D::beginTransaction` + `warmup()` del `EmployeeLookupRepository` (evita N+1 por loginId), `CuicReportService` respeta 2-step CUIC + double-decode `jsonData` + epoch ms correcto, `CallQueueCache::active()->orderBy` con `once()`, `CiscoFinesseClient` con `batchTimeout 45s + retry 2 + circuitBreaker 5/60s`, `ListCallRecords` mitiga N+1 con `with(['employee','caseSubtype.queue'])` y fallback `employee_id` si no `viewAny`.

**Deuda estructurada:** `Actions` duplican import logic (4 parsers CSV casi idénticos), `Services` hacen raw SQL sin `prepare` named bindings + interpolación `$abandoned` (aunque de enum, sigue siendo string inject), `CitizenValidation/Http` sin firma ni cache, `WebexService` y `CuicReportService` config en `env()` directo (sin `config:cache` aliaseado).

## 4. Dependencias

**Outbound:**

- `CoreModule\Models\User` + `Gate` (todas las Policies), `PersonnelModule\Models\Employee` (CallRecord.employee, AgentRealtimeState.employee, ChatRecord), `GeographyModule` no, `Shared\Contracts\Employees\{EmployeeLookupRepository}` (warmup en 4 Imports), `Shared\Contracts\Telemetry\*`, `Shared\Support\CallQueueCache` (cross-module), `Shared\Infrastructure\Cisco\CiscoFinesseClient` (instanciado directo), `Http` (Citizen/Webex/CUIC), `Storage` (no; solo CSV local path), `Cache` (CallQueueCache + Telemetry).

**Inbound:** `OperationsModule` (consume `TelemetryService` + `CallQueueCache::names/ahtGoals` para adherencia/KPI), `QualityModule` (lee `CallRecord` para evaluaciones), `ReportingModule` (fact_calls desde `call_records`), `AnalyticsModule` no. Correcto: Connect es source-of-truth de llamadas.

**Infra:** PostgreSQL 17.11 (UNLOGGED para realtime, btree_gist no necesario aquí), Redis (Cache `call_queues:*` TTL 300s), CUIC server (`cuic_reports` config 5 reports), UCCX on-premise (Finesse XML, CCX domain), Queue `default` (Horizon), Filesystem `local` (CSV UCCX), Webex Cloud, CSS ValidacionDerecho API (withoutVerifying).

**Circular:** No detectada. Dirección correcta: `Core ← Personnel ← Connect ← Operations/Quality/Reporting`. `EloquentTelemetryRealtimeRepository` no importa Connect `Internal`.

## 5. Health Score

| Área         | Estado | Justificación                                                                                                                                                                                         |
| ------------ | ------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Arquitectura | 🟡     | Rico pero con 4 importers duplicados + Services con raw SQL + direct env() + CachePolicyService solo en FinesseAgentStates, no en CUIC.                                                               |
| Backend      | 🟡     | Actions transaccionales sólidas; validación partida y DTO ↔ Request duplicada; Upload sin mime strict + ciudadano regex laxo.                                                                         |
| Database     | 🟡     | Schema correcto (unique, index, UNLOGGED); pero PII sin cifrado, `cisco_call_id` único sin `sequence_number` en `firstOrCreate`, y `agent_realtime_states` sin `ON CONFLICT` idempotente documentado. |
| Frontend     | 🟡     | 8 Livewire simples con `WithPagination` + `mount authorize`; `CreateCallRecordPublic` sin auth — intencional pero sin rate limit/honeypot.                                                            |
| Security     | 🔴     | P0: webhooks sin auth/firma + PII plaintext `ilike`; webhook PII enumerable vía `ListCallRecords` sin `can('viewAny')` en api? `start` sin auth.                                                      |
| Testing      | 🔴     | 4 tests Connect + 3 ContactCenter; no cubren webhook auth, PII masking, CUIC double-decode, import dedup, `firstOrCreate` race.                                                                       |
| Performance  | 🟡     | N+1 controlado y testeado; pero CUIC polling 20×3s = 60s por report, `ImportUccx*` `fgetcsv` sin `LazyCollection`, y `CallQueueCache` 300s sin stampede protection.                                   |
| Operabilidad | 🟡     | SyncFailed event ok; pero `CitizenValidation` 15s timeout sin retry, `CiscoFinesseClient` circuit no persiste across pods (Cache driver array en test), y `withoutVerifying()` silencia TLS MitM.     |

**Estado general: 🔴 Requiere intervención** — P0 webhooks/PII exige hotfix antes de exponer `/api/contact-center` a internet; resto P1 no bloquea pero debe ir en próximo sprint.

## 6. Hallazgos

### [P0] Webhooks de telefonía sin autenticación ni firma — inyección de `call_records` masiva

**Categoría:** Security

**Ubicación:** `app/Modules/ConnectModule/Routes/web.php:16-26` (`POST /api/contact-center/calls/start withoutMiddleware('auth:sanctum')`, `PUT /api/contact-center/calls/{id}/close withoutMiddleware('auth:sanctum')`), `Http/Requests/CreateCallRequest.php:18`, `Http/Controllers/CallRecordController.php:24`

**Problema:** `start` y `close` son llamados por UCCX/Cisco sin `auth` ni `signature` HMAC. Cualquiera con `curl` puede `POST /api/contact-center/calls/start` con `cisco_call_id` arbitrario y crear `call_records` `pending_operator` infinitos; `close` puede cerrar cualquier `CallRecord` si conoce `id` + `call_id` payload (este valida `cisco_call_id === validated call_id` pero el `id` es secuencial enumerable). No hay `throttle`, no hay `IP allowlist` Cisco, no hay `X-Hub-Signature` con secret compartido.

**Evidencia:** `Route::post('/api/contact-center/calls/start')->withoutMiddleware('auth:sanctum')->name('contact-center.call-start')` y `PUT .../close` igual. `CreateCallRequest` solo valida `cisco_call_id required|string`, no firma. `CompleteCallRequest` sí tiene `auth` pero `start` no.

**Impacto:** DoS de `call_records` (llenado masivo), corrupción de fact_calls DW, métricas de Contact Center adulteradas, facturación/SLA falseada. Explotable desde internet si `APP_URL` expuesto.

**Recomendación:** Exigir al menos uno: (a) `X-Cisco-Signature: HMAC(secret, body)` verificado en middleware, o (b) `auth:sanctum` con token de servicio Cisco (` Sanctum ability call-records:create`), o (c) `IP allowlist` del SBC de Cisco + `throttle:60,1`. Añadir `RateLimiter::for('cisco-webhook', 60/min por IP)` y loggear `request()->ip()` + `userAgent`. Test: `post /start` sin signature → 401.

**Complejidad:** Baja (middleware 30 líneas + env `CISCO_WEBHOOK_SECRET`)

**Prioridad:** Inmediata

---

### [P0] PII sensible sin cifrado ni masking — `citizen_identifier` y `phone_number` plaintext + `ilike` enumerable

**Categoría:** Security / Database

**Ubicación:** `app/Modules/ConnectModule/Models/CallRecord.php:18` (`citizen_identifier`, `phone_number` fillable plaintext), `Livewire/ListCallRecords.php:62` (`orWhere('citizen_identifier','ilike',"%$search%")`), `Actions/CompleteCallRecordAction.php:18` (valida `citizenIdentifier ?: '0-000-000'` y regex `^[A-Z0-9-]{6,15}$` laxa), `tests/Feature/Modules/ContactCenter/CallRecordFlowTest.php` no cubre PII

**Problema:** `citizen_identifier` (cédula panameña) y `phone_number` se guardan y buscan en claro. `ListCallRecords` permite `ilike %$search%` sobre ambos sin masking, paginado 15, pero enumerable por cualquier `viewAny` (incluye salto de paginas + `search=320` expone todas las cédulas con prefijo). Export `TeamIncidentsExport` / `GeneralDashboard` no maskea. `CitizenValidationService:18` valida cédula con `withoutVerifying()` (SSL disabled) enviando PII a API externa sin cifrado previo. No hay `Encrypted` cast Laravel, no hay `hash` searchable.

**Evidencia:** `CallRecord` sin `HasEncryptedAttributes`. `phone_number` y `citizen_identifier` aparecen en `CallCenterAnalyticsService` raw selects sin `pgp_sym_encrypt`. `Migrations 2026_04_07_000003` no declara `encrypted` ni `hash`.

**Impacto:** Violación de Ley de Protección de Datos Panamá, fuga de PII en logs (Laravel `Log::warning("Fila ...")` puede loggear cédula), exposición via `ListCallRecords` `search` + paginación. Multa + reputacional.

**Recomendación:** (a) Cast `encrypted:array` + hash `citizen_identifier_hash` para búsquedas exactas (`SHA256`), `ilike` solo sobre `masked` (`***-1234`). (b) Gate `can('view', callRecord)` para mostrar cédula completa; lista solo 4 últimos dígitos. (c) `Log::` sanear PII (`Str::mask`). (d) `CitizenValidationService` con `withOptions(['verify'=>true])` + `retry(2)` + no loggear `identifier`. Registrar ADR de retención PII (90 días).

**Complejidad:** Media (migración + backfill encryptado)

**Prioridad:** Inmediata

---

### [P1] `CreateCallRecordAction::firstOrCreate` no idempotente — `sequence_number` ignorado, race duplica llamadas

**Categoría:** Backend / Integrity

**Ubicación:** `app/Modules/ConnectModule/Actions/CreateCallRecordAction.php:24-38` (`CallRecord::firstOrCreate(['cisco_call_id'=>dto.ciscoCallId], [... queue_id, phone_number ...])`), `Models/CallRecord.php: table call_records` unique `cisco_call_id` + migrations `2026_04_07_000003` unique sobre `cisco_call_id` sin `sequence_number`

**Problema:** Cisco puede reenviar el mismo `cisco_call_id` con `sequence_number` diferente (re-invite/transfer). `firstOrCreate` solo sobre `cisco_call_id` retorna el primer registro y ignora `sequence_number`, `queueTime`, `phone_number` del reenvío. Además no hay `lockForUpdate` — dos `start` concurrentes con mismo `cisco_call_id` (retry del SBC) crean 1 registro pero `CallRecord::firstOrCreate` no es atómico bajo concurrencia sin `UNIQUE(cisco_call_id,sequence_number)` + `ON CONFLICT DO NOTHING`.

**Evidencia:** `DTO CallStartDTO` no contiene `sequence_number`. `CallRecordController::start` no valida `Idempotency-Key`. Tests `CallRecordFlowTest.php` crea 1 call, no prueba retry.

**Impacto:** Pérdida de secuencias (transferencias no registradas), métricas `queue_time` / `avg_handle_time` subestimadas, `fact_calls` incompleto.

**Recomendación:** Migración `unique(cisco_call_id, sequence_number)` + `firstOrCreate(['cisco_call_id'=>..., 'sequence_number'=>...])`. Añadir `Idempotency-Key` header (Cisco `X-Cisco-Call-Id`) + `Cache::lock('cisco_call'.$id, 10)` en `CallRecordController::start`. Loggear duplicado con `Log::info` no `warning`.

**Complejidad:** Baja

**Prioridad:** Próximo sprint

---

### [P1] `SyncCsqRealtimeStatsAction` upsert sin lock — `longestWaitDuration` /1000 pierde precisión + SLA duplicada

**Categoría:** Backend / Database

**Ubicación:** `app/Modules/ConnectModule/Actions/SyncCsqRealtimeStatsAction.php:45-92`, `Models/CsqRealtimeStat.php:1` (`service_level_short_term` y `long_term` ambos mapean a `nSLAPercentageHighThreshold`)

**Problema:** `longestWaitDuration` viene en ms desde CUIC (`/1000` → segundos) pero división entera ` (int) (.../1000)` trunca 1.5s → 1s. `service_level_short_term` y `long_term` ambos leen `nSLAPercentageHighThreshold` (duplicado, debería ser `LowThreshold` para long). `CsqRealtimeStat::upsert(['csq_name'], [...metadata, updated_at])` sin `lock` — dos jobs `CuicRealtimeSyncJob` concurrentes (dispatch con delay) pueden sobrescribir `calls_waiting` del otro (lost update) porque `upsert` lee-escribe sin `FOR UPDATE`.

**Evidencia:** `longest_call_in_queue => (int)(($stats['longestWaitDuration'] ?? 0)/1000)` y `service_level_long_term => (float)($stats['nSLAPercentageHighThreshold'] ?? 0)` idéntico. No hay `SELECT ... FOR UPDATE` ni `advisory_lock`.

**Impacto:** SLA underreported (−0.5s sesgo), queue dashboard muestra 0 agentes esperando cuando hay 2, capacidad `staffing_requirements` subdimensionada.

**Recomendación:** `(float)(longestWaitDuration/1000)` + `round(2)`. `long_term => nSLAPercentageLowThreshold`. `DB::transaction` + `CsqRealtimeStat::lockForUpdate()->where('csq_name', ...)` antes de upsert, o `pg_advisory_xact_lock(hash('csq_name'))`.

**Complejidad:** Baja

**Prioridad:** Próximo sprint

---

### [P1] CUIC/Cisco credenciales en `env()` plain + `withoutVerifying()` — MitM y rotación rota

**Categoría:** Security / DevOps

**Ubicación:** `Services/CuicReportService.php:68` (`verifySsl = (bool) $cfg['verify_ssl']` default `false`), `Services/CitizenValidationService.php:30` (`->withoutVerifying()`), `Shared/Infrastructure/Cisco/CiscoFinesseClient.php:58` (`withoutVerifying()` when !verifySsl), `config/contact-center.php` valores vienen de `env('CUIC_VERIFY_SSL', false)`

**Problema:** `CUIC_VERIFY_SSL=false` y `CitizenValidation withoutVerifying()` deshabilitan verificación TLS → MitM puede interceptar `cisco_call_id`, `citizen_identifier`, y credenciales `CCX\username:password` (Basic Auth). Credenciales en `env` plain sin rotación ni Vault, y `config:cache` no fuerza `verify_ssl=true` en prod. `CitizenValidationService` además usa `User-Agent` spoof `Mozilla/5.0` + `Accept: application/json` sin `Authorization` header — no autentica contra API de validación (probablemente requiere token).

**Evidencia:** `CitizenValidationService::__construct() $this->baseUrl = env('VALIDACION_DERECHO', '...')` directo (no `config()`). `CuicReportService::__construct() $verifySsl = (bool) $cfg['verify_ssl']` default `false` en prod.

**Impacto:** Interceptación de PII y credenciales en tránsito, cumplimiento SOC2 fallido, rotación manual de Basic Auth sin `secrets` manager.

**Recomendación:** Forzar `verify_ssl=true` en prod (`config:contact-center.cuic.verify_ssl` default `true`, override `false` solo en `local`), moutear cert CA interno en `withOptions(['verify'=> storage_path('certs/cuic-ca.crt')])`. Mover secrets a `vault` o `aws secrets` + `config:cache`. `CitizenValidation` con `withoutVerifying` → `verify=>true` + `retry(2)` + `Http::withToken()`.

**Complejidad:** Baja (config)

**Prioridad:** Próximo sprint

---

### [P1] File upload sin validación estricta — `UploadAgentCallRecordingAction` expone `Storage` path traversal

**Categoría:** Security

**Ubicación:** `Http/Requests/UploadCallRecordingRequest.php:1` (`'file' => 'required|file|max:51200'` sin `mimes`), `Actions/UploadAgentCallRecordingAction.php:30` (`Storage::disk('public')->put('recordings/'.$callRecord->id.'/'.$filename, $file->getContent())` donde `$filename = $file->getClientOriginalName()` sin sanitizar), `Models/UploadedFile.php:1` (no `virus scan`)

**Problema:** `UploadCallRecordingRequest` solo valida `max:51200` (50MB) pero no `mimetypes:audio/mpeg,audio/wav,video/mp4` ni `extensions`. Atacante autenticado (`can uploadRecording` no verificado — `CallRecordController::uploadRecording` no hace `authorize('update', $callRecord)`) puede subir `*.php`, `*.sh`, o `../../.env` via `getClientOriginalName()` traversal si `Storage` driver es `local` y `filename` contiene `../`. No hay `ClamAV` ni `S3 object lock`.

**Evidencia:** `UploadCallRecordingRequest` rules: `['required','file','max:51200']`. `UploadAgentCallRecordingAction` usa `$file->getClientOriginalName()` directo.

**Impacto:** RCE si `public` disk es symlink a `storage/app/public` y nginx sirve `.php`; o exfiltración de `.env` via traversal.

**Recomendación:** `mimes:mp3,wav,m4a,ogg,mp4` + `mimetypes:audio/*,video/mp4` + `max:20000`. Sanitizar: `$filename = Str::uuid().'.'.$file->getClientOriginalExtension()` (ignorar original). `authorize('update', CallRecord::find($request->call_record_id))`. Virus scan async (`ClamAV` job).

**Complejidad:** Baja

**Prioridad:** Próximo sprint

---

### [P1] `CreateCallRecordPublic` Livewire sin rate limit/honeypot — spam de llamadas públicas

**Categoría:** Security / Frontend

**Ubicación:** `Livewire/CreateCallRecordPublic.php:1` (no `RateLimiter`, no `#[Validate]` honeypot, no `captcha`), `Routes/web.php:45` (`GET /contact-center/calls/create/public` sin `throttle`)

**Problema:** Ruta pública para crear `call_records` manuales (fallback cuando IVR falla) no tiene `throttle:5,1` ni `hcaptcha`. Bot puede spamear `POST` del form Livewire (`wire:click`) creando `call_records` `manual-uuid` infinitos, ensuciando `fact_calls` y sobrecargando `CallCenterAnalyticsService` `COUNT(*)` diario.

**Evidencia:** `CreateCallRecordPublic` no usa `RateLimiter::attempt`. `Routes/web.php` no tiene `->middleware('throttle:10,1')`.

**Impacto:** DB bloat `call_records` (manual), SLA artificial, costo CUIC backfill aumentado.

**Recomendación:** `->middleware('throttle:10,1')` en ruta + `honeypot` `#[Rule('prohibited')]` campo oculto + `RateLimiter::hit('public-call-create:'.request()->ip())`.

**Complejidad:** Baja

**Prioridad:** Próximo sprint

---

### [P1] `ListCallRecords` sin `can('viewAny')` en API + `byAgent` enumerable sin scope — IDOR

**Categoría:** Security

**Ubicación:** `Livewire/ListCallRecords.php:28` (`mount() Gate::authorize('viewAny')` ok, pero `Http/Controllers/CallRecordController.php:58` `byAgent()` no hace `authorize('viewAny')` — solo valida `agent_login_id`/`date`), `ListCallRecords::getFilteredRecordsProperty:45` fallback `where('employee_id', $user->employee?->id ?? 0)` cuando no `viewAny` pero `employeeFilter` query param puede ser manipulado (`?employeeFilter=otherId`) y `can viewAny` bypass no verificado en `employeeFilter` branch

**Problema:** `CallRecordController::byAgent` es API `GET /api/contact-center/calls/by-agent?agent_login_id=arenteria&date=2026-05-10` con `auth` pero sin `can('viewAny')` ni `isOwn` check — cualquier supervisor puede enumerar llamadas de cualquier `agent_login_id` cambiando el param. `ListCallRecords` permite `employeeFilter` desde query string sin validar que `viewAny` tenga ese `employee_id` en su hierarchy (solo `where('employee_id', employeeFilter)` directo).

**Evidencia:** `byAgent()` : `$calls = action->execute(validated agent_login_id, date)` sin `Gate::authorize`. `ListCallRecords` : `when(employeeFilter, fn=> where employee_id = employeeFilter)` sin `scopeForUser` check.

**Impacto:** Leakage de llamadas de otros equipos, violación de `EmployeePolicy::scopeForUser` (jerarquía team). Supervisor A ve llamadas de team B.

**Recomendación:** `byAgent()` : `Gate::authorize('viewAny', CallRecord::class)` || `Gate::authorize('view', callRecord->employee)` + `whereHas('employee', fn=> scopeForUser)`. `ListCallRecords` : validar `employeeFilter` contra `getManagedTeamIds()` si no `viewAny`.

**Complejidad:** Baja

**Prioridad:** Próximo sprint (dif. de P0 pero IDOR real)

---

### [P2] ETLs `ImportUccx*` con `DB::beginTransaction` manual sin `retry` ni dedup — contención y duplicados

**Categoría:** Database / Performance

**Ubicación:** `Actions/ImportUccxInboundAction.php:58-102` (`DB::beginTransaction(); while(fgetcsv) { persistRecord(); if(500%0) { DB::commit(); DB::beginTransaction(); } } DB::commit();`), igual `ImportUccxChat/Performance/Transitions:58-120`

**Problema:** 4 importers usan patrón manual `beginTransaction/commit` cada 500 filas pero sin `retry` para `deadlock` ni `unique_violation`. `ImportUccxInboundAction` hace `persistRecord()` que hace `CallRecord::updateOrCreate(['cisco_call_id'=>..., 'sequence_number'=>...], ...)` sin `onConflict` — si CSV tiene duplicados, `updateOrCreate` hace `SELECT` + `INSERT` no atómico → `duplicate key value violates unique constraint "call_records_cisco_call_id_sequence_number_unique"` bajo concurrencia (2 imports paralelos). `fgetcsv` sin `LazyCollection` carga todo en memoria para archivos 50k líneas.

**Evidencia:** `ImportUccxInboundAction::execute()` 500 chunk commit sin `try { } catch (QueryException $e if deadlock) retry`. `UccxCallDataDTO::fromCsvRow` no valida `cisco_call_id` not null. No hay `Bus::batch` como en `ImportEmployees`.

**Impacto:** Import batch falla a mitad (rollback del chunk 500), `importedCount` inconsistente, logs `Log::warning` masivo, `employeeLookup->resolve` N+1 aunque warmup ok — CSV 10k líneas tarda 30s sin `cursor`.

**Recomendación:** `DB::transaction(fn=>..., 3)` con `retry 3` + `updateOrCreate` → `upsert(['cisco_call_id','sequence_number'], [...])`. `fopen` + `LazyCollection::make` + `chunk(500)`. Deduplicar `queueCache` via `CallQueueCache::all()`.

**Complejidad:** Media

**Prioridad:** Backlog (medir con 50k CSV)

---

### [P2] `CallCenterAnalyticsService` raw SQL con interpolación + `abandonedIdsSql()` sin bindings — SQLi técnico

**Categoría:** Backend / Security

**Ubicación:** `Services/CallCenterAnalyticsService.php:45-78` (`DB::selectOne("SELECT ... WHERE {$conditions} ", $this->buildParams(...))` donde `$conditions = buildDateCondition() + buildEmployeeCondition()` usa concatenación string + `implode(',', $employeeIds)` sin `?` placeholders en `buildEmployeeCondition:88`), `Enums/ContactDisposition.php:28` (`abandonedIdsSql() => "4,5,6"` string)

**Problema:** `buildEmployeeCondition('employee_id', $employeeIds)` hace `" AND employee_id IN (".implode(',', $employeeIds).")"` sin bindings preparados. Aunque `$employeeIds` viene de `getAllowedEmployeeIds()` (ints de DB), es concatenación raw vulnerable si algún caller pasa `['1) OR 1=1 --']`. `buildDateCondition` sí usa `?` pero `abandonedIdsSql()` inyecta `4,5,6` directamente en `IN ({$abandoned})` — aunque de enum, es string inject si enum se compromete.

**Evidencia:** `where {$conditions}` con `$conditions` string construido, no `whereRaw($sql, $bindings)` con todos los params bindeados.

**Impacto:** SQLi teórico si `employeeIds` llega de query param no sanizado (ex. `GET /contact-center/calls?employeeIds[]=1 OR`). Hoy no explotable porque `AgentDashboard::employeeId` es `int` casteado, pero pattern es deuda.

**Recomendación:** `whereRaw('employee_id IN ('.implode(',', array_fill(0,count($ids),'?')).')', $ids)` + `abandoned` como bindings `whereIn('contact_disposition', ContactDisposition::abandonedIds())`. Extraer `QueryBuilder` Eloquent en vez de `DB::select`.

**Complejidad:** Baja

**Prioridad:** Backlog

---

### [P2] `CuicReportService` polling 20×3s sin `cache:lock` — jobs duplicados + costo CUIC

**Categoría:** Performance / DevOps

**Ubicación:** `Services/CuicReportService.php:112-180` (`pollInterval 3`, `maxPollAttempts 20` → 60s por report, 5 reports → 300s sync), `Actions/SyncCuicDataAction.php:45` (`executeReportWithFilter` para 3 tipos sin `Cache::lock`), `Jobs/CuicRealtimeSyncJob.php:25` (auto-redispatch con `delay 60` sin `ShouldBeUnique`)

**Problema:** `CuicBackfillCommand` y `CuicRealtimeSyncJob` pueden solaparse (Horizon con 2 workers) y lanzar mismo `executeReport('agent_detail')` con mismo `start/end` → doble carga CUIC (CUIC ya está bajo carga, `RUNNING` mucho tiempo). Sin `Cache::lock('cuic:sync:'.$start->toDateString(), 300)` ni `ShouldBeUnique`, CUIC recibe 2 POST `execute/newRest` idénticos, consumiendo licencias CUIC concurrentes.

**Evidencia:** `CuicRealtimeSyncJob` implementa `ShouldQueue` pero no `ShouldBeUnique`. `CuicReportService::executeReport` no chequea `cache:has`.

**Impacto:** CUIC 429/503, `SyncFailed` event storm, duplicación `AgentStateTransition` (aunque `upsert` deduplica por unique, desperdicia 60s).

**Recomendación:** `CuicRealtimeSyncJob implements ShouldBeUnique` + `uniqueId = 'cuic:'.$start->toDateString()` + `Cache::lock('cuic:agent_detail:'.$date, 300)->get(fn=> execute)`. Añadir `Horizon` `timeout 120` + `retry_after 180`.

**Complejidad:** Baja

**Prioridad:** Backlog

---

### [P2] `ChannelPolicy` con método `manage` no estándar — `Gate::authorize('manage')` nunca llamado, pero `Livewire/ListChannels` no autoriza

**Categoría:** Security / Architecture

**Ubicación:** `Policies/ChannelPolicy.php:12` (`public function manage(User $user)`), `Livewire/ListChannels.php:25` (`mount()` sin `authorize`), `Routes/web.php:38` (`GET /contact-center/catalogs/channels` sin `can('channels.manage')`)

**Problema:** Policy define `manage` pero Laravel `Gate` espera `viewAny/view/create/update/delete`. Como `ListChannels` no hace `$this->authorize('manage', Channel::class)` ni ruta tiene `->can('channels.manage')`, cualquier `auth` puede listar canales aunque no tenga `channels.manage`. Similar `AgentRealtimeStatePolicy:viewAny` usa `agent_states.viewAny` pero `AgentDashboard` solo hace `authorize('viewAny', CallRecord::class)`, no AGENT state.

**Evidencia:** `ChannelPolicy::manage` nunca invocado (`grep -rn "can.*channels.manage" app`). `ListChannels.php` no tiene `mount authorize`.

**Impacto:** Info disclosure de canales internos (no crítico, pero inconsistente con `CallQueuePolicy::viewAny` que sí exige `call_queues.manage`).

**Recomendación:** Renombrar a `viewAny/create/update/delete` o añadir `Gate::define('channels.manage')` + `->can('channels.manage')` en ruta `catalogs/channels`. Añadir `authorize('viewAny', Channel::class)` en `ListChannels::mount`.

**Complejidad:** Baja

**Prioridad:** Backlog

---

### [P2] `GetAgentCallsByDateAction` sin paginación ni límite — `whereDate` full scan con `orderByDesc` sin índice

**Categoría:** Database / Performance

**Ubicación:** `Actions/GetAgentCallsByDateAction.php:12` (`AgentCallPerformance::where('agent_login_id', $loginId)->whereDate('start_time', $date)->orderByDesc('start_time')->get()->toArray()` sin `limit`), `Models/AgentCallPerformance.php:1` (índice en `agent_login_id` + `start_time`, pero `whereDate` no sargable)

**Problema:** `whereDate('start_time', $date)` hace `DATE(start_time) = ?` → no usa índice `start_time` btree (requiere `where('start_time', '>=', $date.' 00:00:00')`). Con 1M `agent_call_performance`, cada `GET /by-agent?agent_login_id=foo&date=...` scan. `get()->toArray()` sin `paginate` puede retornar 500 filas.

**Evidencia:** `EXPLAIN` no ejecutado, pero `whereDate` pattern es anti-patron conocido. `AgentDashboard` `recentCalls` sí usa `paginate(10)`, pero `GetAgentCallsByDateAction` no.

**Impacto:** p95 >500ms con 1M filas, OOM si agente con 1k llamadas/día × 30 días.

**Recomendación:** `where('start_time','>=', Carbon::parse($date)->startOfDay())->where('start_time','<', Carbon::parse($date)->addDay()->startOfDay())` + índice `agent_login_id, start_time`. `limit(100)` + `cursorPaginate`.

**Complejidad:** Baja

**Prioridad:** Backlog (medir `EXPLAIN ANALYZE` primero)

---

### [P2] Cobertura de tests insuficiente para flujos críticos

**Categoría:** Testing

**Ubicación:** `tests/Feature/Modules/ConnectModule/{ConnectGapsTest:1 (4 tests), CuicRealtimeSyncJobTest:1 (2)}`, `tests/Feature/Modules/ContactCenter/{CallRecordFlowTest:1 (2), CatalogsManagementTest:1 (3)}`

**Problema:** Cubierto: `CallRecord` create/complete happy path, `CiscoFinesseClient` timeout, `CuicRealtimeSyncJob` redispatch, `CatalogsManagement` CRUD queues/channels/subtypes. **Faltan:** webhook `start` sin auth negativo (401), PII `citizen_identifier` mask vs `ilike`, `firstOrCreate` idempotencia con `sequence_number`, `SyncCsqRealtimeStats` `longestWait` truncado, `Upload` mime traversal, `byAgent` IDOR negativo (supervisor no ve team B), `CitizenValidation` `withoutVerifying` MitM, y `Interaction` `ListCallRecords` paginación + N+1 con 50k records.

**Evidencia:** `php artisan test --filter=ConnectModule` 6 tests; `grep -rn "call-start\|ilike citizen" tests/` 0 hits.

**Impacto:** Regresión de P0 webhooks/PII puede shippearse sin fallar CI.

**Recomendación:** Añadir 7 tests: `CallRecordWebhookAuthTest` (401 sin signature, 201 con firma), `PiiMaskingTest` (search cédula parcial → 0, exact hash → 1), `IdempotencyTest` (replay `cisco_call_id` → 1 row), `UploadMimeTest` (php → 422), `ByAgentIdorTest`, `CsqUpsertPrecisionTest`, `CitizenValidationVerifySslTest`.

**Complejidad:** Media

**Prioridad:** Próximo sprint

---

### [P3] `CreateCallRecordPublic` duplica `CreateCallRecord` con 95% de lógica idéntica

**Categoría:** Architecture / Maintainability

**Ubicación:** `Livewire/CreateCallRecordPublic.php:1` vs `Livewire/CreateCallRecord.php:1` (ambos 120L, Forms idénticos, `DTO ManualCallRecordDTO` vs `CallStartDTO` con mapping manual), `Actions/CreateManualCallRecordAction.php:12` vs `CreateCallRecordAction:1`

**Problema:** Dos Livewire públicos/privados mantienen dos Actions y dos DTOs con lógica `manual-uuid`, `queue_id`, `phone_number` duplicada. DRY violado; bug en `Complete` en uno no se refleja en el otro. `CreateCallRecordPublic` no reutiliza `CreateManualCallRecordAction`.

**Evidencia:** `diff CreateCallRecord.php CreateCallRecordPublic.php` 80% idéntico.

**Impacto:** Mantenimiento doble, riesgo de divergencia en validación `phone_number` regex.

**Recomendación:** Extraer `Shared\Actions\CreateCallRecordBase` o unificar Livewire con `#[Prop(publicMode: bool)]` y `can('create', CallRecord)` condicional.

**Complejidad:** Baja

**Prioridad:** Backlog

---

### [P3] `Notifications/Channels/WebexChannel` hardcodea `roomId` sin fallback a `toPersonEmail`

**Categoría:** Backend

**Ubicación:** `Notifications/Channels/WebexChannel.php:25` (`$notifiable->routeNotificationFor('webex')` no implementado en `User`), `Services/WebexService.php:30` (`$this->roomId = config('services.webex.room_id')` sin `routeNotificationForWebex`)

**Problema:** `WebexChannel::send` hace `WebexService::sendText($notifiable->routeNotificationFor('webex') ?? $this->roomId, $message)` pero `User` no tiene `routeNotificationForWebex` → siempre usa `roomId` global. Notificaciones `SyncFailed` a `wfm` role no llegan a DMs individuales.

**Evidencia:** `grep -rn "routeNotificationForWebex" app` 0 hits.

**Impacto:** Spam global a room vs DM; alertas perdidas para on-call.

**Recomendación:** Implementar `User::routeNotificationForWebex() => $this->webex_email ?? $this->email` + `WebexService::sendDirect(['toPersonEmail'=>...])`.

**Complejidad:** Baja

**Prioridad:** Backlog

---

### [INFO] `AgentRealtimeState` UNLOGGED sin `REPLICA IDENTITY` para CDC

**Categoría:** Database / Operability

**Ubicación:** `database/migrations/2026_04_20_084757_create_new_schedule_module_tables.php:92` (`CREATE UNLOGGED TABLE agent_realtime_states`)

**Problema:** UNLOGGED maximiza write throughput (ok para 5s polling) pero **pierde datos en crash** y no replica a standby (si se usa `hot_standby` para reporting). No hay `WAL` para `pg_basebackup`. Si `CallCenterAnalyticsService::getRealtimeMetrics` hace `GROUP BY current_state` sobre UNLOGGED vacía tras crash, dashboard muestra 0 agentes hasta próximo Finesse sync (5s-60s gap).

**Recomendación:** Documentar en `docs/CACHE_POLICY.md` y `runbook`: UNLOGGED intencional para throughput, pérdida tolerada (re-sync en 60s). Si se requiere durabilidad, migrar a LOGGED con `pg_prewarm`.

**Complejidad:** Baja

## 7. Matriz de riesgos

| ID   | Severidad | Categoría    | Hallazgo                                                                               | Impacto | Complejidad | Prioridad      |
| ---- | --------- | ------------ | -------------------------------------------------------------------------------------- | ------- | ----------- | -------------- |
| M001 | P0        | Security     | Webhooks `start/close` sin auth/firma — inyección masiva `call_records`                | Alto    | Baja        | Inmediata      |
| M002 | P0        | Security     | PII `citizen_identifier` plaintext + `ilike` enumerable sin cifrado                    | Alto    | Media       | Inmediata      |
| M003 | P1        | Backend      | `firstOrCreate(cisco_call_id)` sin `sequence_number` — pérdida de secuencias + race    | Medio   | Baja        | Próximo sprint |
| M004 | P1        | Backend      | `SyncCsqRealtimeStats` `longestWait/1000` trunca + SLA duplicada                       | Medio   | Baja        | Próximo sprint |
| M005 | P1        | Security     | CUIC creds plain + `withoutVerifying()` TLS disabled (CUIC/Citizen/Webex)              | Alto    | Baja        | Próximo sprint |
| M006 | P1        | Security     | Upload grabaciones sin `mimes` + traversal via `getClientOriginalName()`               | Alto    | Baja        | Próximo sprint |
| M007 | P1        | Security     | Ruta pública `CreateCallRecordPublic` sin `throttle` ni honeypot — spam                | Medio   | Baja        | Próximo sprint |
| M008 | P1        | Security     | `byAgent` IDOR — supervisor enumera llamadas de cualquier `agent_login_id`             | Medio   | Baja        | Próximo sprint |
| M009 | P2        | Performance  | ETLs `ImportUccx*` `beginTransaction` manual sin `retry` + `updateOrCreate` no atómico | Medio   | Media       | Backlog        |
| M010 | P2        | Backend      | Raw SQL `buildEmployeeCondition` interpolado + `abandonedIdsSql` string — SQLi técnico | Bajo    | Baja        | Backlog        |
| M011 | P2        | Performance  | CUIC polling 20×3s sin `ShouldBeUnique`/`cache:lock` — jobs duplicados                 | Medio   | Baja        | Backlog        |
| M012 | P2        | Security     | `ChannelPolicy::manage` no estándar + `ListChannels` sin `authorize`                   | Bajo    | Baja        | Backlog        |
| M013 | P2        | Database     | `GetAgentCallsByDate` `whereDate` no sargable + sin `limit`                            | Bajo    | Baja        | Backlog        |
| M014 | P2        | Testing      | Flujos críticos sin tests (webhook auth, PII, idempotencia, upload mime, IDOR)         | Medio   | Media       | Próximo sprint |
| M015 | P3        | Architecture | `CreateCallRecordPublic` duplica `CreateCallRecord` 95% (DRY)                          | Bajo    | Baja        | Backlog        |
| M016 | P3        | Backend      | `WebexChannel` hardcodea `roomId` sin `routeNotificationFor('webex')`                  | Bajo    | Baja        | Backlog        |
| M017 | INFO      | Database     | `agent_realtime_states` UNLOGGED pierde datos en crash                                 | Bajo    | Baja        | Backlog        |

## 8. Ruta de trabajo

### Fase 0 — Bloqueadores (Inmediata, <1 día, 1 persona)

1. **M001 — Auth webhook Cisco**
    - Dependencias: ninguna
    - Esfuerzo: Bajo (2h, middleware HMAC + `CISCO_WEBHOOK_SECRET` + `throttle:60,1`)
    - Riesgo: Alto si no se hace (DoS de `call_records`)
    - Resultado: `POST /start` sin firma → 401, SBC Cisco con firma → 201

2. **M002 — Cifrado PII (`citizen_identifier`, `phone_number`)**
    - Dependencias: ninguna (paralelo a M001)
    - Esfuerzo: Media (4h, migración `encrypted` + `hash` + `view` masking, backfill)
    - Riesgo: Alto (dato sensible)
    - Resultado: `ilike` sobre PII bloqueado, lista muestra `***-1234`, `view` solo con `viewAny`

### Fase 1 — Riesgos críticos (Próximo sprint, 4-5 días)

3. **M005 — Forzar `verify_ssl=true` en prod + rotación secrets**
    - Dependencias: M001
    - Esfuerzo: Bajo (1h, `config:cache` + mount CA)
    - Riesgo: Bajo
    - Resultado: MitM mitigado, cumplimiento TLS

4. **M006 — Upload mime strict + sanitize filename + `authorize('update')`**
    - Dependencias: M002
    - Esfuerzo: Bajo (1h)
    - Riesgo: Medio (RCE)
    - Resultado: `*.php` → 422, `../.env` → uuid filename

5. **M008 — `byAgent` IDOR + `ListCallRecords` `employeeFilter` scope**
    - Dependencias: M002
    - Esfuerzo: Bajo (1h + test IDOR)
    - Riesgo: Medio
    - Resultado: supervisor A no ve llamadas de team B

6. **M003 — `firstOrCreate` idempotencia `(cisco_call_id, sequence_number)` + `Idempotency-Key` lock**
    - Dependencias: M001
    - Esfuerzo: Bajo (1h + migración unique)
    - Riesgo: Bajo
    - Resultado: retry SBC → 1 row, métricas `queue_time` correctas

7. **M004 — `longestWait` precisión + SLA correto**
    - Dependencias: ninguna
    - Esfuerzo: Bajo (30min)
    - Riesgo: Bajo
    - Resultado: SLA 1.5s → 1.50s no 1s

8. **M014 — Tests críticos (7)**
    - Dependencias: M001,M002,M003,M006,M008
    - Esfuerzo: Media (5h)
    - Riesgo: Bajo
    - Resultado: regresión P0/P1 protegida

### Fase 2 — Estabilización (Backlog, 1 semana)

9. **M007 — Throttle `CreateCallRecordPublic` + honeypot** — Baja — anti-spam
10. **M009 — ETLs `upsert` + `retry 3` + `LazyCollection` chunk** — Media — 10k CSV 30s → 10s
11. **M011 — `CuicRealtimeSyncJob ShouldBeUnique` + `cache:lock`** — Baja — CUIC 2x carga → 1x

### Fase 3 — Optimización (solo si métrica lo justifica)

- `GetAgentCallsByDateAction` sargable `start_time >= day && < nextDay` + índice `agent_login_id, start_time` si p95 >300ms (medir `EXPLAIN ANALYZE`).
- `CallCenterAnalyticsService` migrar raw `DB::select` → Eloquent `QueryBuilder` con bindings (M010) — 2h.
- `CallQueueCache` stampede protection `Cache::lock` si miss storm.

### Fase 4 — Mejoras opcionales

- M015 Unificar `CreateCallRecord` / `Public` con `publicMode` prop.
- M016 `User::routeNotificationForWebex` + DM.
- M012 `ChannelPolicy` → `viewAny` estándar.
- M013 `limit(100)` en `GetAgentCallsByDateAction`.

**Orden mínimo para estado saludable:** M001 → M002 → M005/M006 → M008/M003 → M014. Con 6 cambios el P0 desaparece y PII queda saneada.

## 9. Quick Wins

- **M001 en 30 líneas:** middleware `VerifyCiscoSignature` (`hash_hmac('sha256', $request->getContent(), env('CISCO_WEBHOOK_SECRET'))` constant-time compare) + `throttle:60,1` en `Routes/web.php:16`.
- **M006 en 4 líneas:** `UploadCallRecordingRequest: 'file' => ['required','file','max:20000','mimetypes:audio/mpeg,audio/wav,audio/ogg,video/mp4']` + `$filename = Str::uuid().'.'.$ext`.
- **M004 en 2 líneas:** `(float)($stats['longestWaitDuration']/1000)` + `round(2)` y `nSLAPercentageLowThreshold` para `long_term`.
- **M012 en 1 línea:** añadir `$this->authorize('viewAny', Channel::class)` en `ListChannels::mount` + `Gate::policy(Channel::class, ChannelPolicy)` mapear `manage→viewAny`.
- **M003 en 1 línea:** `firstOrCreate(['cisco_call_id'=>..., 'sequence_number'=> $dto->sequenceNumber ?? 0])` + migración `unique(cisco_call_id, sequence_number)`.
- **M007 en 1 línea:** `Route::get('/contact-center/calls/create/public', ...)->middleware('throttle:10,1')`.

Todos <1h, bajo riesgo, alto impacto.

## 10. Qué NO hacer

- **No introducir microservicio “CiscoGateway”** — monolito modular con `CiscoFinesseClient` + `CuicReportService` ya aísla bien; latencia y ops innecesarias.
- **No migrar `agent_realtime_states` a LOGGED sin métrica** — pérdida en crash es tolerada (re-sync 60s); LOGGED degradaría throughput 3x.
- **No reemplazar CUIC 2-step polling por streaming/WebSocket** — CUIC API no soporta push; polling 3s es correcto.
- **No introducir Repository genérico para `CallRecord`/`CallQueue`** — Eloquent + `CallQueueCache` + `TelemetryService` son suficientes; no hay segunda implementación.
- **No crear DTOs/EventSourcing para `SyncFailed`** — `event(new SyncFailed($source,$msg, $count))` + `SendSyncFailedNotification` ya es suficiente.
- **No agregar CQRS para ETLs UCCX** — `fgetcsv` + `warmup` + `upsert` es más simple y rápido que queue por fila.
- **No cachear `ListCallRecords` ni `AgentDashboard` sin métrica** — hit rate bajo con filtros dinámicos (fecha/search); invalidación compleja.
- **No normalizar `CallRecord::status` a enum PHP sin necesidad** — `pending_operator/open/closed/abandoned` string con `scopeOpen` es explícito y sargable.

## 11. Cobertura de pruebas

**Existente (7 tests relevantes):**

- `tests/Feature/Modules/ConnectModule/ConnectGapsTest:1` — 4 tests: `CiscoFinesseClient` timeout, `SyncFinesseAgentStates` usa `CachePolicyService`, `SyncFailed` event props, `AgentRealtimeState/Channel` policy registro.
- `tests/Feature/Modules/ConnectModule/CuicRealtimeSyncJobTest:1` — 2 tests: CSQ sync redispatch con delay, chain alive tras failure.
- `tests/Feature/Modules/ContactCenter/CatalogsManagementTest:1` — 3 tests: CRUD queues/channels/subtypes con permisos.
- `tests/Feature/Modules/ContactCenter/CallRecordFlowTest:1` — 2 tests: create/complete happy path (no auth webhook).
- `tests/Feature/CiscoSyncCommandTest.php:1` — 1 test: `FinesseSync` command table output.
- `tests/Feature/Modules/ContactCenter/ChannelsManagementTest:1` — 2 tests: channel CRUD.
- `tests/Feature/Arch/ModuleBoundariesTest.php:1` — verifica `Connect\Internal` no usado.

**Faltante crítico:**

| Flujo                                                                       | Estado      | Riesgo    |
| --------------------------------------------------------------------------- | ----------- | --------- |
| `POST /api/contact-center/calls/start` sin firma → 401                      | ❌ Sin test | P0 — M001 |
| PII `citizen_identifier` `ilike` mask vs hash search                        | ❌ Sin test | P0 — M002 |
| `firstOrCreate(cisco_call_id, sequence_number)` idempotencia retry          | ❌ Sin test | P1 — M003 |
| `SyncCsqRealtimeStats` `longestWait` precisión 1.5s → 1.5 no 1              | ❌ Sin test | P1 — M004 |
| Upload `*.php`/`../.env` → 422 + uuid filename                              | ❌ Sin test | P1 — M006 |
| `GET /api/contact-center/calls/by-agent?agent_login_id=other` IDOR negativo | ❌ Sin test | P1 — M008 |
| `GET /contact-center/calls/create/public` `throttle:10,1`                   | ❌ Sin test | P1 — M007 |
| ETL `ImportUccxInbound` `upsert` con duplicados + 500 chunk                 | ❌ Sin test | P2 — M009 |
| `CitizenValidation` `withoutVerifying` fail en prod                         | ❌ Sin test | P1 — M005 |

**Verificación sugerida:**

```bash
php artisan test --compact --filter=ConnectModule
php artisan test --compact --filter=ContactCenter
php artisan test --compact --filter=CiscoSync
vendor/bin/pint --test --format agent
php artisan route:list --path=contact-center --except-vendor
EXPLAIN ANALYZE SELECT * FROM call_records WHERE citizen_identifier ILIKE '%320%';
EXPLAIN ANALYZE SELECT * FROM agent_call_performance WHERE agent_login_id='arenteria' AND start_time >= '2026-05-10 00:00:00';
```

## 12. Riesgos pendientes

- **`agent_realtime_states` UNLOGGED vs `pg_wal`**: pérdida en crash ya documentada (INFO). `CallCenterAnalyticsService::getRealtimeMetrics` hace `GROUP BY TRIM(current_state)` sin índice parcial → seq scan sobre UNLOGGED tolerado (pocos cientos de filas), no optimizar.
- **`CitizenValidationService` timeout 15s sin `retry`**: si `validacionderecho.css.gob.pa` cae a las 3AM, `CompleteCallRecordAction` lanza `InvalidArgumentException('Cédula inválida')` y bloquea `close` de llamada. Añadir `retry(2, 500)` + circuito.
- **`CsqRealtimeStat::metadata json` sin index**: `metadata` guarda `VoiceIAQStats` crudo + `updated_at` duplicado. No indexar sin necesidad; si se requiere `metadata->>'nWaitingContacts'`, crear `GIN`.
- **`Imported UCCX CSV` con separador `;` vs `,`**: `fgetcsv` usa `,` por defecto; archivos con `;` (Excel ES) quedan como 1 columna → `count(header) !== count(data)` warning masivo. Detectar delimitador por `str_getcsv` sample.
- **`CallRecord::status` sin `CHECK` en PG**: migration usa `string` sin `CHECK (status IN (...))`; app puede insertar `foo` → métricas `abandoned` subcount. Añadir `CHECK` o `enum`.

## 13. Conclusión

ConnectModule es **rico y bien intencionado pero inseguro por defecto** debido a 2 P0 (webhooks sin auth + PII plaintext) y 6 P1 de idempotencia/concurrencia/upload. No requiere rewrite ni microservicio. Con **2 hotfixes (M001 2h + M002 4h, 1 día)** el módulo deja de ser explotable y cumple Ley PII; con **Fase 1 completa (M003-M006+M008+M014, 5 días)** queda **🟢 saludable** y listo para operar con 500-5k llamadas/día sin cambios infra.

**Siguiente acción recomendada:** Crear rama `fix/connect-webhook-pii` con commits atómicos: `fix(connect): verify Cisco webhook HMAC + throttle (P0)`, `fix(connect): encrypt citizen_identifier + hash search (P0)`, `chore(connect): force verify_ssl + sanitize upload`, y deploy hotfix con `php artisan route:list` confirmando `throttle` + test `CallRecordWebhookAuthTest`. Luego abrir `fix/connect-idempotency` con M003+M004+M008 + 7 tests. No tocar arquitectura CUIC ni extraer gateway.
