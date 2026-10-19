<?php

namespace App\Policies;

use App\Models\HomepageSection;
use App\Models\User;

class HomepageSectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, HomepageSection $section): bool
    {
        return $user->isAdmin();
    }
}
