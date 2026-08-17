<?php

declare(strict_types=1);

namespace App\Modules\DirectoryModule\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Edificio / Torre de la CSS con su jerarquía administrativa.
 *
 * La jerarquía administrativa se recopila una sola vez por edificio.
 *
 * @property int $id
 * @property string $name
 * @property string $director_name
 * @property string|null $subdirector_name
 * @property string $administrator_name
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Unit> $units
 */
class Building extends Model
{
    protected $table = 'directory_buildings';

    protected $fillable = [
        'name',
        'director_name',
        'subdirector_name',
        'administrator_name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Ubicaciones físicas registradas en este edificio.
     */
    public function units(): HasMany
    {
        return $this->hasMany(Unit::class, 'building_id');
    }
}
