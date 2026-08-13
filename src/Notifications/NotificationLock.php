<?php

namespace Ghijk\CpNotifications\Notifications;

use Ghijk\CpNotifications\Contracts\AcknowledgementRepository;
use Statamic\Contracts\Entries\Entry;

final class NotificationLock
{
    public function __construct(private AcknowledgementRepository $acknowledgements) {}

    public function isLocked(Entry|string $notification): bool
    {
        $id = $notification instanceof Entry ? $notification->id() : $notification;

        return $id !== null
            && $this->acknowledgements->forNotification((string) $id)->isNotEmpty();
    }
}
