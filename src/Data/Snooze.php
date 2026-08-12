<?php

namespace Ghijk\CpNotifications\Data;

use Carbon\CarbonImmutable;
use JsonSerializable;

final readonly class Snooze implements JsonSerializable
{
    public function __construct(
        public string $notificationId,
        public string $userId,
        public CarbonImmutable $snoozedUntil,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            notificationId: $data['notification_id'],
            userId: $data['user_id'],
            snoozedUntil: CarbonImmutable::parse($data['snoozed_until']),
        );
    }

    public function isActiveAt(CarbonImmutable $instant): bool
    {
        return $instant->lessThan($this->snoozedUntil);
    }

    public function toArray(): array
    {
        return [
            'notification_id' => $this->notificationId,
            'user_id' => $this->userId,
            'snoozed_until' => $this->snoozedUntil->toIso8601String(),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
