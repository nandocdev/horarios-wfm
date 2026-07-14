<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Models;

use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CriteriaVersion extends BaseModel
{
    protected $table = 'quality_criteria_versions';

    protected $fillable = [
        'criteria_id',
        'version',
        'criterio_text',
        'puntaje',
        'descripcion',
        'valid_from',
        'valid_to',
    ];

    protected function casts(): array
    {
        return [
            'puntaje' => 'integer',
            'version' => 'integer',
            'valid_from' => 'date',
            'valid_to' => 'date',
        ];
    }

    public function criteria(): BelongsTo
    {
        return $this->belongsTo(Criteria::class, 'criteria_id');
    }

    public function queueCriteria(): HasMany
    {
        return $this->hasMany(QueueCriteria::class, 'criteria_version_id');
    }
}
