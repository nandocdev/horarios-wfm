<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Models;

use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationRedFlag extends BaseModel
{
    protected $table = 'quality_evaluation_red_flags';

    protected $fillable = [
        'evaluation_id',
        'red_flag_criteria_id',
    ];

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class, 'evaluation_id');
    }

    public function redFlagCriteria(): BelongsTo
    {
        return $this->belongsTo(RedFlagCriteria::class, 'red_flag_criteria_id');
    }
}
