<?php

namespace Ghijk\CpNotifications\Tests\Pest\NudgeScheduleTest;

use Illuminate\Console\Scheduling\Schedule;

test('the nudge command is registered hourly without overlap', function () {
    $event = collect($this->app->make(Schedule::class)->events())
        ->first(fn ($event): bool => str_contains($event->command ?? '', 'cp-notifications:nudge'));

    expect($event)->not->toBeNull();
    expect($event->expression)->toBe('0 * * * *');
    expect($event->withoutOverlapping)->toBeTrue();
    $documentation = file_get_contents(__DIR__.'/../DOCUMENTATION.md');
    $this->assertStringContainsString('schedule:run', $documentation);
    $this->assertStringContainsString('queue:work', $documentation);
});
