<?php

namespace App\Services;

use App\Models\SettingsAudit;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class SettingsAuditLogger
{
    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public function log(
        string $group,
        string $summary,
        ?array $before = null,
        ?array $after = null,
        ?User $actor = null,
    ): SettingsAudit {
        $user = $actor ?? Auth::user();

        return SettingsAudit::query()->create([
            'group' => $group,
            'user_id' => $user instanceof User ? $user->id : null,
            'summary' => $summary,
            'before' => $before,
            'after' => $after,
            'created_at' => now(),
        ]);
    }
}
