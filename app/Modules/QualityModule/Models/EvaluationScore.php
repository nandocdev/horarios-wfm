<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Models;

use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationScore extends BaseModel
{
    protected $table = 'quality_evaluation_scores';

    protected $fillable = [
        'evaluation_id',
        'criteria_version_id',
        'puntaje_obtenido',
    ];

    protected function casts(): array
    {
        return [
            'puntaje_obtenido' => 'integer',
        ];
    }

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class, 'evaluation_id');
    }

    public function criteriaVersion(): BelongsTo
    {
        return $this->belongsTo(CriteriaVersion::class, 'criteria_version_id');
    }
}
