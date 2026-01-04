<?php

namespace App\Module\News;

use App\Core\Policy;
use App\Core\User;

class NewsPolicy implements Policy
{
    public function view(User $user, array $context): bool
    {
        return $user->hasPermission('news.read');
    }

    public function create(User $user, array $context): bool
    {
        return $user->hasPermission('news.manage');
    }

    public function update(User $user, array $context): bool
    {
        return $user->hasPermission('news.manage');
    }

    public function delete(User $user, array $context): bool
    {
        return $user->hasPermission('news.manage');
    }
}
