<?php

declare(strict_types=1);

namespace App\Modules\KnowledgeModule\Policies;

use App\Modules\CoreModule\Models\User;
use App\Modules\KnowledgeModule\Models\Article;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Policy para controlar permisos de acceso sobre la Base de Conocimiento.
 */
class ArticlePolicy
{
    use HandlesAuthorization;

    /**
     * Determina si el usuario puede listar los artículos (acceso a la vista del operador).
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('knowledge.viewAny') || $user->hasPermissionTo('knowledge.manage');
    }

    /**
     * Determina si el usuario puede ver el detalle de un artículo específico.
     */
    public function view(User $user, Article $article): bool
    {
        if ($article->status === 'published') {
            return $user->hasPermissionTo('knowledge.viewAny') || $user->hasPermissionTo('knowledge.manage');
        }

        return $user->hasPermissionTo('knowledge.manage');
    }

    /**
     * Determina si el usuario puede crear artículos.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('knowledge.manage');
    }

    /**
     * Determina si el usuario puede actualizar artículos.
     */
    public function update(User $user, Article $article): bool
    {
        return $user->hasPermissionTo('knowledge.manage');
    }

    /**
     * Determina si el usuario puede eliminar artículos.
     */
    public function delete(User $user, Article $article): bool
    {
        return $user->hasPermissionTo('knowledge.manage');
    }
}
/**
 * [RIESGOS]
 * - Bypass de Permisos → Es crítico que estas capacidades se registren correctamente en AuthServiceProvider o dinámicamente en el ModuleServiceProvider.
 */
