<?php

namespace Ghijk\CpNotifications\Tests;

use Ghijk\CpNotifications\Notifications\NotificationOrder;
use Statamic\Entries\Collection;
use Statamic\Entries\Entry;

class NotificationOrderTest extends TestCase
{
    public function test_priority_overrides_severity_then_severity_and_oldest_start_order_notices(): void
    {
        $notices = collect([
            $this->notice('info-new', null, 'info', '2026-08-12 11:00'),
            $this->notice('warning', null, 'warning', '2026-08-12 08:00'),
            $this->notice('critical-new', null, 'critical', '2026-08-12 10:00'),
            $this->notice('priority-two', 2, 'info', '2026-08-12 12:00'),
            $this->notice('critical-old', null, 'critical', '2026-08-12 09:00'),
            $this->notice('priority-one', 1, 'info', '2026-08-12 12:00'),
        ]);

        $this->assertSame([
            'priority-one',
            'priority-two',
            'critical-old',
            'critical-new',
            'warning',
            'info-new',
        ], (new NotificationOrder)->sort($notices)->map->id()->all());
    }

    public function test_nullable_and_tied_priorities_have_exact_deterministic_precedence(): void
    {
        $notices = collect([
            $this->notice('tie-b', 5, 'warning', '2026-08-12 08:00'),
            $this->notice('unprioritized-critical', null, 'critical', '2026-08-12 07:00'),
            $this->notice('priority-five-info', 5, 'info', '2026-08-12 06:00'),
            $this->notice('priority-zero', 0, 'info', '2026-08-12 12:00'),
            $this->notice('tie-a', 5, 'warning', '2026-08-12 08:00'),
            $this->notice('priority-five-critical', 5, 'critical', '2026-08-12 11:00'),
            $this->notice('blank-priority', null, 'warning', '2026-08-12 05:00')
                ->set('priority', ''),
        ]);

        $this->assertSame([
            'priority-zero',
            'priority-five-critical',
            'tie-a',
            'tie-b',
            'priority-five-info',
            'unprioritized-critical',
            'blank-priority',
        ], (new NotificationOrder)->sort($notices)->map->id()->all());
    }

    private function notice(string $id, ?int $priority, string $severity, string $start): Entry
    {
        return (new Entry)
            ->id($id)
            ->collection(Collection::make('notifications'))
            ->set('priority', $priority)
            ->set('severity', $severity)
            ->set('start_date', $start);
    }
}
