<?php

namespace Ghijk\CpNotifications\Tests;

use Ghijk\CpNotifications\Contracts\AcknowledgementRepository;
use Ghijk\CpNotifications\Notifications\NotificationLock;
use Ghijk\CpNotifications\Notifications\NotificationStatus;
use Mockery;
use Statamic\Entries\Collection;
use Statamic\Entries\Entry;

class NotificationStatusTest extends TestCase
{
    public function test_it_distinguishes_each_management_status(): void
    {
        config()->set('app.timezone', 'Pacific/Auckland');
        $acknowledgements = Mockery::mock(AcknowledgementRepository::class);
        $acknowledgements->allows('forNotification')->andReturnUsing(
            fn (string $id) => $id === 'locked' ? collect(['acknowledgement']) : collect(),
        );
        $status = new NotificationStatus(new NotificationLock($acknowledgements));
        $now = '2026-08-12 12:00:00 Pacific/Auckland';

        $this->assertSame('draft', $status->for($this->notice('draft', false), $now));
        $this->assertSame('scheduled', $status->for(
            $this->notice('scheduled')->set('start_date', '2026-08-12 12:00:01'),
            $now,
        ));
        $this->assertSame('active', $status->for($this->notice('active'), $now));
        $this->assertSame('expired', $status->for(
            $this->notice('expired')->set('end_date', '2026-08-12 12:00:00'),
            $now,
        ));
        $this->assertSame('locked', $status->for($this->notice('locked'), $now));
    }

    private function notice(string $id, bool $published = true): Entry
    {
        return (new Entry)
            ->id($id)
            ->collection(Collection::make('notifications'))
            ->published($published)
            ->set('start_date', '2026-08-12 11:00:00');
    }
}
