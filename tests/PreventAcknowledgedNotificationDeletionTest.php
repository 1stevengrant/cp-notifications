<?php

namespace Ghijk\CpNotifications\Tests;

use Ghijk\CpNotifications\Contracts\AcknowledgementRepository;
use Ghijk\CpNotifications\Data\Acknowledgement;
use Ghijk\CpNotifications\Listeners\PreventAcknowledgedNotificationDeletion;
use Ghijk\CpNotifications\Notifications\NotificationLock;
use Illuminate\Validation\ValidationException;
use Mockery;
use Statamic\Entries\Collection;
use Statamic\Entries\Entry;
use Statamic\Events\EntryDeleting;

class PreventAcknowledgedNotificationDeletionTest extends TestCase
{
    public function test_statamic_deletion_is_rejected_after_the_first_acknowledgement(): void
    {
        $repository = Mockery::mock(AcknowledgementRepository::class);
        $repository->allows('forNotification')->with('notice-1')->andReturn(collect([
            new Acknowledgement('ack-1', 'notice-1', 'user-1', now()->toImmutable()),
        ]));
        $entry = (new Entry)->id('notice-1')->collection(Collection::make('notifications'));
        $listener = new PreventAcknowledgedNotificationDeletion(new NotificationLock($repository));

        $this->expectException(ValidationException::class);
        $listener->handle(new EntryDeleting($entry));
    }
}
