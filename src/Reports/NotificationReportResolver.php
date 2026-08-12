<?php

namespace Ghijk\CpNotifications\Reports;

use Ghijk\CpNotifications\Audience\AudienceResolver;
use Ghijk\CpNotifications\Contracts\AcknowledgementRepository;
use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Entry;

final class NotificationReportResolver
{
    public function __construct(
        private AudienceResolver $audience,
        private AcknowledgementRepository $acknowledgements,
    ) {
    }

    public function resolve(Entry $notification): Collection
    {
        return $this->audience->resolve($notification)
            ->map(fn ($user): array => [
                'user' => $user,
                'acknowledgement' => $this->acknowledgements->find(
                    (string) $notification->id(),
                    (string) $user->id(),
                ),
            ])
            ->values();
    }
}
