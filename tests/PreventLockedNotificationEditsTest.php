<?php

namespace Ghijk\CpNotifications\Tests;

use Carbon\CarbonImmutable;
use Ghijk\CpNotifications\Contracts\AcknowledgementRepository;
use Ghijk\CpNotifications\Data\Acknowledgement;
use Ghijk\CpNotifications\Listeners\PreventLockedNotificationEdits;
use Ghijk\CpNotifications\Notifications\NotificationLock;
use Illuminate\Validation\ValidationException;
use Mockery;
use Statamic\Entries\Collection;
use Statamic\Entries\Entry;
use Statamic\Events\EntrySaving;

class PreventLockedNotificationEditsTest extends TestCase
{
    public function test_locked_notifications_are_rejected_on_the_server(): void
    {
        $entry = $this->entry('notifications');
        $repository = Mockery::mock(AcknowledgementRepository::class);
        $repository->allows('forNotification')->with('entry-1')->andReturn(collect([
            new Acknowledgement(
                id: 'ack-1',
                notificationId: 'entry-1',
                userId: 'user-1',
                acknowledgedAt: CarbonImmutable::parse('2026-08-12 12:00'),
            ),
        ]));

        $this->expectException(ValidationException::class);

        (new PreventLockedNotificationEdits(new NotificationLock($repository)))
            ->handle(new EntrySaving($entry));
    }

    public function test_the_error_directs_admins_to_create_a_superseding_notification(): void
    {
        $repository = Mockery::mock(AcknowledgementRepository::class);
        $repository->allows('forNotification')->andReturn(collect([
            new Acknowledgement(
                id: 'ack-1',
                notificationId: 'entry-1',
                userId: 'user-1',
                acknowledgedAt: CarbonImmutable::parse('2026-08-12 12:00'),
            ),
        ]));

        try {
            (new PreventLockedNotificationEdits(new NotificationLock($repository)))
                ->handle(new EntrySaving($this->entry('notifications')));
            $this->fail('Expected locked notification validation to fail.');
        } catch (ValidationException $exception) {
            $message = $exception->errors()['notification'][0];

            $this->assertStringContainsString('acknowledgements', $message);
            $this->assertStringContainsString('superseding notification', $message);
        }
    }

    public function test_unlocked_notifications_and_other_collections_can_save(): void
    {
        $repository = Mockery::mock(AcknowledgementRepository::class);
        $repository->allows('forNotification')->with('entry-1')->andReturn(collect());
        $listener = new PreventLockedNotificationEdits(new NotificationLock($repository));

        $listener->handle(new EntrySaving($this->entry('notifications')));
        $listener->handle(new EntrySaving($this->entry('pages')));

        $this->addToAssertionCount(1);
    }

    public function test_the_listener_is_registered_before_save_normalization(): void
    {
        $listeners = (new \ReflectionClass(\Ghijk\CpNotifications\ServiceProvider::class))
            ->getDefaultProperties()['listen'][EntrySaving::class];

        $this->assertSame(PreventLockedNotificationEdits::class, $listeners[0]);
    }

    private function entry(string $collection): Entry
    {
        return (new Entry)
            ->id('entry-1')
            ->collection(Collection::make($collection));
    }
}
