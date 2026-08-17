<?php

declare(strict_types=1);

namespace App\Modules\DirectoryModule\Models;

use App\Modules\KnowledgeModule\Models\KnowledgeArticle;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Piso (nivel) dentro de un edificio que agrupa los servicios y contactos
 * que dependen de él. La puerta/consultorio pertenece a cada servicio.
 *
 * @property int $id
 * @property int $building_id
 * @property string|null $sector
 * @property string|null $level
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Building $building
 * @property-read Collection<int, DirectoryService> $services
 */
class Unit extends Model
{
    protected $table = 'directory_units';

    protected $fillable = [
        'building_id',
        'sector',
        'level',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Edificio al que pertenece esta ubicación física.
     */
    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class, 'building_id');
    }

    /**
     * Servicios operativos ofrecidos en este piso.
     */
    public function services(): HasMany
    {
        return $this->hasMany(DirectoryService::class, 'unit_id');
    }

    /**
     * Artículo de la base de conocimiento que usa esta unidad como ficha de contacto.
     */
    public function articles(): HasMany
    {
        return $this->hasMany(KnowledgeArticle::class, 'directory_unit_id');
    }

    /**
     * Etiqueta legible para selects y listados.
     */
    public function getDisplayNameAttribute(): string
    {
        $parts = array_filter([$this->building->name, $this->sector, $this->level]);

        return implode(' — ', $parts);
    }
}
