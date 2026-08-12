<?php

namespace Ghijk\CpNotifications\Retention;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Ghijk\CpNotifications\Contracts\AcknowledgementRepository;
use Illuminate\Support\Collection as SupportCollection;
use Psr\Log\LoggerInterface;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;

final class NotificationPurgeService
{
    public function __construct(
        private AcknowledgementRepository $acknowledgements,
        private LoggerInterface $logger,
    ) {
    }

    public function candidates(): SupportCollection
    {
        $collection = Collection::find('notifications');

        if (! $collection) {
            return collect();
        }

        $timezone = (string) config('app.timezone', 'UTC');
        $instant = CarbonImmutable::now($timezone);
        $retentionDays = config('cp-notifications.retention.inbox_days');
        $cutoff = $retentionDays === null || $retentionDays === ''
            ? $instant
            : $instant->subDays(max(0, (int) $retentionDays));

        return $collection->queryEntries()
            ->where('site', Site::default()->handle())
            ->get()
            ->filter(function ($notification) use ($cutoff, $timezone): bool {
                $end = $notification->get('end_date');

                if ($end === null || $end === '') {
                    return false;
                }

                $expiredAt = $end instanceof DateTimeInterface
                    ? CarbonImmutable::instance($end)->setTimezone($timezone)
                    : CarbonImmutable::parse($end, $timezone);

                return $notification->published()
                    && $expiredAt->lessThanOrEqualTo($cutoff)
                    && $this->acknowledgements->forNotification((string) $notification->id())->isEmpty();
            })
            ->values();
    }

    public function purge(string $actorId): SupportCollection
    {
        $candidates = $this->candidates();
        $ids = $candidates->map->id()->map(fn ($id): string => (string) $id)->values();

        $candidates->each->delete();

        $this->logger->info('CP notification manual purge completed.', [
            'actor_id' => $actorId,
            'notification_ids' => $ids->all(),
            'affected_count' => $ids->count(),
            'result' => 'success',
        ]);

        return $ids;
    }
}
