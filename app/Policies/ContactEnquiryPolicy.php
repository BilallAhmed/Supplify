<?php

namespace App\Policies;

use App\Models\ContactEnquiry;
use App\Models\User;

class ContactEnquiryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, ContactEnquiry $contactEnquiry): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, ContactEnquiry $contactEnquiry): bool
    {
        return $user->isAdmin();
    }
}
