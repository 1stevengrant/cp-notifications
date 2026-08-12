<?php

namespace Ghijk\CpNotifications\Repositories;

use Carbon\CarbonImmutable;
use Ghijk\CpNotifications\Contracts\SnoozeRepository;
use Ghijk\CpNotifications\Data\Snooze;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Collection;
use RuntimeException;

final class EloquentSnoozeRepository implements SnoozeRepository
{
    public const TABLE = 'cp_notification_snoozes';

    public function __construct(private readonly ConnectionInterface $database)
    {
    }

    public function find(string $notificationId, string $userId): ?Snooze
    {
        $record = $this->database->table(self::TABLE)
            ->where('notification_id', $notificationId)
            ->where('user_id', $userId)
            ->first();

        return $record ? $this->hydrate($record) : null;
    }

    public function record(
        string $notificationId,
        string $userId,
        ?CarbonImmutable $snoozedUntil = null,
    ): Snooze {
        $this->database->table(self::TABLE)->insertOrIgnore([
            'notification_id' => $notificationId,
            'user_id' => $userId,
            'snoozed_until' => ($snoozedUntil ?? CarbonImmutable::now()->addDay())->toISOString(),
        ]);

        return $this->find($notificationId, $userId)
            ?? throw new RuntimeException('Unable to record notification snooze.');
    }

    public function forNotification(string $notificationId): Collection
    {
        return $this->recordsFor('notification_id', $notificationId);
    }

    public function forUser(string $userId): Collection
    {
        return $this->recordsFor('user_id', $userId);
    }

    /** @return Collection<int, Snooze> */
    private function recordsFor(string $column, string $value): Collection
    {
        return $this->database->table(self::TABLE)
            ->where($column, $value)
            ->orderBy('snoozed_until')
            ->get()
            ->map(fn (object $record): Snooze => $this->hydrate($record));
    }

    private function hydrate(object $record): Snooze
    {
        return Snooze::fromArray((array) $record);
    }
}
