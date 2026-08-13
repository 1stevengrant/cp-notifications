<?php

namespace Ghijk\CpNotifications\Tests\Pest\NotificationLockTest;

use Carbon\CarbonImmutable;
use Ghijk\CpNotifications\Contracts\AcknowledgementRepository;
use Ghijk\CpNotifications\Data\Acknowledgement;
use Ghijk\CpNotifications\Notifications\NotificationLock;
use Statamic\Entries\Collection;
use Statamic\Entries\Entry;

test('notice locks when its first acknowledgement exists', function () {
    $repository = \Mockery::mock(AcknowledgementRepository::class);
    $repository->allows('forNotification')->with('notice-1')->andReturn(collect());
    $repository->allows('forNotification')->with('notice-2')->andReturn(collect([
        new Acknowledgement(
            id: 'ack-1',
            notificationId: 'notice-2',
            userId: 'user-1',
            acknowledgedAt: CarbonImmutable::parse('2026-08-12 12:00'),
        ),
    ]));
    $lock = new NotificationLock($repository);

    expect($lock->isLocked('notice-1'))->toBeFalse();
    expect($lock->isLocked('notice-2'))->toBeTrue();
});

test('it accepts notification entries and new entries are unlocked', function () {
    $repository = \Mockery::mock(AcknowledgementRepository::class);
    $repository->allows('forNotification')->with('notice-1')->andReturn(collect([
        new Acknowledgement(
            id: 'ack-1',
            notificationId: 'notice-1',
            userId: 'user-1',
            acknowledgedAt: CarbonImmutable::parse('2026-08-12 12:00'),
        ),
    ]));
    $lock = new NotificationLock($repository);
    $persisted = (new Entry)->id('notice-1')->collection(Collection::make('notifications'));
    $new = (new Entry)->collection(Collection::make('notifications'));

    expect($lock->isLocked($persisted))->toBeTrue();
    expect($lock->isLocked($new))->toBeFalse();
});
