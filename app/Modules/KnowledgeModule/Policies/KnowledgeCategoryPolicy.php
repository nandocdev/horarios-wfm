<?php

declare(strict_types=1);

namespace App\Modules\KnowledgeModule\Policies;

use App\Modules\CoreModule\Models\User;
use App\Modules\KnowledgeModule\Models\KnowledgeCategory;

class KnowledgeCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('knowledge.viewAny') || $user->hasPermissionTo('knowledge.manage');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('knowledge.manage');
    }

    public function update(User $user, KnowledgeCategory $category): bool
    {
        return $user->hasPermissionTo('knowledge.manage');
    }

    public function delete(User $user, KnowledgeCategory $category): bool
    {
        return $user->hasPermissionTo('knowledge.manage');
    }
}
