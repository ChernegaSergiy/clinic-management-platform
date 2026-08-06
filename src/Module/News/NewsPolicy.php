<?php

namespace App\Module\News;

use App\Core\Auth\Policy;
use App\Core\Model\User;

class NewsPolicy implements Policy
{
    public function view(User $user, array $context) : bool
    {
        return $user->hasPermission('news.read');
    }

    public function create(User $user, array $context) : bool
    {
        return $user->hasPermission('news.manage');
    }

    public function update(User $user, array $context) : bool
    {
        return $user->hasPermission('news.manage');
    }

    public function delete(User $user, array $context) : bool
    {
        return $user->hasPermission('news.manage');
    }
}
