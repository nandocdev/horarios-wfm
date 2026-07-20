<?php

declare(strict_types=1);

namespace App\Modules\KnowledgeModule\Policies;

use App\Modules\CoreModule\Models\User;
use App\Modules\KnowledgeModule\Models\ArticleVersion;

class ArticleVersionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('knowledge.viewAny') || $user->hasPermissionTo('knowledge.manage');
    }

    public function view(User $user, ArticleVersion $articleVersion): bool
    {
        return $user->hasPermissionTo('knowledge.viewAny') || $user->hasPermissionTo('knowledge.manage');
    }
}
