<?php

namespace Ghijk\CpNotifications\Tests\Pest\NudgeScheduleTest;

use Illuminate\Console\Scheduling\Schedule;

test('the nudge command is registered hourly without overlap', function () {
    $event = collect($this->app->make(Schedule::class)->events())
        ->first(fn ($event): bool => str_contains($event->command ?? '', 'cp-notifications:nudge'));

    expect($event)->not->toBeNull();
    expect($event->expression)->toBe('0 * * * *');
    expect($event->withoutOverlapping)->toBeTrue();
    $this->assertStringContainsString('schedule:run', file_get_contents(__DIR__.'/../README.md'));
    $this->assertStringContainsString('queue:work', file_get_contents(__DIR__.'/../README.md'));
});
