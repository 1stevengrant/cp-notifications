<?php

namespace Ghijk\CpNotifications\Listeners;

use Ghijk\CpNotifications\Notifications\NotificationLock;
use Illuminate\Validation\ValidationException;
use Statamic\Events\EntrySaving;

final class PreventLockedNotificationEdits
{
    public function __construct(private NotificationLock $lock)
    {
    }

    public function handle(EntrySaving $event): void
    {
        $entry = $event->entry;

        if ($entry->collectionHandle() !== 'notifications' || ! $this->lock->isLocked($entry)) {
            return;
        }

        throw ValidationException::withMessages([
            'notification' => 'This notification is locked because it has acknowledgements and cannot be changed. Create a superseding notification to issue a correction.',
        ]);
    }
}
