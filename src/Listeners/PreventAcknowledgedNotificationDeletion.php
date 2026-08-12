<?php

namespace Ghijk\CpNotifications\Listeners;

use Ghijk\CpNotifications\Notifications\NotificationLock;
use Illuminate\Validation\ValidationException;
use Statamic\Events\EntryDeleting;

final class PreventAcknowledgedNotificationDeletion
{
    public function __construct(private NotificationLock $lock)
    {
    }

    public function handle(EntryDeleting $event): void
    {
        if ($event->entry->collectionHandle() !== 'notifications' || ! $this->lock->isLocked($event->entry)) {
            return;
        }

        throw ValidationException::withMessages([
            'notification' => 'Acknowledged notifications cannot be deleted.',
        ]);
    }
}
