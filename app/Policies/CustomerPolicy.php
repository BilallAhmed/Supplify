<?php

namespace App\Policies;

use App\Models\User;

class CustomerPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function view(User $actor, User $customer): bool
    {
        return $actor->isAdmin() && $customer->isCustomer();
    }

    public function update(User $actor, User $customer): bool
    {
        return $actor->isAdmin() && $customer->isCustomer();
    }
}
