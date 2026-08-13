<?php

namespace Ghijk\CpNotifications\Listeners;

use Ghijk\CpNotifications\Notifications\NotificationLock;
use Statamic\Events\EntryBlueprintFound;

final class RenderLockedNotificationReadOnly
{
    public function __construct(private NotificationLock $lock) {}

    public function handle(EntryBlueprintFound $event): void
    {
        $entry = $event->entry;

        if (! $entry || $entry->collectionHandle() !== 'notifications' || ! $this->lock->isLocked($entry)) {
            return;
        }

        $event->blueprint->fields()->all()->each(function ($field): void {
            $field->setConfig(array_merge($field->config(), ['visibility' => 'read_only']));
        });
    }
}
