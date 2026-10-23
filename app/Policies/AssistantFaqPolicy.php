<?php

namespace App\Policies;

use App\Models\AssistantFaq;
use App\Models\User;

class AssistantFaqPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, AssistantFaq $faq): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, AssistantFaq $faq): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, AssistantFaq $faq): bool
    {
        return $user->isAdmin();
    }

    public function manageSettings(User $user): bool
    {
        return $user->isAdmin();
    }

    public function approveSources(User $user): bool
    {
        return $user->isAdmin();
    }
}
