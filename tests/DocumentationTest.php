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

    public function test_locked_notice_and_superseding_correction_workflow_is_documented(): void
    {
        $readme = file_get_contents(__DIR__.'/../README.md');

        $this->assertStringContainsString('first acknowledgement locks a notice', $readme);
        $this->assertStringContainsString('read-only', $readme);
        $this->assertStringContainsString('superseding notice', $readme);
        $this->assertStringContainsString('historical evidence', $readme);
    }

    public function test_verified_compatibility_matrix_is_documented(): void
    {
        $readme = file_get_contents(__DIR__.'/../README.md');
        $overlay = file_get_contents(__DIR__.'/../resources/js/components/NotificationOverlay.vue');
        $packageLock = json_decode(file_get_contents(__DIR__.'/../package-lock.json'), true, flags: JSON_THROW_ON_ERROR);

        $this->assertStringContainsString('Statamic 6.27', $readme);
        $this->assertStringContainsString('Laravel 13.25', $readme);
        $this->assertStringContainsString('statamic/eloquent-driver` 5.11', $readme);
        $this->assertStringStartsWith('3.', $packageLock['packages']['node_modules/vue']['version']);

        foreach (['<ui-card', '<ui-badge', '<ui-button'] as $component) {
            $this->assertStringContainsString($component, $overlay);
        }
    }

    public function test_recurring_notices_are_explicitly_out_of_v1_scope(): void
    {
        $readme = file_get_contents(__DIR__.'/../README.md');
        $installer = file_get_contents(__DIR__.'/../src/Content/NotificationCollectionInstaller.php');

        $this->assertStringContainsString('Notices do not recur', $readme);
        $this->assertStringContainsString('one `start_date`/`end_date`', $readme);
        $this->assertStringContainsString('publication', $readme);
        $this->assertStringNotContainsString("'handle' => 'recurrence'", $installer);
        $this->assertStringNotContainsString("'handle' => 'repeat_rule'", $installer);
    }

    public function test_per_user_timezone_scheduling_is_explicitly_out_of_v1_scope(): void
    {
        $readme = file_get_contents(__DIR__.'/../README.md');
        $installer = file_get_contents(__DIR__.'/../src/Content/NotificationCollectionInstaller.php');

        $this->assertStringContainsString("application's configured timezone", $readme);
        $this->assertStringContainsString('does not offer per-user timezone scheduling', $readme);
        $this->assertStringNotContainsString("'handle' => 'user_timezone'", $installer);
        $this->assertStringNotContainsString("'handle' => 'timezone'", $installer);
    }

    public function test_compliance_product_framing_is_explicitly_out_of_v1_scope(): void
    {
        $readme = file_get_contents(__DIR__.'/../README.md');
        $installer = file_get_contents(__DIR__.'/../src/Content/NotificationCollectionInstaller.php');

        $this->assertStringContainsString('operational record of acknowledgements', $readme);
        $this->assertStringContainsString('not presented as a dedicated compliance or attestation product', $readme);
        $this->assertStringContainsString('regulated evidence workflow is outside v1', $readme);
        $this->assertStringNotContainsString("'handle' => 'compliance_framework'", $installer);
        $this->assertStringNotContainsString("'handle' => 'attestation'", $installer);
    }
}
