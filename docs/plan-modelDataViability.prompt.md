## Plan: Viabilidad Del Modelo De Datos

El DDL de `docs/tmp/Model.md` es técnicamente viable como objetivo de evolución sobre PostgreSQL 16 y Laravel 13, pero no debe ejecutarse ni reemplazar las migraciones actuales de forma directa. La aplicación ya cubre una parte importante del Core, Personnel, WFM operativo, Connect básico, Communications, Filesystem, Helpdesk y Documentation; el modelo añade capas aún inexistentes de Forecast, Analytics, Quality, Alerts, Shrinkage y varias relaciones históricas. La estrategia recomendada es un baseline compatible y una migración incremental por módulos, con una fase previa de corrección del contrato de dominio.

**Hallazgos de Discovery**
- El DDL declara 138 tablas; el repositorio contiene 96 declaraciones `Schema::create`, pero esa cifra incluye tablas auxiliares, históricas u obsoletas y no representa cobertura 1:1.
- El proyecto usa Laravel 13, PostgreSQL está configurado, Spatie Permission, Fortify, Horizon, Reverb, Pulse, Media Library, Pest y Livewire/Flux. La conexión predeterminada sigue siendo SQLite, por lo que varias garantías del DDL solo se validarían en PostgreSQL.
- Ya existen migraciones/modelos para: auth/RBAC, cache/jobs, geo, directorates/departments/positions/employees, equipos y detalles de personal, schedules/weekly assignments/intraday, swaps/leaves/exceptions, channels/queues/case subtypes/call records, chat/performance/state transitions, daily metrics, communications, media/files/folders/shares/quotas, helpdesk, documentation, audit y parte de knowledge.
- Faltan o son mayormente futuros: `organizational_units`, skills y su histórico, temporal assignments, queue/interval metrics adicionales, todo el bloque Forecast/Capacity, casi todo Quality, Analytics dimensions/facts, Alert events/escalations, workflow genérico, Shrinkage, y algunos catálogos/relaciones de conocimiento y directorio.
- El contrato de dominio actual contradice el DDL en puntos relevantes: el código trata `employees.parent_id` y `teams.supervisor_id` como empleados, mientras el DDL los cambia a `users`; el código usa jerarquía de empleados y la lógica de permisos/gestión depende de ella. El DDL además declara `employees.user_id` opcional, pero el modelo actual ya asume la relación User-Employee.

**Cobertura estimada por dominio**
- Core/auth/RBAC/cache/jobs: alta, aproximadamente 85-95%; falta `user_tour_progress` y hay que conservar el esquema exacto esperado por Laravel/paquetes.
- Settings: media, aproximadamente 50%; existen app/operational settings, faltan notification configs y alert rules.
- Geo: alta, aproximadamente 100%.
- Organization/personnel: media-alta, aproximadamente 70-80%; faltan unidades organizativas, skills, skill history y revisar diferencias de supervisor/horarios base.
- WFM schedules/intradía/workflows específicos: alta en operación actual, aproximadamente 75-90%; faltan temporal assignments y algunas FKs/columnas históricas.
- Connect: media-alta, aproximadamente 70-80%; el núcleo Cisco está implementado, faltan queue skills, estados/relaciones ampliadas y `uploaded_files` según el DDL.
- Operations metrics: baja-media, aproximadamente 35-50%; existe `agent_daily_metrics`, faltan queue daily y agent interval completos.
- Forecast/capacity: 0% como módulo persistente.
- Quality: baja/incipiente; existe al menos una migración aislada, pero no hay evidencia de cobertura integral del bloque de criterios, evaluaciones, scores, red flags, feedback y calibración.
- Analytics facts/dimensions: 0% como esquema dedicado.
- Alerts/events: 0% persistente en el modelo actual.
- Generic workflow: 0% o no evidenciado; existen workflows específicos de swaps/leaves.
- Shrinkage: 0%.
- Communications, filesystem, helpdesk, documentation: alta, con posibles diferencias de columnas/relaciones y knowledge parcialmente divergente.

**Riesgos bloqueantes que deben resolverse antes de implementar**
1. No ejecutar el SQL completo como una migración única: hay 138 tablas, FKs, índices parciales, índice `EXCLUDE USING gist`, trigger PL/pgSQL y extensión `btree_gist`; se volvería difícil de revertir, probar y mantener.
2. Resolver `User` versus `Employee` como actor/sujeto. La migración propuesta rompería `Employee::manager()`, `Team::supervisor()`, `hasCoordinatorRights()` y `getManagedTeamIds()` si se aplica sin refactor funcional.
3. Corregir FKs inconsistentes del DDL: columnas `NOT NULL` con `ON DELETE SET NULL` (por ejemplo `attendance_incidents.employee_id`, varios facts/metrics); `organizational_units.head_employee_id` está nombrada como empleado pero referencia `users`; `weekly_schedule_assignments.swap_request_id` no tiene FK; `intraday_activities.approved_period_id` no tiene FK; dimensiones `date_id`/`interval_id` de facts no tienen FKs.
4. Revisar integridad de PostgreSQL: `employees_parent_not_self` compara un `parent_id` que el DDL hace FK a `users`, por lo que no representa la misma jerarquía; índices parciales y `EXCLUDE USING gist` requieren SQL específico y `btree_gist`.
5. No cifrar PII sin estrategia de búsqueda: encrypted casts para teléfono/identificador ciudadano invalidan búsquedas e índices btree directos; diseñar hash determinístico separado si se requiere igualdad.
6. Evitar duplicar Laravel/paquetes: users, permissions, notifications, media, jobs, failed_jobs, cache y tokens deben conservar el contrato de los paquetes, no copiar el DDL a ciegas.
7. Definir estrategia de migración de datos existentes y compatibilidad de IDs antes de cambiar nombres, tipos o acciones de borrado.
8. Mantener PostgreSQL como matriz obligatoria de integración; SQLite puede seguir para tests rápidos, pero no valida constraints, `jsonb`, `tstzrange`, índices parciales, triggers ni extensiones.

