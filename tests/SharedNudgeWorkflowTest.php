<?php

namespace Ghijk\CpNotifications\Tests;

use Ghijk\CpNotifications\Jobs\SendNotificationNudges;
use Ghijk\CpNotifications\Nudges\NotificationNudgeService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use ReflectionMethod;

class SharedNudgeWorkflowTest extends TestCase
{
    public function test_manual_and_scheduled_modes_share_the_same_queue_job_and_service(): void
    {
        $scheduled = new SendNotificationNudges('notice-1');
        $manual = new SendNotificationNudges('notice-1', true);
        $serviceParameter = (new ReflectionMethod(SendNotificationNudges::class, 'handle'))
            ->getParameters()[0]
            ->getType()
            ->getName();

        $this->assertInstanceOf(ShouldQueue::class, $scheduled);
        $this->assertInstanceOf(ShouldBeUnique::class, $scheduled);
        $this->assertFalse($scheduled->manual);
        $this->assertTrue($manual->manual);
        $this->assertSame(NotificationNudgeService::class, $serviceParameter);
        $this->assertSame($scheduled->uniqueId(), $manual->uniqueId());
    }
}
