<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Models;

use App\Modules\PersonnelModule\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class IntradayActivity extends Model
{
    protected $table = 'intraday_activities';

    protected $fillable = [
        'employee_id', 'activity_type_id', 'time_range',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function activityType(): BelongsTo
    {
        return $this->belongsTo(ActivityType::class);
    }

    /**
     * Extrae las horas del rango TSTZRANGE de PostgreSQL.
     * [RIESGO] Depende del formato de string de rangos de Postgres si no se usa un cast especializado.
     */
    /**
     * Extrae el inicio del rango TSTZRANGE como objeto Carbon.
     */
    public function getRangeStart(): ?Carbon
    {
        if (empty($this->time_range)) {
            return null;
        }

        $clean = str_replace(['[', '(', ']', ')', '"'], '', $this->time_range);
        $parts = explode(',', $clean);

        return Carbon::parse(trim($parts[0]));
    }

    /**
     * Extrae el fin del rango TSTZRANGE como objeto Carbon.
     */
    public function getRangeEnd(): ?Carbon
    {
        if (empty($this->time_range)) {
            return null;
        }

        $clean = str_replace(['[', '(', ']', ')', '"'], '', $this->time_range);
        $parts = explode(',', $clean);

        return Carbon::parse(trim($parts[1]));
    }
}
