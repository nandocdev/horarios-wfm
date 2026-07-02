<?php

declare(strict_types=1);

namespace App\Src\Platform\Presentation\Policies;

use App\Modules\CommunicationsModule\Models\News;
use App\Modules\CoreModule\Models\User;

final class NewsPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('news.view');
    }

    public function view(User $user, News $news): bool
    {
        return $user->can('news.view');
    }

    public function create(User $user): bool
    {
        return $user->can('news.create');
    }

    public function update(User $user, News $news): bool
    {
        return $user->can('news.edit') || $user->id === $news->author_id;
    }

    public function delete(User $user, News $news): bool
    {
        return $user->can('news.delete') || $user->id === $news->author_id;
    }

    public function restore(User $user, News $news): bool
    {
        return $user->can('news.delete');
    }

    public function forceDelete(User $user, News $news): bool
    {
        return $user->can('news.delete');
    }

    public function viewPending(User $user): bool
    {
        return $user->can('communications.view_pending') || $user->can('communications.moderate');
    }

    public function moderateContent(User $user): bool
    {
        return $user->can('communications.moderate');
    }
}
