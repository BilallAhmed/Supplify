<?php

namespace App\Policies;

use App\Models\MediaAsset;
use App\Models\User;

class MediaAssetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, MediaAsset $mediaAsset): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, MediaAsset $mediaAsset): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, MediaAsset $mediaAsset): bool
    {
        return $user->isAdmin();
    }
}
