<?php

namespace Ghijk\CpNotifications\Tests\Pest\NotificationStatusTest;

use Ghijk\CpNotifications\Contracts\AcknowledgementRepository;
use Ghijk\CpNotifications\Notifications\NotificationLock;
use Ghijk\CpNotifications\Notifications\NotificationStatus;
use Statamic\Entries\Collection;
use Statamic\Entries\Entry;

test('it distinguishes each management status', function () {
    config()->set('app.timezone', 'Pacific/Auckland');
    $acknowledgements = \Mockery::mock(AcknowledgementRepository::class);
    $acknowledgements->allows('forNotification')->andReturnUsing(
        fn (string $id) => $id === 'locked' ? collect(['acknowledgement']) : collect(),
    );
    $status = new NotificationStatus(new NotificationLock($acknowledgements));
    $now = '2026-08-12 12:00:00 Pacific/Auckland';

    expect($status->for(notice('draft', false), $now))->toBe('draft');
    expect($status->for(
        notice('scheduled')->set('start_date', '2026-08-12 12:00:01'),
        $now,
    ))->toBe('scheduled');
    expect($status->for(notice('active'), $now))->toBe('active');
    expect($status->for(
        notice('expired')->set('end_date', '2026-08-12 12:00:00'),
        $now,
    ))->toBe('expired');
    expect($status->for(notice('locked'), $now))->toBe('locked');
});

function notice(string $id, bool $published = true): Entry
{
    return (new Entry)
        ->id($id)
        ->collection(Collection::make('notifications'))
        ->published($published)
        ->set('start_date', '2026-08-12 11:00:00');
}
