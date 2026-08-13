<?php

namespace Ghijk\CpNotifications\Contracts;

use Carbon\CarbonImmutable;
use Ghijk\CpNotifications\Data\Snooze;
use Illuminate\Support\Collection;

interface SnoozeRepository
{
    public function find(string $notificationId, string $userId): ?Snooze;

    public function record(
        string $notificationId,
        string $userId,
        ?CarbonImmutable $snoozedUntil = null,
    ): Snooze;

    /** @return Collection<int, Snooze> */
    public function forNotification(string $notificationId): Collection;

    /** @return Collection<int, Snooze> */
    public function forUser(string $userId): Collection;
}
