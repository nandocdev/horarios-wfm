<?php

declare(strict_types=1);

namespace App\Modules\OrganizationModule\Models;

use App\Modules\CoreModule\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo para Departments (Departamentos).
 *
 * Representa los departamentos dentro de una dirección.
 */
class Department extends Model
{
    use Auditable, SoftDeletes;

    protected $fillable = [
        'directorate_id',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
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
}
