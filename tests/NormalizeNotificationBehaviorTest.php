<?php

namespace Ghijk\CpNotifications\Tests\Pest\NormalizeNotificationBehaviorTest;

use Ghijk\CpNotifications\Listeners\NormalizeNotificationBehavior;
use Statamic\Events\EntrySaving;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;

test('blocking notifications are never snoozeable', function () {
    $entry = entry(['blocking' => true, 'snoozeable' => true]);

    (new NormalizeNotificationBehavior)->handle(new EntrySaving($entry));

    expect($entry->get('snoozeable'))->toBeFalse();
});

test('advisory notifications keep their snooze setting', function () {
    $entry = entry(['blocking' => false, 'snoozeable' => true]);

    (new NormalizeNotificationBehavior)->handle(new EntrySaving($entry));

    expect($entry->get('snoozeable'))->toBeTrue();
});

function entry(array $data)
{
    return Entry::make()
        ->id('notification-id')
        ->collection(Collection::make('notifications'))
        ->data($data);
}
