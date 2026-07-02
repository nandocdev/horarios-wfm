<?php

declare(strict_types=1);

namespace App\Src\Quality\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class EloquentEvaluationCriteria extends Model
{
    protected $table = 'quality_evaluation_criteria';

    protected $fillable = ['form_id', 'name', 'description', 'max_score', 'weight', 'is_fatal_error'];

    protected $casts = [
        'max_score' => 'integer',
        'weight' => 'float',
        'is_fatal_error' => 'boolean',
    ];
}
