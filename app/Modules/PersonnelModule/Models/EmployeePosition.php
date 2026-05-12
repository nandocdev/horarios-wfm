<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeePosition extends Model
{
    protected $fillable = ['employee_id', 'position_id', 'start_date', 'end_date', 'is_primary'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_primary' => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }
}
