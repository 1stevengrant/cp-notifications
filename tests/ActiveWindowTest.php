<?php

namespace Ghijk\CpNotifications\Tests\Pest\ActiveWindowTest;

use Carbon\CarbonImmutable;
use Ghijk\CpNotifications\Notifications\ActiveWindow;
use Statamic\Entries\Collection;
use Statamic\Entries\Entry;

beforeEach(function () {
    config()->set('app.timezone', 'Pacific/Auckland');
});

test('published notice is active from its start until before its end', function () {
    $notice = notification(true, '2026-08-12 09:00', '2026-08-12 17:00');
    $window = new ActiveWindow;

    expect($window->isActive($notice, '2026-08-12 09:00'))->toBeTrue();
    expect($window->isActive($notice, '2026-08-12 16:59:59'))->toBeTrue();
    expect($window->isActive($notice, '2026-08-12 17:00'))->toBeFalse();
});

test('future draft and missing start notices are inactive', function () {
    $window = new ActiveWindow;

    expect($window->isActive(
        notification(true, '2026-08-13 09:00'),
        '2026-08-12 09:00',
    ))->toBeFalse();
    expect($window->isActive(
        notification(false, '2026-08-11 09:00'),
        '2026-08-12 09:00',
    ))->toBeFalse();
    expect($window->isActive(
        notification(true, null),
        '2026-08-12 09:00',
    ))->toBeFalse();
});

test('open ended notice remains active', function () {
    $notice = notification(true, '2026-01-01 00:00');

    expect((new ActiveWindow)->isActive($notice, '2027-01-01 00:00'))->toBeTrue();
});

test('future blocking notice is inactive until its start boundary', function () {
    $notice = notification(true, '2026-08-13 09:00')->set('blocking', true);
    $window = new ActiveWindow;

    expect($window->isActive($notice, '2026-08-13 08:59:59'))->toBeFalse();
    expect($window->isActive($notice, '2026-08-13 09:00:00'))->toBeTrue();
});

test('instants are compared in the configured site timezone', function () {
    $notice = notification(true, '2026-08-12 09:00', '2026-08-12 10:00');

    expect((new ActiveWindow)->isActive(
        $notice,
        CarbonImmutable::parse('2026-08-11 21:30:00 UTC'),
    ))->toBeTrue();
});

function notification(bool $published, ?string $start, ?string $end = null): Entry
{
    return (new Entry)
        ->id('notice-1')
        ->collection(Collection::make('notifications'))
        ->published($published)
        ->set('start_date', $start)
        ->set('end_date', $end);
}
