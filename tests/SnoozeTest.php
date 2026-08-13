<?php

namespace Ghijk\CpNotifications\Tests\Pest\SnoozeTest;

use Carbon\CarbonImmutable;
use Ghijk\CpNotifications\Data\Snooze;

test('it is an immutable round trippable data object', function () {
    $data = [
        'notification_id' => 'notice-id',
        'user_id' => 'user-id',
        'snoozed_until' => '2026-08-13T16:55:00+12:00',
    ];

    $snooze = Snooze::fromArray($data);

    expect($snooze->notificationId)->toBe('notice-id');
    expect($snooze->userId)->toBe('user-id');
    expect($snooze->snoozedUntil)->toBeInstanceOf(CarbonImmutable::class);
    expect($snooze->toArray())->toBe($data);
    expect($snooze->jsonSerialize())->toBe($data);
    expect((new \ReflectionClass($snooze))->isReadOnly())->toBeTrue();
});

test('it is inactive at the exact expiry boundary', function () {
    $until = CarbonImmutable::parse('2026-08-13T16:55:00+12:00');
    $snooze = new Snooze('notice-id', 'user-id', $until);

    expect($snooze->isActiveAt($until->subMicrosecond()))->toBeTrue();
    expect($snooze->isActiveAt($until))->toBeFalse();
    expect($snooze->isActiveAt($until->addMicrosecond()))->toBeFalse();
});