**Plan de ejecución recomendado si se aprueba la evolución**
1. Fase 0, contrato y baseline: inventariar tablas reales con `php artisan migrate:status`, revisar migraciones/modelos efectivos, fijar PostgreSQL como entorno de integración y crear una matriz DDL-versus-aplicación con estado `igual/diferente/faltante/obsoleto`. No modificar funcionalidad aún.
2. Fase 1, compatibilidad Core y Personnel: decidir definitivamente actor/sujeto; conservar o migrar la jerarquía actual; añadir solo columnas/tablas faltantes (`organizational_units`, skills/history y `user_tour_progress`) con migraciones incrementales y tests de relaciones, FKs y políticas.
3. Fase 2, consolidación WFM/Connect/Operations: comparar las migraciones existentes con schedules, intraday, workflow específico, Cisco y métricas; añadir FKs/índices/columnas faltantes, eliminar tablas obsoletas solo mediante migraciones explícitas y actualizar modelos/relaciones sin cambiar comportamiento accidentalmente.
4. Fase 3, módulos nuevos independientes: implementar Settings/Alerts, Forecast/Capacity, Quality, Shrinkage y Workflow genérico como módulos separados, cada uno con modelos, Actions, policies, factories y tests Pest; las fact tables solo después de estabilizar sus fuentes OLTP.
5. Fase 4, Analytics: crear calendario e intervalos, snapshot SCD2 y facts mediante jobs idempotentes; definir la clave de fecha/intervalo y la política de retención antes de agregar FKs o cargas masivas.
6. Fase 5, datos y operación: preparar backfill por lotes, validación de conteos/checksums, ventana de despliegue, rollback lógico y observabilidad. No borrar tablas antiguas hasta completar doble lectura o verificación de equivalencia.

**Archivos y superficies relevantes**
- `docs/tmp/Model.md` — DDL objetivo, fuente de tablas y constraints.
- `database/migrations/` — baseline real y migraciones existentes; no reemplazar históricas.
- `app/Modules/CoreModule/Models/User.php` — contrato de autenticación, SoftDeletes, Fortify y Spatie.
- `app/Modules/PersonnelModule/Models/Employee.php` — jerarquía y reglas de visibilidad que dependen de `parent_id` como Employee.
- `app/Modules/PersonnelModule/Models/Team.php` — supervisor actual como Employee.
- `app/Modules/WfmModule/Models/` — contratos de schedules, intraday y assignments.
- `app/Modules/ConnectModule/Models/` — integración Cisco y registros operativos.
- `app/Modules/OperationsModule/Models/AgentDailyMetric.php` — métrica existente y divergencias de columnas/acciones de borrado.
- `app/Modules/AuditModule/Models/AuditLog.php` y `CoreModule/Concerns/Auditable.php` — auditoría actual; el trigger de inmutabilidad sería un cambio de comportamiento.
- `composer.json`, `config/database.php`, `config/permission.php`, `config/auth.php` — compatibilidad de stack y conexión.

**Verificación propuesta**
- Ejecutar `php artisan migrate:status` y una migración limpia sobre PostgreSQL 16; no usar solo SQLite.
- Añadir/ejecutar tests Pest por bloque para FKs, acciones `ON DELETE`, índices parciales, `EXCLUDE`, triggers y relaciones `User`/`Employee`.
- Validar el esquema con consultas a `information_schema`/`pg_constraint`/`pg_indexes` y comparar contra una matriz tabular del DDL.
- Ejecutar la suite focalizada por módulo y después `php artisan test --compact`; ejecutar Pint si se modifican archivos PHP.
- Probar backfill en una copia con conteos, duplicados, huérfanos y consistencia de snapshots/facts.

**Alcance recomendado**
- Incluido: evaluación de viabilidad, cobertura actual, riesgos, priorización y ruta incremental.
- Excluido por ahora: crear migraciones, cambiar modelos, ejecutar `migrate`, modificar el DDL o crear una rama/commit.
- El roadmap canónico descrito por las instrucciones (`docs/technical/*`) no está disponible en la ruta visible del checkout; las búsquedas ubican documentación equivalente bajo `docs/HorariosWFM`, pero el lector no pudo abrirla. No asignar códigos UC ni marcar tareas hasta confirmar la fuente vigente.

**Decisión recomendada**
Aprobar el modelo como arquitectura objetivo y contrato de revisión, no como script de despliegue. Primero aprobar una fase de normalización del DDL y reconciliación User/Employee; luego implementar por módulos con migraciones Laravel incrementales. La cobertura funcional potencial es amplia, pero la cobertura persistente inmediata es parcial y el mayor riesgo no es PostgreSQL sino cambiar semántica de dominio ya usada por la aplicación.
