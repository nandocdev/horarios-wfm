# Setup Inicial — QualityModule

> **Nota:** Este módulo no tiene datos legacy que migrar. A diferencia del sistema original (PHP plano), el QualityModule se construye desde cero sobre el monólito modular existente. Este documento cubre la creación inicial del módulo y la siembra de datos de referencia.

## 1. Creación del Módulo

```bash
# 1. Crear estructura del módulo
php artisan make:module QualityModule

# 2. Crear migraciones
php artisan make:migration create_quality_queues_table --path=app/Modules/QualityModule/Database/Migrations
php artisan make:migration create_quality_criteria_table --path=app/Modules/QualityModule/Database/Migrations
php artisan make:migration create_quality_criteria_versions_table --path=app/Modules/QualityModule/Database/Migrations
php artisan make:migration create_quality_queue_criteria_table --path=app/Modules/QualityModule/Database/Migrations
php artisan make:migration create_quality_red_flag_criteria_table --path=app/Modules/QualityModule/Database/Migrations
php artisan make:migration create_quality_evaluations_table --path=app/Modules/QualityModule/Database/Migrations
php artisan make:migration create_quality_evaluation_scores_table --path=app/Modules/QualityModule/Database/Migrations
php artisan make:migration create_quality_evaluation_red_flags_table --path=app/Modules/QualityModule/Database/Migrations
php artisan make:migration create_quality_feedback_table --path=app/Modules/QualityModule/Database/Migrations
php artisan make:migration create_quality_calibration_log_table --path=app/Modules/QualityModule/Database/Migrations

# 3. Registrar el módulo en config/modules.php
# Agregar al array 'enabled':
#   App\Modules\QualityModule\Providers\ModuleServiceProvider::class,
```

## 2. Registro del Módulo

```php
// config/modules.php
'enabled' => [
    // ... módulos existentes
    App\Modules\QualityModule\Providers\ModuleServiceProvider::class,
],
```

## 3. Migraciones

```bash
php artisan migrate --path=app/Modules/QualityModule/Database/Migrations
```

### Tablas creadas

| Migración | Tabla | Propósito |
|---|---|---|
| `001_create_quality_queues` | `quality_queues` | Catálogo de colas de atención |
| `002_create_quality_criteria` | `quality_criteria` | Identidad del criterio |
| `003_create_quality_criteria_versions` | `quality_criteria_versions` | Versión inmutable del criterio |
| `004_create_quality_queue_criteria` | `quality_queue_criteria` | Asignación cola ↔ criterio |
| `005_create_quality_red_flag_criteria` | `quality_red_flag_criteria` | Banderas rojas con penalización |
| `006_create_quality_evaluations` | `quality_evaluations` | Cabecera de evaluación |
| `007_create_quality_evaluation_scores` | `quality_evaluation_scores` | Puntajes individuales por criterio |
| `008_create_quality_evaluation_red_flags` | `quality_evaluation_red_flags` | Banderas rojas de la evaluación |
| `009_create_quality_feedback` | `quality_feedback` | Retroalimentación |
| `010_create_quality_calibration_log` | `quality_calibration_log` | Historial de calibraciones |

### Ejemplo de migración (ULIDs + FK)

```php
// 006_create_quality_evaluations_table.php
use App\Shared\Models\BaseModel;

class CreateQualityEvaluationsTable extends Migration
{
    public function up(): void
    {
        Schema::create('quality_evaluations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('queue_id')->constrained('quality_queues');
            $table->foreignUlid('employee_id')->constrained('employees');
            $table->foreignUlid('evaluator_id')->constrained('users');
            $table->date('dtcall')->nullable();
            $table->time('tmcall')->nullable();
            $table->date('dteval');
            $table->time('tmeval');
            $table->integer('score')->nullable();
            $table->text('callobs')->nullable();
            $table->boolean('has_redflag')->default(false);
            $table->string('status')->default('activa');
            $table->softDeletes();
            $table->timestamps();
        });
    }
}
```

## 4. Seed de Datos Iniciales

```bash
php artisan db:seed --class=App\Modules\QualityModule\Database\Seeders\QualityModuleSeeder
```

El seeder debe crear:

1. **Colas** (11 colas de atención):
   - `CM-Tr` — Citas Médicas - Trámite
   - `CM-Canc` — Citas Médicas - Cancelación
   - `CM-Conf` — Citas Médicas - Confirmación
   - `AU` — Atención al Usuario
   - `Farm` — Farmacia
   - `Mor` — Apremio y Cobro
   - `CONF` — Llamadas Salientes - Confirmación
   - `SIPE` — SIPE
   - `WEB` — Web / Telegram / WhatsApp
   - `CIGESA` — CIGESA – Quejas
   - `Fact` — Facturación

2. **Roles Spatie**:
   - `quality-evaluator` — permiso `quality.evaluations.create`
   - `quality-coordinator` — permisos `quality.evaluations.*`, `quality.feedback.*`, `quality.calibrations.*`
   - `quality-supervisor` — permisos `quality.evaluations.view`, `quality.feedback.create`
   - `quality-admin` — todos los permisos `quality.*`

3. **Criterios de ejemplo** (1 genérico por cola para comenzar)

## 5. Verificación

```bash
# Ejecutar migraciones
php artisan migrate --path=app/Modules/QualityModule/Database/Migrations

# Sembrar datos
php artisan db:seed --class=App\Modules\QualityModule\Database\Seeders\QualityModuleSeeder

# Verificar rutas
php artisan route:list --path=quality

# Probar acceso
php artisan serve
# Abrir: http://localhost:8000/quality/evaluaciones
```
