<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Infrastructure\Persistence\Eloquent\Policies;

use App\Modules\CommunicationsModule\Infrastructure\Persistence\Eloquent\NewsModel;
use App\Modules\CoreModule\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class NewsPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('news.view');
    }

    public function view(User $user, NewsModel $news): bool
    {
        return $user->can('news.view');
    }

    public function create(User $user): bool
    {
        return $user->can('news.create');
    }

    public function update(User $user, NewsModel $news): bool
    {
        return $user->can('news.edit') || $user->id === $news->author_id;
    }

    public function delete(User $user, NewsModel $news): bool
    {
        return $user->can('news.delete') || $user->id === $news->author_id;
    }

    public function moderateContent(User $user): bool
    {
        return $user->can('communications.moderate');
    }
}
