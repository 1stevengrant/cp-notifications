<?php

namespace Ghijk\CpNotifications\Reports;

use Carbon\CarbonImmutable;
use Ghijk\CpNotifications\Audience\AudienceResolver;
use Ghijk\CpNotifications\Contracts\AcknowledgementRepository;
use Ghijk\CpNotifications\Contracts\SnoozeRepository;
use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Entry;

final class NotificationReportResolver
{
    public function __construct(
        private AudienceResolver $audience,
        private AcknowledgementRepository $acknowledgements,
        private SnoozeRepository $snoozes,
    ) {
    }

    public function resolve(Entry $notification): Collection
    {
        $instant = CarbonImmutable::now(config('app.timezone', 'UTC'));

        return $this->audience->resolve($notification)
            ->map(function ($user) use ($notification, $instant): array {
                $snooze = $this->snoozes->find(
                    (string) $notification->id(),
                    (string) $user->id(),
                );

                return [
                    'user' => $user,
                    'acknowledgement' => $this->acknowledgements->find(
                        (string) $notification->id(),
                        (string) $user->id(),
                    ),
                    'snooze' => $snooze,
                    'snooze_active' => $snooze?->isActiveAt($instant) ?? false,
                ];
            })
            ->values();
    }
}
