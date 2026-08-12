<?php

namespace Ghijk\CpNotifications\Tests;

use Ghijk\CpNotifications\Listeners\NormalizeNotificationBehavior;
use Statamic\Events\EntrySaving;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;

class NormalizeNotificationBehaviorTest extends TestCase
{
    public function test_blocking_notifications_are_never_snoozeable(): void
    {
        $entry = $this->entry(['blocking' => true, 'snoozeable' => true]);

        (new NormalizeNotificationBehavior)->handle(new EntrySaving($entry));

        $this->assertFalse($entry->get('snoozeable'));
    }

    public function test_advisory_notifications_keep_their_snooze_setting(): void
    {
        $entry = $this->entry(['blocking' => false, 'snoozeable' => true]);

        (new NormalizeNotificationBehavior)->handle(new EntrySaving($entry));

        $this->assertTrue($entry->get('snoozeable'));
    }

    private function entry(array $data)
    {
        return Entry::make()
            ->id('notification-id')
            ->collection(Collection::make('notifications'))
            ->data($data);
    }
}
