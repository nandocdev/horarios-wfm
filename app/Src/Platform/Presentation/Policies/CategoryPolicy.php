<?php

declare(strict_types=1);

namespace App\Src\Platform\Presentation\Policies;

use App\Modules\CommunicationsModule\Models\Category;
use App\Modules\CoreModule\Models\User;

final class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('communications.manage');
    }

    public function view(User $user, Category $category): bool
    {
        return $user->can('communications.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('communications.manage');
    }

    public function update(User $user, Category $category): bool
    {
        return $user->can('communications.manage');
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->can('communications.manage');
    }
}
