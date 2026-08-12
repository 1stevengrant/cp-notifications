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
}
