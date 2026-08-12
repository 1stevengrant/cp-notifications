<?php

namespace Ghijk\CpNotifications\Notifications;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Statamic\Contracts\Entries\Entry;

final class NotificationStatus
{
    public function __construct(private NotificationLock $lock)
    {
    }

    public function for(Entry $notification, CarbonInterface|DateTimeInterface|string|null $now = null): string
    {
        if ($this->lock->isLocked($notification)) {
            return 'locked';
        }

        if (! $notification->published()) {
            return 'draft';
        }

        $timezone = (string) config('app.timezone', 'UTC');
        $current = $this->date($now ?? CarbonImmutable::now($timezone), $timezone);
        $start = $this->date($notification->get('start_date'), $timezone);
        $end = $this->date($notification->get('end_date'), $timezone);

        if ($start !== null && $current->lessThan($start)) {
            return 'scheduled';
        }

        if ($end !== null && $current->greaterThanOrEqualTo($end)) {
            return 'expired';
        }

        return 'active';
    }

    private function date(CarbonInterface|DateTimeInterface|string|null $value, string $timezone): ?CarbonImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $value instanceof DateTimeInterface
            ? CarbonImmutable::instance($value)->setTimezone($timezone)
            : CarbonImmutable::parse($value, $timezone);
    }
}
