<?php

namespace Ghijk\CpNotifications\Retention;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Ghijk\CpNotifications\Contracts\AcknowledgementRepository;
use Illuminate\Support\Collection as SupportCollection;
use Psr\Log\LoggerInterface;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;
use Throwable;

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
        $occurredAt = CarbonImmutable::now(config('app.timezone', 'UTC'))->toIso8601String();

        try {
            $candidates = $this->candidates();
            $ids = $candidates
                ->filter(function ($notification): bool {
                    if ($this->acknowledgements->forNotification((string) $notification->id())->isNotEmpty()) {
                        return false;
                    }

                    $notification->delete();

                    return true;
                })
                ->map->id()
                ->map(fn ($id): string => (string) $id)
                ->values();

            $this->logger->info('CP notification manual purge completed.', [
                'actor_id' => $actorId,
                'notification_ids' => $ids->all(),
                'affected_count' => $ids->count(),
                'occurred_at' => $occurredAt,
                'result' => 'success',
            ]);

            return $ids;
        } catch (Throwable $exception) {
            $this->logger->error('CP notification manual purge failed.', [
                'actor_id' => $actorId,
                'notification_ids' => [],
                'affected_count' => 0,
                'occurred_at' => $occurredAt,
                'result' => 'failure',
                'exception' => $exception::class,
            ]);

            throw $exception;
        }
    }
}
