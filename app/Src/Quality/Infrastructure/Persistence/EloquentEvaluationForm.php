<?php

declare(strict_types=1);

namespace App\Src\Quality\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EloquentEvaluationForm extends Model
{
    protected $table = 'quality_evaluation_forms';

    protected $fillable = ['name', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function criteria(): HasMany
    {
        return $this->hasMany(EloquentEvaluationCriteria::class, 'form_id');
    }
}
