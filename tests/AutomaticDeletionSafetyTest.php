<?php

namespace Ghijk\CpNotifications\Tests\Pest\AutomaticDeletionSafetyTest;

use Ghijk\CpNotifications\Contracts\AcknowledgementRepository;
use Illuminate\Console\Scheduling\Schedule;

test('no automatic workflow can hard delete notices or acknowledgements', function () {
    $repositoryMethods = collect((new \ReflectionClass(AcknowledgementRepository::class))->getMethods())
        ->pluck('name');
    $automaticSources = collect([
        __DIR__.'/../src/Console/Commands/NudgeCommand.php',
        __DIR__.'/../src/Jobs/SendNotificationNudges.php',
        __DIR__.'/../src/Nudges/NotificationNudgeService.php',
    ])->map(fn (string $path): string => file_get_contents($path))->implode("\n");
    $scheduledCommands = collect($this->app->make(Schedule::class)->events())
        ->pluck('command')
        ->filter();

    expect($repositoryMethods->intersect(['delete', 'remove', 'revoke', 'purge']))->toBeEmpty();
    $this->assertStringNotContainsString('->delete(', $automaticSources);
    $this->assertStringNotContainsString('AcknowledgementRepository::', $automaticSources);
    expect($scheduledCommands->every(
        fn (string $command): bool => ! str_contains($command, 'purge'),
    ))->toBeTrue();
    $documentation = file_get_contents(__DIR__.'/../DOCUMENTATION.md');
    $this->assertStringContainsString('permanently removes', $documentation);
    $this->assertStringContainsString('rather than archiving', $documentation);
});
