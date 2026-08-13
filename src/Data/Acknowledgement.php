<?php

namespace Ghijk\CpNotifications\Data;

use Carbon\CarbonImmutable;
use JsonSerializable;

final readonly class Acknowledgement implements JsonSerializable
{
    public function __construct(
        public string $id,
        public string $notificationId,
        public string $userId,
        public CarbonImmutable $acknowledgedAt,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            notificationId: $data['notification_id'],
            userId: $data['user_id'],
            acknowledgedAt: CarbonImmutable::parse($data['acknowledged_at']),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'notification_id' => $this->notificationId,
            'user_id' => $this->userId,
            'acknowledged_at' => $this->acknowledgedAt->toIso8601String(),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
