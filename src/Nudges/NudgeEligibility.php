<?php

namespace Ghijk\CpNotifications\Nudges;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Ghijk\CpNotifications\Notifications\ActiveWindow;
use Statamic\Contracts\Entries\Entry;

final class NudgeEligibility
{
    public function __construct(private ActiveWindow $window)
    {
    }

    public function eligible(
        Entry $notification,
        CarbonInterface|DateTimeInterface|string|null $lastSentAt = null,
        CarbonInterface|DateTimeInterface|string|null $now = null,
        bool $manual = false,
    ): bool {
        $timezone = (string) config('app.timezone', 'UTC');
        $instant = $this->date($now ?? CarbonImmutable::now($timezone), $timezone);

        if (! $this->window->isActive($notification, $instant)) {
            return false;
        }

        if ($manual) {
            return true;
        }

        $settings = $notification->get('nudge');

        if (! is_array($settings) || ! ($settings['enabled'] ?? false)) {
            return false;
        }

        $start = $this->date($notification->get('start_date'), $timezone);
        $threshold = max(0, (int) ($settings['threshold_hours'] ?? 24));

        if ($start === null || $instant->lessThan($start->addHours($threshold))) {
            return false;
        }

        if ($lastSentAt === null || $lastSentAt === '') {
            return true;
        }

        $cadence = $settings['cadence_hours'] ?? null;

        return $cadence !== null
            && $cadence !== ''
            && $instant->greaterThanOrEqualTo(
                $this->date($lastSentAt, $timezone)->addHours(max(1, (int) $cadence)),
            );
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
