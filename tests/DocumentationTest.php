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

    public function test_enforcement_modes_and_bypass_implications_are_documented(): void
    {
        $readme = file_get_contents(__DIR__.'/../README.md');

        $this->assertStringContainsString('CP_NOTIFICATIONS_ENFORCEMENT', $readme);
        $this->assertStringContainsString('`strict` (default)', $readme);
        $this->assertStringContainsString('`modal`', $readme);
        $this->assertStringContainsString('bypass notifications', $readme);
        $this->assertStringContainsString('does not hide notices', $readme);
    }

    public function test_notice_targeting_reporting_nudge_and_purge_workflows_are_documented(): void
    {
        $readme = file_get_contents(__DIR__.'/../README.md');

        foreach ([
            'Bard body',
            'roles, groups, explicit users',
            '`start_date`',
            'application timezone',
            'Notifications → Inbox',
            'Export CSV',
            'Remind non-ackers',
            '`purge notifications`',
            'structured audit log',
        ] as $detail) {
            $this->assertStringContainsString($detail, $readme);
        }
    }

    public function test_blocking_end_date_expiry_risk_is_documented(): void
    {
        $readme = file_get_contents(__DIR__.'/../README.md');

        $this->assertStringContainsString('Blocking notice expiry', $readme);
        $this->assertStringContainsString('stops gating users', $readme);
        $this->assertStringContainsString('never acknowledged it', $readme);
    }
}
