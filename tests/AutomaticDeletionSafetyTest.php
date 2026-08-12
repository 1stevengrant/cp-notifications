<?php

namespace Ghijk\CpNotifications\Tests;

use Ghijk\CpNotifications\Contracts\AcknowledgementRepository;
use Illuminate\Console\Scheduling\Schedule;
use ReflectionClass;

class AutomaticDeletionSafetyTest extends TestCase
{
    public function test_no_automatic_workflow_can_hard_delete_notices_or_acknowledgements(): void
    {
        $repositoryMethods = collect((new ReflectionClass(AcknowledgementRepository::class))->getMethods())
            ->pluck('name');
        $automaticSources = collect([
            __DIR__.'/../src/Console/Commands/NudgeCommand.php',
            __DIR__.'/../src/Jobs/SendNotificationNudges.php',
            __DIR__.'/../src/Nudges/NotificationNudgeService.php',
        ])->map(fn (string $path): string => file_get_contents($path))->implode("\n");
        $scheduledCommands = collect($this->app->make(Schedule::class)->events())
            ->pluck('command')
            ->filter();

        $this->assertEmpty($repositoryMethods->intersect(['delete', 'remove', 'revoke', 'purge']));
        $this->assertStringNotContainsString('->delete(', $automaticSources);
        $this->assertStringNotContainsString('AcknowledgementRepository::', $automaticSources);
        $this->assertTrue($scheduledCommands->every(
            fn (string $command): bool => ! str_contains($command, 'purge'),
        ));
    }
}
