<?php

declare(strict_types=1);

namespace App\Modules\OrganizationModule\Models;

use App\Modules\CoreModule\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo para Directorates (Direcciones).
 *
 * Representa las direcciones de la organización.
 */
class Directorate extends Model
{
    use Auditable, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Departamentos pertenecientes a esta dirección.
     */
    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }
}
