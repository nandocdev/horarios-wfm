<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Models;

use App\Modules\CoreModule\Models\User;
use App\Modules\PersonnelModule\Models\Employee;
use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Evaluation extends BaseModel
{
    use SoftDeletes;

    protected $table = 'quality_evaluations';

    protected $fillable = [
        'queue_id',
        'employee_id',
        'evaluator_id',
        'clip_id',
        'dtcall',
        'tmcall',
        'dteval',
        'tmeval',
        'score',
        'callobs',
        'has_redflag',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'dtcall' => 'date',
            'tmcall' => 'datetime',
            'dteval' => 'date',
            'tmeval' => 'datetime',
            'score' => 'integer',
            'has_redflag' => 'boolean',
        ];
    }

    public function queue(): BelongsTo
    {
        return $this->belongsTo(Queue::class, 'queue_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(EvaluationScore::class, 'evaluation_id');
    }

    public function redFlags(): HasMany
    {
        return $this->hasMany(EvaluationRedFlag::class, 'evaluation_id');
    }

    public function feedbacks(): HasMany
    {
        return $this->hasMany(Feedback::class, 'evaluation_id');
    }

    public function calibrations(): HasMany
    {
        return $this->hasMany(CalibrationLog::class, 'evaluation_id');
    }
}
