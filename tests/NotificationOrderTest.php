<?php

namespace Ghijk\CpNotifications\Tests\Pest\NotificationOrderTest;

use Ghijk\CpNotifications\Notifications\NotificationOrder;
use Statamic\Entries\Collection;
use Statamic\Entries\Entry;

test('priority overrides severity then severity and oldest start order notices', function () {
    $notices = collect([
        notice('info-new', null, 'info', '2026-08-12 11:00'),
        notice('warning', null, 'warning', '2026-08-12 08:00'),
        notice('critical-new', null, 'critical', '2026-08-12 10:00'),
        notice('priority-two', 2, 'info', '2026-08-12 12:00'),
        notice('critical-old', null, 'critical', '2026-08-12 09:00'),
        notice('priority-one', 1, 'info', '2026-08-12 12:00'),
    ]);

    expect((new NotificationOrder)->sort($notices)->map->id()->all())->toBe([
        'priority-one',
        'priority-two',
        'critical-old',
        'critical-new',
        'warning',
        'info-new',
    ]);
});

test('nullable and tied priorities have exact deterministic precedence', function () {
    $notices = collect([
        notice('tie-b', 5, 'warning', '2026-08-12 08:00'),
        notice('unprioritized-critical', null, 'critical', '2026-08-12 07:00'),
        notice('priority-five-info', 5, 'info', '2026-08-12 06:00'),
        notice('priority-zero', 0, 'info', '2026-08-12 12:00'),
        notice('tie-a', 5, 'warning', '2026-08-12 08:00'),
        notice('priority-five-critical', 5, 'critical', '2026-08-12 11:00'),
        notice('blank-priority', null, 'warning', '2026-08-12 05:00')
            ->set('priority', ''),
    ]);

    expect((new NotificationOrder)->sort($notices)->map->id()->all())->toBe([
        'priority-zero',
        'priority-five-critical',
        'tie-a',
        'tie-b',
        'priority-five-info',
        'unprioritized-critical',
        'blank-priority',
    ]);
});

function notice(string $id, ?int $priority, string $severity, string $start): Entry
{
    return (new Entry)
        ->id($id)
        ->collection(Collection::make('notifications'))
        ->set('priority', $priority)
        ->set('severity', $severity)
        ->set('start_date', $start);
}
