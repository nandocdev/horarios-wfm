# Guía de Comandos Personalizados — horarios-wfm

Catálogo completo de comandos Artisan, jobs programados y scripts de Composer del proyecto.

---

## Composer Scripts

| Script        | Comando                                                                                                                                       | Propósito                                           |
| ------------- | --------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------- |
| `setup`       | `composer install && cp .env.example .env && php artisan key:generate --force && php artisan migrate --force && npm install && npm run build` | Instalación completa desde cero                     |
| `dev`         | `php artisan serve & php artisan queue:listen & php artisan pail & npm run dev`                                                               | Entorno de desarrollo (server + cola + logs + Vite) |
| `dev:uploads` | Igual que `dev` con `upload_max_filesize=20M` y `post_max_size=24M`                                                                           | Desarrollo con soporte de archivos grandes          |
| `lint`        | `pint --parallel`                                                                                                                             | Corrige automáticamente el formato PHP              |
| `lint:check`  | `pint --parallel --test`                                                                                                                      | Verifica el formato sin modificar                   |
| `test`        | `config:clear && lint:check && php artisan test --parallel --processes=8`                                                                     | Suite completa de pruebas                           |
| `ci:check`    | `@test`                                                                                                                                       | Alias para CI                                       |

---

## Cisco / Telefonía (ConnectModule)

### Sincronización CUIC

```bash
php artisan cuic:sync                               # Incremental (últimos N minutos)
php artisan cuic:sync --minutes=60                   # Última hora
php artisan cuic:sync --from=2026-07-01 --to=2026-07-28  # Rango específico
php artisan cuic:sync --loop                         # Loop continuo cada 5 min

php artisan cuic:backfill                            # Backfill histórico masivo
php artisan cuic:backfill --from=2026-01-01 --to=2026-06-30

php artisan cuic:sync-realtime                       # Métricas CSQ cada 10s
php artisan cuic:sync-realtime --loop --interval=10

php artisan cuic:test-agent-detail                   # Prueba reporte agent_detail
```

### Sincronización Finesse

```bash
php artisan finesse:sync                             # Nombres de agentes
php artisan finesse:sync-queues                      # Colas (CSQ) desde Finesse API
```

### Importación UCCX

```bash
php artisan uccx:auto-import                         # Barrido automático (vía Schedule)
php artisan uccx:import /ruta/al/archivo.csv         # Importación manual de CSV
php artisan uccx:import --all                        # Importar todos los archivos disponibles
```

### Pruebas de conectividad

```bash
php artisan cisco:sync                               # Sincroniza estados desde Finesse
php artisan cisco:sync --loop                        # Loop continuo
php artisan cisco:test                               # Prueba conexión con Finesse API
php artisan cisco:test 123                           # Prueba con ID de agente específico
```

---

## Operaciones (OperationsModule)

### Reconciliación de asistencia

```bash
php artisan operations:reconcile-attendance                          # Hoy
php artisan operations:reconcile-attendance 2026-07-28               # Fecha específica
```

Genera incidentes de tardanza/ausencia对比ando programación vs estados reales.
Schedule: `->dailyAt('03:00')`

### Alertas

```bash
php artisan alerts:evaluate                            # Evalúa todas las reglas activas
php artisan alerts:seed-rules                          # Crea/actualiza reglas predeterminadas
```

Schedule: `->everyMinute()`

### Métricas de intervalo (Sprint 2 — Upgrade 0.2)

**Job** (no es comando directo, se ejecuta vía Schedule):

```php
AggregateIntervalMetricsJob    # Procesa agent_interval_metrics cada 15 min
```

Schedule: `->everyFifteenMinutes()` en cola `wfm-heavy`.

Procesa todos los empleados activos, calcula duraciones de estado por intervalo de 15 minutos, occupancy, utilization y adherence.

---

## Planificación WFM (WfmModule)

### Reportes diarios

```bash
php artisan wfm:calculate-daily-reports               # Calcula daily_operator_reports
```

### Limpieza

```bash
php artisan wfm:clean-temporal-assignments             # Asignaciones temporales expiradas
```

Schedule: `->dailyAt('04:00')`

### Métricas agregadas

```bash
php artisan wfm:aggregate-metrics                      # Métricas avanzadas (WU, PWI, Capacidad)
php artisan wfm:aggregate-metrics --from=2026-07-01 --to=2026-07-28
```

---

## Comunicaciones (CommunicationsModule)

```bash
php artisan communications:publish-scheduled            # Publica contenido programado
php artisan communications:auto-archive                 # Archiva contenido vencido
php artisan communications:send-expired-poll-reminders  # Recordatorios de encuestas
php artisan communications:send-newsletter              # Newsletter diario
```

Schedules:
- `publish-scheduled` → cada 5 min
- `auto-archive` → cada hora
- `send-expired-poll-reminders` → cada hora
- `send-newsletter` → `dailyAt('08:00')`

