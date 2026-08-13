<?php

namespace Ghijk\CpNotifications\Notifications;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Statamic\Contracts\Entries\Entry;

final class ActiveWindow
{
    public function isActive(Entry $notification, CarbonInterface|DateTimeInterface|string|null $now = null): bool
    {
        if (! $notification->published()) {
            return false;
        }

        $timezone = (string) config('app.timezone', 'UTC');
        $current = $this->date($now ?? CarbonImmutable::now($timezone), $timezone);
        $start = $this->date($notification->get('start_date'), $timezone);
        $end = $this->date($notification->get('end_date'), $timezone);

        return $start !== null
            && $current->greaterThanOrEqualTo($start)
            && ($end === null || $current->lessThan($end));
    }

    private function date(CarbonInterface|DateTimeInterface|string|null $value, string $timezone): ?CarbonImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return CarbonImmutable::instance($value)->setTimezone($timezone);
        }

        return CarbonImmutable::parse($value, $timezone);
    }
}
