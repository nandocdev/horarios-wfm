<?php

declare(strict_types=1);

namespace App\Modules\OrganizationModule\Models;

use App\Modules\CoreModule\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo para Positions (Cargos).
 *
 * Representa los cargos dentro de un departamento.
 */
class Position extends Model
{
    use Auditable;

    protected $fillable = [
        'department_id',
        'name',
        'description',
        'position_code',
        'salary',
        'is_active',
    ];

    protected $casts = [
        'salary' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Departamento al que pertenece este cargo.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Empleados que ocupan este cargo.
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Empleados que ocupan este cargo.
     *
     * @deprecated Usar employees() para consistencia.
     */
    public function users(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
