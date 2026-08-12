<?php

namespace Ghijk\CpNotifications\Tests\Pest\AcknowledgementTest;

use Carbon\CarbonImmutable;
use Ghijk\CpNotifications\Data\Acknowledgement;

test('it is an immutable round trippable data object', function () {
    $data = [
        'id' => 'ack-id',
        'notification_id' => 'notice-id',
        'user_id' => 'user-id',
        'acknowledged_at' => '2026-08-12T16:55:00+12:00',
    ];

    $acknowledgement = Acknowledgement::fromArray($data);

    expect($acknowledgement->id)->toBe('ack-id');
    expect($acknowledgement->notificationId)->toBe('notice-id');
    expect($acknowledgement->userId)->toBe('user-id');
    expect($acknowledgement->acknowledgedAt)->toBeInstanceOf(CarbonImmutable::class);
    expect($acknowledgement->toArray())->toBe($data);
    expect($acknowledgement->jsonSerialize())->toBe($data);

    $reflection = new \ReflectionClass($acknowledgement);
    expect($reflection->isFinal())->toBeTrue();
    expect($reflection->isReadOnly())->toBeTrue();
});
