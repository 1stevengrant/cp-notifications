<?php

namespace Ghijk\CpNotifications\Notifications;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class NotificationOrder
{
    private const SEVERITY = [
        'critical' => 0,
        'warning' => 1,
        'info' => 2,
    ];

    public function sort(Collection $notifications): Collection
    {
        return $notifications
            ->sortBy(fn ($notification): array => $this->key($notification))
            ->values();
    }

    private function key($notification): array
    {
        $priority = $notification->get('priority');
        $hasPriority = $priority !== null && $priority !== '';
        $timezone = (string) config('app.timezone', 'UTC');

        return [
            $hasPriority ? 0 : 1,
            $hasPriority ? (int) $priority : 0,
            self::SEVERITY[$notification->get('severity', 'info')] ?? self::SEVERITY['info'],
            CarbonImmutable::parse($notification->get('start_date'), $timezone)->getTimestamp(),
            (string) $notification->id(),
        ];
    }
}
