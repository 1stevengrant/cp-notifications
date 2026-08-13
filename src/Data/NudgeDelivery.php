<?php

namespace Ghijk\CpNotifications\Data;

use Carbon\CarbonImmutable;

final readonly class NudgeDelivery
{
    public function __construct(
        public string $notificationId,
        public string $userId,
        public CarbonImmutable $lastSentAt,
        public int $sendCount,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            (string) $data['notification_id'],
            (string) $data['user_id'],
            CarbonImmutable::parse($data['last_sent_at']),
            (int) $data['send_count'],
        );
    }

    public function toArray(): array
    {
        return [
            'notification_id' => $this->notificationId,
            'user_id' => $this->userId,
            'last_sent_at' => $this->lastSentAt->toIso8601String(),
            'send_count' => $this->sendCount,
        ];
    }
}
