<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Models;

use App\Modules\CoreModule\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo para Departments (Departamentos).
 *
 * Representa los departamentos dentro de una dirección.
 */
class Department extends Model
{
    use Auditable;

    protected $fillable = [
        'directorate_id',
        'name',
        'description',
    ];

    /**
     * Dirección a la que pertenece este departamento.
     */
    public function directorate(): BelongsTo
    {
        return $this->belongsTo(Directorate::class);
    }

    /**
     * Cargos pertenecientes a este departamento.
     */
    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }

    /**
     * Empleados pertenecientes a este departamento.
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
