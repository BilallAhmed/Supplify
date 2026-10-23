<?php

namespace App\Policies;

use App\Models\AssistantAlias;
use App\Models\User;

class AssistantAliasPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, AssistantAlias $alias): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, AssistantAlias $alias): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, AssistantAlias $alias): bool
    {
        return $user->isAdmin();
    }
}
