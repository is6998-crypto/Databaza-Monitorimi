<?php

namespace App\Support;

use App\Models\CourtCase;
use App\Models\Hearing;
use App\Models\User;

/**
 * Record-level access rules for monitors (proposal B4): they see cases in their assigned
 * courts plus cases they created or were assigned to, and edit only their own records.
 */
final class CaseAccess
{
    /** @var array<int, list<int>> */
    private static array $courtCache = [];

    /**
     * @return list<int>
     */
    public static function monitorCourtIds(User $user): array
    {
        $monitor = $user->monitor;

        if ($monitor === null) {
            return [];
        }

        return self::$courtCache[$monitor->id] ??= $monitor->courts()->pluck('courts.id')->map(fn ($id): int => (int) $id)->all();
    }

    public static function flush(): void
    {
        self::$courtCache = [];
    }

    public static function canSeeCase(User $user, CourtCase $case): bool
    {
        if (! $user->isMonitorOnly()) {
            return true;
        }

        return self::ownsCase($user, $case)
            || ($case->court_id !== null && in_array($case->court_id, self::monitorCourtIds($user), true));
    }

    /**
     * Created by the user or assigned to the user's monitor profile.
     */
    public static function ownsCase(User $user, CourtCase $case): bool
    {
        return $case->created_by === $user->id
            || ($user->monitor !== null && $case->assigned_monitor_id === $user->monitor->id);
    }

    public static function ownsHearing(User $user, Hearing $hearing): bool
    {
        return $hearing->created_by === $user->id
            || ($user->monitor !== null && $hearing->monitor_id === $user->monitor->id);
    }
}
