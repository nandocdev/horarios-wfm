<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Models;

use App\Modules\CoreModule\Models\User;
use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalibrationLog extends BaseModel
{
    protected $table = 'quality_calibration_log';

    protected $fillable = [
        'evaluation_id',
        'score_anterior',
        'score_nuevo',
        'obs',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'score_anterior' => 'integer',
            'score_nuevo' => 'integer',
        ];
    }

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class, 'evaluation_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
