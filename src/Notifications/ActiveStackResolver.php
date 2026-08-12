<?php

namespace Ghijk\CpNotifications\Notifications;

use Carbon\CarbonInterface;
use DateTimeInterface;
use Ghijk\CpNotifications\Audience\AudienceMatcher;
use Ghijk\CpNotifications\Contracts\AcknowledgementRepository;
use Illuminate\Support\Collection;
use Statamic\Contracts\Auth\User;

final class ActiveStackResolver
{
    public function __construct(
        private AudienceMatcher $audience,
        private ActiveWindow $window,
        private AcknowledgementRepository $acknowledgements,
    ) {}

    public function resolve(
        iterable $notifications,
        User $user,
        CarbonInterface|DateTimeInterface|string|null $now = null,
    ): Collection {
        return collect($notifications)
            ->filter(fn ($notification): bool => $this->audience->matches($notification, $user))
            ->filter(fn ($notification): bool => $this->window->isActive($notification, $now))
            ->reject(fn ($notification): bool => $this->acknowledgements->find(
                (string) $notification->id(),
                (string) $user->id(),
            ) !== null)
            ->values();
    }
}
