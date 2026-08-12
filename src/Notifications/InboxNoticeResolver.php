<?php

namespace Ghijk\CpNotifications\Notifications;

use Ghijk\CpNotifications\Audience\AudienceMatcher;
use Ghijk\CpNotifications\Contracts\AcknowledgementRepository;
use Ghijk\CpNotifications\Contracts\SnoozeRepository;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Collection as SupportCollection;
use Statamic\Contracts\Auth\User;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;

final class InboxNoticeResolver
{
    public function __construct(
        private AudienceMatcher $audience,
        private ActiveWindow $window,
        private AcknowledgementRepository $acknowledgements,
        private SnoozeRepository $snoozes,
    ) {}

    public function resolve(User $user): SupportCollection
    {
        $collection = Collection::find('notifications');

        if (! $collection) {
            return collect();
        }

        $instant = CarbonImmutable::now(config('app.timezone', 'UTC'));

        return $collection->queryEntries()
            ->where('site', Site::default()->handle())
            ->get()
            ->filter(fn ($notification): bool => $notification->published())
            ->filter(fn ($notification): bool => $this->audience->matches($notification, $user))
            ->filter(fn ($notification): bool => $this->retained($notification, $instant))
            ->map(function ($notification) use ($user, $instant): array {
                $acknowledgement = $this->acknowledgements->find(
                    (string) $notification->id(),
                    (string) $user->id(),
                );
                $snooze = $this->snoozes->find(
                    (string) $notification->id(),
                    (string) $user->id(),
                );

                return [
                    'notification' => $notification,
                    'acknowledgement' => $acknowledgement,
                    'snooze' => $snooze,
                    'active' => $this->window->isActive($notification, $instant)
                        && $acknowledgement === null
                        && ! ($snooze?->isActiveAt($instant) ?? false),
                ];
            })
            ->sortByDesc(fn (array $item): bool => $item['active'])
            ->values();
    }

    private function retained($notification, CarbonImmutable $instant): bool
    {
        $days = config('cp-notifications.retention.inbox_days');
        $end = $notification->get('end_date');

        if ($days === null || $days === '' || $end === null || $end === '') {
            return true;
        }

        $timezone = (string) config('app.timezone', 'UTC');
        $expiredAt = $end instanceof DateTimeInterface
            ? CarbonImmutable::instance($end)->setTimezone($timezone)
            : CarbonImmutable::parse($end, $timezone);

        return $expiredAt->greaterThanOrEqualTo($instant->subDays((int) $days));
    }
}
