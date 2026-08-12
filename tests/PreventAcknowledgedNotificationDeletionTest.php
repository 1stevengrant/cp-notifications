<?php

namespace Ghijk\CpNotifications\Tests\Pest\PreventAcknowledgedNotificationDeletionTest;

use Ghijk\CpNotifications\Contracts\AcknowledgementRepository;
use Ghijk\CpNotifications\Data\Acknowledgement;
use Ghijk\CpNotifications\Listeners\PreventAcknowledgedNotificationDeletion;
use Ghijk\CpNotifications\Notifications\NotificationLock;
use Illuminate\Validation\ValidationException;
use Statamic\Entries\Collection;
use Statamic\Entries\Entry;
use Statamic\Events\EntryDeleting;

test('statamic deletion is rejected after the first acknowledgement', function () {
    $repository = \Mockery::mock(AcknowledgementRepository::class);
    $repository->allows('forNotification')->with('notice-1')->andReturn(collect([
        new Acknowledgement('ack-1', 'notice-1', 'user-1', now()->toImmutable()),
    ]));
    $entry = (new Entry)->id('notice-1')->collection(Collection::make('notifications'));
    $listener = new PreventAcknowledgedNotificationDeletion(new NotificationLock($repository));

    $this->expectException(ValidationException::class);
    $listener->handle(new EntryDeleting($entry));
});
