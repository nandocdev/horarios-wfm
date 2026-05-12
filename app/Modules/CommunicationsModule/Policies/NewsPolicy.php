<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Policies;

use App\Modules\CommunicationsModule\Models\News;
use App\Modules\CoreModule\Models\User;

/**
 * Define los permisos de acceso al recurso News.
 * Registrar en ModuleServiceProvider.
 */
class NewsPolicy
{
    public function viewAny(User $authUser): bool
    {
        return $authUser->hasPermissionTo('news.view');
    }

    public function view(User $authUser, News $news): bool
    {
        return $authUser->hasPermissionTo('news.view');
    }

    public function create(User $authUser): bool
    {
        return $authUser->hasPermissionTo('news.create');
    }

    public function update(User $authUser, News $news): bool
    {
        return $authUser->hasPermissionTo('news.edit')
            || $authUser->id === $news->author_id;
    }

    public function delete(User $authUser, News $news): bool
    {
        return $authUser->hasPermissionTo('news.delete')
            || $authUser->id === $news->author_id;
    }

    public function restore(User $authUser, News $news): bool
    {
        return $authUser->hasPermissionTo('news.delete');
    }

    public function forceDelete(User $authUser, News $news): bool
    {
        return $authUser->hasPermissionTo('news.delete');
    }

    /**
     * Determina si el usuario puede ver contenido pendiente de revisión.
     */
    public function viewPending(User $authUser): bool
    {
        return $authUser->hasPermissionTo('communications.view_pending')
            || $authUser->hasPermissionTo('communications.moderate');
    }

    /**
     * Determina si el usuario puede moderar este contenido.
     */
    public function moderateContent(User $authUser, News $news): bool
    {
        return $authUser->hasPermissionTo('communications.moderate');
    }
}
