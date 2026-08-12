<?php

namespace Ghijk\CpNotifications\Notifications;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Ghijk\CpNotifications\Audience\AudienceMatcher;
use Ghijk\CpNotifications\Contracts\AcknowledgementRepository;
use Ghijk\CpNotifications\Contracts\SnoozeRepository;
use Illuminate\Support\Collection;
use Statamic\Contracts\Auth\User;

final class ActiveStackResolver
{
    public function __construct(
        private AudienceMatcher $audience,
        private ActiveWindow $window,
        private AcknowledgementRepository $acknowledgements,
        private SnoozeRepository $snoozes,
    ) {}

    public function resolve(
        iterable $notifications,
        User $user,
        CarbonInterface|DateTimeInterface|string|null $now = null,
    ): Collection {
        $instant = $now instanceof DateTimeInterface
            ? CarbonImmutable::instance($now)
            : CarbonImmutable::parse($now ?? 'now', config('app.timezone', 'UTC'));

        return collect($notifications)
            ->filter(fn ($notification): bool => $this->audience->matches($notification, $user))
            ->filter(fn ($notification): bool => $this->window->isActive($notification, $now))
            ->reject(fn ($notification): bool => $this->acknowledgements->find(
                (string) $notification->id(),
                (string) $user->id(),
            ) !== null)
            ->reject(function ($notification) use ($user, $instant): bool {
                $snooze = $this->snoozes->find((string) $notification->id(), (string) $user->id());

                return $snooze?->isActiveAt($instant) ?? false;
            })
            ->values();
    }
}
