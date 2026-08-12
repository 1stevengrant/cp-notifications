<?php

namespace Ghijk\CpNotifications\Tests\Pest\SharedNudgeWorkflowTest;

use Ghijk\CpNotifications\Jobs\SendNotificationNudges;
use Ghijk\CpNotifications\Nudges\NotificationNudgeService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;

test('manual and scheduled modes share the same queue job and service', function () {
    $scheduled = new SendNotificationNudges('notice-1');
    $manual = new SendNotificationNudges('notice-1', true);
    $serviceParameter = (new \ReflectionMethod(SendNotificationNudges::class, 'handle'))
        ->getParameters()[0]
        ->getType()
        ->getName();

    expect($scheduled)->toBeInstanceOf(ShouldQueue::class);
    expect($scheduled)->toBeInstanceOf(ShouldBeUnique::class);
    expect($scheduled->manual)->toBeFalse();
    expect($manual->manual)->toBeTrue();
    expect($serviceParameter)->toBe(NotificationNudgeService::class);
    expect($manual->uniqueId())->toBe($scheduled->uniqueId());
});
