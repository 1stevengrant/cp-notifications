<?php

namespace Ghijk\CpNotifications\Listeners;

use Statamic\Events\EntrySaving;

class NormalizeNotificationBehavior
{
    public function handle(EntrySaving $event): void
    {
        $entry = $event->entry;

        if ($entry->collectionHandle() !== 'notifications' || ! $entry->get('blocking', false)) {
            return;
        }

        $entry->set('snoozeable', false);
    }
}
