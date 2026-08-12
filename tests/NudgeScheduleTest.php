<?php

namespace Ghijk\CpNotifications\Tests;

use Illuminate\Console\Scheduling\Schedule;

class NudgeScheduleTest extends TestCase
{
    public function test_the_nudge_command_is_registered_hourly_without_overlap(): void
    {
        $event = collect($this->app->make(Schedule::class)->events())
            ->first(fn ($event): bool => str_contains($event->command ?? '', 'cp-notifications:nudge'));

        $this->assertNotNull($event);
        $this->assertSame('0 * * * *', $event->expression);
        $this->assertTrue($event->withoutOverlapping);
        $this->assertStringContainsString('schedule:run', file_get_contents(__DIR__.'/../README.md'));
        $this->assertStringContainsString('queue:work', file_get_contents(__DIR__.'/../README.md'));
    }
}
