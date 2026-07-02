<?php

declare(strict_types=1);

namespace App\Src\Quality\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class EloquentAgentEvaluation extends Model
{
    protected $table = 'quality_agent_evaluations';

    protected $fillable = [
        'agent_id', 'evaluator_id', 'form_id', 'scores',
        'total_score', 'comments', 'status', 'evaluated_at',
    ];

    protected $casts = [
        'scores' => 'array',
        'total_score' => 'float',
        'evaluated_at' => 'datetime',
    ];
}
