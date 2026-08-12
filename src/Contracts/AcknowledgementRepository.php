<?php

namespace Ghijk\CpNotifications\Contracts;

use Carbon\CarbonImmutable;
use Ghijk\CpNotifications\Data\Acknowledgement;
use Illuminate\Support\Collection;

interface AcknowledgementRepository
{
    public function find(string $notificationId, string $userId): ?Acknowledgement;

    public function record(
        string $notificationId,
        string $userId,
        ?CarbonImmutable $acknowledgedAt = null,
    ): Acknowledgement;

    /** @return Collection<int, Acknowledgement> */
    public function forNotification(string $notificationId): Collection;

    /** @return Collection<int, Acknowledgement> */
    public function forUser(string $userId): Collection;
}
