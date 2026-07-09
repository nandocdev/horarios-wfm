<?php

declare(strict_types=1);

namespace App\Modules\KnowledgeModule\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo de Categoría para artículos de la base de conocimiento.
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class Category extends Model
{
    protected $table = 'knowledge_categories';

    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Relación con los artículos asociados a esta categoría.
     */
    public function articles(): HasMany
    {
        return $this->hasMany(KnowledgeArticle::class, 'category_id');
    }
}
/**
 * [RIESGOS]
 * - Eliminación en cascada → Configurado nullOnDelete en base de datos para no perder artículos si se elimina la categoría.
 */
