<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Models;

use App\Modules\CoreModule\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo para Directorates (Direcciones).
 *
 * Representa las direcciones de la organización.
 */
class Directorate extends Model
{
    use Auditable;

    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    /**
     * Departamentos pertenecientes a esta dirección.
     */
    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }
}
