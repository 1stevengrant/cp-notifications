<?php

namespace Ghijk\CpNotifications\Reports;

use Carbon\CarbonImmutable;
use Ghijk\CpNotifications\Audience\AudienceResolver;
use Ghijk\CpNotifications\Contracts\AcknowledgementRepository;
use Ghijk\CpNotifications\Contracts\SnoozeRepository;
use Illuminate\Support\Collection;
use Statamic\Contracts\Auth\UserRepository;
use Statamic\Contracts\Entries\Entry;

final class NotificationReportResolver
{
    public function __construct(
        private AudienceResolver $audience,
        private AcknowledgementRepository $acknowledgements,
        private SnoozeRepository $snoozes,
        private UserRepository $users,
    ) {}

    public function resolve(Entry $notification): Collection
    {
        $instant = CarbonImmutable::now(config('app.timezone', 'UTC'));

        $rows = $this->audience->resolve($notification)
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
                    'currently_targeted' => true,
                ];
            })
            ->values();

        $currentUserIds = $rows->pluck('user')->map->id()->map(fn ($id): string => (string) $id);

        $this->acknowledgements->forNotification((string) $notification->id())
            ->reject(fn ($acknowledgement): bool => $currentUserIds->contains($acknowledgement->userId))
            ->each(function ($acknowledgement) use ($rows, $notification, $instant): void {
                $snooze = $this->snoozes->find(
                    (string) $notification->id(),
                    $acknowledgement->userId,
                );

                $rows->push([
                    'user' => $this->users->find($acknowledgement->userId),
                    'user_id' => $acknowledgement->userId,
                    'acknowledgement' => $acknowledgement,
                    'snooze' => $snooze,
                    'snooze_active' => $snooze?->isActiveAt($instant) ?? false,
                    'currently_targeted' => false,
                ]);
            });

        return $rows;
    }
}
