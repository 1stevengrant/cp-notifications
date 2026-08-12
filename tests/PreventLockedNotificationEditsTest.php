<?php

namespace Ghijk\CpNotifications\Tests\Pest\PreventLockedNotificationEditsTest;

use Carbon\CarbonImmutable;
use Ghijk\CpNotifications\Contracts\AcknowledgementRepository;
use Ghijk\CpNotifications\Data\Acknowledgement;
use Ghijk\CpNotifications\Listeners\PreventLockedNotificationEdits;
use Ghijk\CpNotifications\Notifications\NotificationLock;
use Ghijk\CpNotifications\ServiceProvider;
use Illuminate\Validation\ValidationException;
use Statamic\Entries\Collection;
use Statamic\Entries\Entry;
use Statamic\Events\EntrySaving;

test('locked notifications are rejected on the server', function () {
    $entry = entry('notifications');
    $repository = \Mockery::mock(AcknowledgementRepository::class);
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
});

test('the error directs admins to create a superseding notification', function () {
    $repository = \Mockery::mock(AcknowledgementRepository::class);
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
            ->handle(new EntrySaving(entry('notifications')));
        $this->fail('Expected locked notification validation to fail.');
    } catch (ValidationException $exception) {
        $message = $exception->errors()['notification'][0];

        $this->assertStringContainsString('acknowledgements', $message);
        $this->assertStringContainsString('superseding notification', $message);
    }
});

test('unlocked notifications and other collections can save', function () {
    $repository = \Mockery::mock(AcknowledgementRepository::class);
    $repository->allows('forNotification')->with('entry-1')->andReturn(collect());
    $listener = new PreventLockedNotificationEdits(new NotificationLock($repository));

    $listener->handle(new EntrySaving(entry('notifications')));
    $listener->handle(new EntrySaving(entry('pages')));

    $this->addToAssertionCount(1);
});

test('the listener is registered before save normalization', function () {
    $listeners = (new \ReflectionClass(ServiceProvider::class))
        ->getDefaultProperties()['listen'][EntrySaving::class];

    expect($listeners[0])->toBe(PreventLockedNotificationEdits::class);
});

function entry(string $collection): Entry
{
    return (new Entry)
        ->id('entry-1')
        ->collection(Collection::make($collection));
}
