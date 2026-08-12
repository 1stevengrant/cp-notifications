<?php

namespace Ghijk\CpNotifications\Repositories;

use Carbon\CarbonImmutable;
use Ghijk\CpNotifications\Contracts\AcknowledgementRepository;
use Ghijk\CpNotifications\Data\Acknowledgement;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;

class EloquentAcknowledgementRepository implements AcknowledgementRepository
{
    public const TABLE = 'cp_notification_acknowledgements';

    public function __construct(private readonly ConnectionInterface $database)
    {
    }

    public function find(string $notificationId, string $userId): ?Acknowledgement
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
        ?CarbonImmutable $acknowledgedAt = null,
    ): Acknowledgement {
        $this->database->table(self::TABLE)->insertOrIgnore([
            'id' => (string) Str::uuid(),
            'notification_id' => $notificationId,
            'user_id' => $userId,
            'acknowledged_at' => ($acknowledgedAt ?? CarbonImmutable::now())->toISOString(),
        ]);

        return $this->find($notificationId, $userId)
            ?? throw new RuntimeException('Unable to record notification acknowledgement.');
    }

    public function forNotification(string $notificationId): Collection
    {
        return $this->recordsFor('notification_id', $notificationId);
    }

    public function forUser(string $userId): Collection
    {
        return $this->recordsFor('user_id', $userId);
    }

    /** @return Collection<int, Acknowledgement> */
    private function recordsFor(string $column, string $value): Collection
    {
        return $this->database->table(self::TABLE)
            ->where($column, $value)
            ->orderBy('acknowledged_at')
            ->get()
            ->map(fn (object $record): Acknowledgement => $this->hydrate($record));
    }

    private function hydrate(object $record): Acknowledgement
    {
        return Acknowledgement::fromArray((array) $record);
    }
}
