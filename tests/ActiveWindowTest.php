<?php

namespace Ghijk\CpNotifications\Tests;

use Carbon\CarbonImmutable;
use Ghijk\CpNotifications\Notifications\ActiveWindow;
use Statamic\Entries\Collection;
use Statamic\Entries\Entry;

class ActiveWindowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.timezone', 'Pacific/Auckland');
    }

    public function test_published_notice_is_active_from_its_start_until_before_its_end(): void
    {
        $notice = $this->notification(true, '2026-08-12 09:00', '2026-08-12 17:00');
        $window = new ActiveWindow;

        $this->assertTrue($window->isActive($notice, '2026-08-12 09:00'));
        $this->assertTrue($window->isActive($notice, '2026-08-12 16:59:59'));
        $this->assertFalse($window->isActive($notice, '2026-08-12 17:00'));
    }

    public function test_future_draft_and_missing_start_notices_are_inactive(): void
    {
        $window = new ActiveWindow;

        $this->assertFalse($window->isActive(
            $this->notification(true, '2026-08-13 09:00'),
            '2026-08-12 09:00',
        ));
        $this->assertFalse($window->isActive(
            $this->notification(false, '2026-08-11 09:00'),
            '2026-08-12 09:00',
        ));
        $this->assertFalse($window->isActive(
            $this->notification(true, null),
            '2026-08-12 09:00',
        ));
    }

    public function test_open_ended_notice_remains_active(): void
    {
        $notice = $this->notification(true, '2026-01-01 00:00');

        $this->assertTrue((new ActiveWindow)->isActive($notice, '2027-01-01 00:00'));
    }

    public function test_instants_are_compared_in_the_configured_site_timezone(): void
    {
        $notice = $this->notification(true, '2026-08-12 09:00', '2026-08-12 10:00');

        $this->assertTrue((new ActiveWindow)->isActive(
            $notice,
            CarbonImmutable::parse('2026-08-11 21:30:00 UTC'),
        ));
    }

    private function notification(bool $published, ?string $start, ?string $end = null): Entry
    {
        return (new Entry)
            ->id('notice-1')
            ->collection(Collection::make('notifications'))
            ->published($published)
            ->set('start_date', $start)
            ->set('end_date', $end);
    }
}
