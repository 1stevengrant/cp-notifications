<?php

namespace Ghijk\CpNotifications\Tests;

use Carbon\CarbonImmutable;
use Ghijk\CpNotifications\Contracts\AcknowledgementRepository;
use Ghijk\CpNotifications\Data\Acknowledgement;
use Ghijk\CpNotifications\Notifications\NotificationLock;
use Mockery;
use Statamic\Entries\Collection;
use Statamic\Entries\Entry;

class NotificationLockTest extends TestCase
{
    public function test_notice_locks_when_its_first_acknowledgement_exists(): void
    {
        $repository = Mockery::mock(AcknowledgementRepository::class);
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

        $this->assertFalse($lock->isLocked('notice-1'));
        $this->assertTrue($lock->isLocked('notice-2'));
    }

    public function test_it_accepts_notification_entries_and_new_entries_are_unlocked(): void
    {
        $repository = Mockery::mock(AcknowledgementRepository::class);
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

        $this->assertTrue($lock->isLocked($persisted));
        $this->assertFalse($lock->isLocked($new));
    }
}