---

## Calidad (QualityModule)

```bash
php artisan quality:seed                                # Datos iniciales (colas, criterios)
php artisan quality:import-csv                          # Importa criterios desde CSVs legacy
php artisan quality:import-csv --force                  # Forzar sobreescritura
```

---

## Auditoría (AuditModule)

```bash
php artisan audit:prune                                 # Podar logs antiguos
```

---

## Notificaciones (CoreModule)

```bash
php artisan notifications:seed-configs                  # Configuraciones predeterminadas
```

---

## Analytics (AnalyticsModule — Upgrade 0.2)

### Data Mart

**Job** (Schedule, no comando directo):

```php
RefreshDataMartJob    # Refresca fact_* y dim_* cada hora
```

Schedule: `->hourly()` en cola `wfm-heavy`.

Refresca:
- Dimensiones: dim_employee, dim_team, dim_department, dim_queue, dim_shift, dim_skill
- Vistas (siempre actuales): dim_date, dim_interval
- Facts: fact_calls, fact_schedule, fact_quality, fact_absence, fact_agent_interval

### KPIs Consolidados

**Scheduled callback** (no comando directo):

```php
CalculateDailyKpisAction::execute(yesterday)    # Materializa daily_kpis
```

Schedule: `dailyAt('03:30')` — corre después del Data Mart y reconciliación de asistencia.

### Forecast, Staffing, Shrinkage, Capacity

Estos subdominios no tienen comandos Artisan dedicados. Se ejecutan vía sus Actions:

```php
app(ImportForecastAction::class)->execute(...)
app(GenerateForecastAction::class)->execute(...)
app(CalculateStaffingRequirementsAction::class)->execute(...)
app(CalculateShrinkageAction::class)->execute(...)
app(GenerateCapacityPlanAction::class)->execute(...)
app(CalculateForecastAccuracyAction::class)->execute(...)
```

---

## Pruebas

```bash
php artisan app:login-test-command                      # Test de autenticación y rutas
```

---

## Jobs programados (resumen)

| Job/Comando                         | Frecuencia   | Cola        | Propósito                                      |
| ----------------------------------- | ------------ | ----------- | ---------------------------------------------- |
| `AggregateIntervalMetricsJob`       | Cada 15 min  | `wfm-heavy` | Métricas de intervalo (agent_interval_metrics) |
| `communications:publish-scheduled`  | Cada 5 min   | —           | Publicar contenido programado                  |
| `communications:auto-archive`       | Cada hora    | —           | Archivar contenido vencido                     |
| `uccx:auto-import`                  | Cada hora    | —           | Importar CSV de UCCX                           |
| `RefreshDataMartJob`                | Cada hora    | `wfm-heavy` | Refrescar Data Mart                            |
| `alerts:evaluate`                   | Cada minuto  | —           | Evaluar reglas de alerta                       |
| `RecalculateQueueStats`             | Diario       | —           | Recalcular estadísticas de colas (Quality)     |
| `schedules:compile-daily-snapshots` | Diario 02:00 | —           | Compilar snapshots de horarios                 |
| `operations:reconcile-attendance`   | Diario 03:00 | —           | Reconciliar asistencia                         |
| `CalculateDailyKpisAction`          | Diario 03:30 | —           | Materializar KPIs consolidados                 |
| `wfm:clean-temporal-assignments`    | Diario 04:00 | —           | Limpiar asignaciones temporales                |
| `communications:send-newsletter`    | Diario 08:00 | —           | Enviar newsletter                              |

---

## Referencia rápida por tarea

| Tarea                     | Comando                                                                                                                             |
| ------------------------- | ----------------------------------------------------------------------------------------------------------------------------------- |
| Iniciar desarrollo        | `composer dev`                                                                                                                      |
| Ejecutar tests            | `composer test`                                                                                                                     |
| Corregir formato PHP      | `composer lint`                                                                                                                     |
| Verificar formato         | `composer lint:check`                                                                                                               |
| Sincronizar Cisco CUIC    | `php artisan cuic:sync`                                                                                                             |
| Sincronizar Cisco Finesse | `php artisan finesse:sync`                                                                                                          |
| Importar llamadas UCCX    | `php artisan uccx:auto-import`                                                                                                      |
| Reconciliar asistencia    | `php artisan operations:reconcile-attendance`                                                                                       |
| Seed datos iniciales      | `php artisan db:seed`                                                                                                               |
| Forzar Data Mart          | `php artisan tinker --execute 'dispatch(new App\Modules\AnalyticsModule\Jobs\RefreshDataMartJob)'`                                  |
| Forzar KPIs diarios       | `php artisan tinker --execute 'app(App\Modules\AnalyticsModule\Actions\CalculateDailyKpisAction::class)->execute(now()->subDay())'` |
