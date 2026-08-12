<?php

namespace Ghijk\CpNotifications\Repositories;

use Carbon\CarbonImmutable;
use Ghijk\CpNotifications\Contracts\NudgeDeliveryRepository;
use Ghijk\CpNotifications\Data\NudgeDelivery;
use Illuminate\Database\ConnectionInterface;
use RuntimeException;

final class EloquentNudgeDeliveryRepository implements NudgeDeliveryRepository
{
    public const TABLE = 'cp_notification_nudge_deliveries';

    public function __construct(private ConnectionInterface $database)
    {
    }

    public function find(string $notificationId, string $userId): ?NudgeDelivery
    {
        $record = $this->database->table(self::TABLE)
            ->where('notification_id', $notificationId)
            ->where('user_id', $userId)
            ->first();

        return $record ? NudgeDelivery::fromArray((array) $record) : null;
    }

    public function recordSent(string $notificationId, string $userId, ?CarbonImmutable $sentAt = null): NudgeDelivery
    {
        $instant = $sentAt ?? CarbonImmutable::now();
        $this->database->transaction(function () use ($notificationId, $userId, $instant): void {
            $this->database->table(self::TABLE)->insertOrIgnore([
                'notification_id' => $notificationId,
                'user_id' => $userId,
                'last_sent_at' => $instant->toISOString(),
                'send_count' => 0,
            ]);
            $this->database->table(self::TABLE)
                ->where('notification_id', $notificationId)
                ->where('user_id', $userId)
                ->update([
                    'last_sent_at' => $instant->toISOString(),
                    'send_count' => $this->database->raw('send_count + 1'),
                ]);
        });

        return $this->find($notificationId, $userId)
            ?? throw new RuntimeException('Unable to record notification nudge delivery.');
    }
}
