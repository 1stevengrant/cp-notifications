<?php

namespace Ghijk\CpNotifications\Tests\Pest\NudgeEligibilityTest;

use Ghijk\CpNotifications\Notifications\ActiveWindow;
use Ghijk\CpNotifications\Nudges\NudgeEligibility;
use Statamic\Entries\Collection;
use Statamic\Entries\Entry;

test('scheduled eligibility uses enabled threshold and optional cadence', function (array $settings, ?string $lastSentAt, string $now, bool $expected) {
    config()->set('app.timezone', 'Pacific/Auckland');
    $notification = notice()->set('nudge', $settings);

    expect((new NudgeEligibility(new ActiveWindow))->eligible($notification, $lastSentAt, $now))->toBe($expected);
})->with('scheduledCases');

dataset('scheduledCases', function () {
    return [
        'disabled' => [['enabled' => false, 'threshold_hours' => 24], null, '2026-08-12 12:00', false],
        'before threshold' => [['enabled' => true, 'threshold_hours' => 24], null, '2026-08-11 11:59:59', false],
        'at threshold' => [['enabled' => true, 'threshold_hours' => 24], null, '2026-08-11 12:00', true],
        'one shot already sent' => [['enabled' => true, 'threshold_hours' => 24], '2026-08-11 12:00', '2026-08-12 12:00', false],
        'before cadence' => [['enabled' => true, 'threshold_hours' => 24, 'cadence_hours' => 24], '2026-08-11 12:00', '2026-08-12 11:59:59', false],
        'at cadence' => [['enabled' => true, 'threshold_hours' => 24, 'cadence_hours' => 24], '2026-08-11 12:00', '2026-08-12 12:00', true],
    ];
});

test('manual reminders bypass settings but require an active notice', function () {
    $eligibility = new NudgeEligibility(new ActiveWindow);
    $notification = notice()->set('nudge', ['enabled' => false]);

    expect($eligibility->eligible($notification, null, '2026-08-10 12:00', true))->toBeTrue();
    expect($eligibility->eligible($notification, null, '2026-08-13 12:00', true))->toBeFalse();
});

function notice(): Entry
{
    return (new Entry)
        ->id('notice-1')
        ->collection(Collection::make('notifications'))
        ->published(true)
        ->data([
            'start_date' => '2026-08-10 12:00',
            'end_date' => '2026-08-13 12:00',
        ]);
}
