<?php

namespace Ghijk\CpNotifications\Tests;

class DocumentationTest extends TestCase
{
    public function test_installation_and_operations_commands_are_documented(): void
    {
        $readme = file_get_contents(__DIR__.'/../README.md');

        foreach ([
            'composer require ghijk/cp-notifications',
            'cp-notifications:install',
            '--tag=cp-notifications-config',
            '--tag=cp-notifications-migrations',
            'php artisan migrate',
            'npm ci',
            'npm run build',
            'schedule:run',
            'queue:work',
        ] as $command) {
            $this->assertStringContainsString($command, $readme);
        }
    }

    public function test_file_eloquent_and_auto_driver_behavior_is_documented(): void
    {
        $readme = file_get_contents(__DIR__.'/../README.md');

        $this->assertStringContainsString('CP_NOTIFICATIONS_DRIVER', $readme);
        $this->assertStringContainsString('storage/statamic/cp-notifications', $readme);
        $this->assertStringContainsString('statamic/eloquent-driver', $readme);
        $this->assertStringContainsString('auto` — the default', $readme);
        $this->assertStringContainsString('does not migrate existing records', $readme);
    }
}
