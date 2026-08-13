<?php

namespace Ghijk\CpNotifications\Contracts;

use Carbon\CarbonImmutable;
use Ghijk\CpNotifications\Data\NudgeDelivery;

interface NudgeDeliveryRepository
{
    public function find(string $notificationId, string $userId): ?NudgeDelivery;

    public function recordSent(
        string $notificationId,
        string $userId,
        ?CarbonImmutable $sentAt = null,
    ): NudgeDelivery;
}
