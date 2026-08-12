<?php

namespace Ghijk\CpNotifications\Tests\Pest\DocumentationTest;

test('installation and operations commands are documented', function () {
    $documentation = file_get_contents(__DIR__.'/../DOCUMENTATION.md');

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
        $this->assertStringContainsString($command, $documentation);
    }
});

test('commercial production licensing is documented', function () {
    $documentation = file_get_contents(__DIR__.'/../DOCUMENTATION.md');

    foreach ([
        'proprietary commercial software',
        'US$75',
        'per production site',
        'Statamic Marketplace',
        'One license is required for each production installation',
        'without a license on local development sites',
        'STATAMIC_LICENSE_KEY',
        'does not use a separate addon license key',
    ] as $detail) {
        $this->assertStringContainsString($detail, $documentation);
    }
});

test('file eloquent and auto driver behavior is documented', function () {
    $documentation = file_get_contents(__DIR__.'/../DOCUMENTATION.md');

    $this->assertStringContainsString('CP_NOTIFICATIONS_DRIVER', $documentation);
    $this->assertStringContainsString('storage/statamic/cp-notifications', $documentation);
    $this->assertStringContainsString('statamic/eloquent-driver', $documentation);
    $this->assertStringContainsString('auto` — the default', $documentation);
    $this->assertStringContainsString('does not migrate existing records', $documentation);
});

test('enforcement modes and bypass implications are documented', function () {
    $documentation = file_get_contents(__DIR__.'/../DOCUMENTATION.md');

    $this->assertStringContainsString('CP_NOTIFICATIONS_ENFORCEMENT', $documentation);
    $this->assertStringContainsString('`strict` (default)', $documentation);
    $this->assertStringContainsString('`modal`', $documentation);
    $this->assertStringContainsString('bypass notifications', $documentation);
    $this->assertStringContainsString('does not hide notices', $documentation);
});

test('notice targeting reporting nudge and purge workflows are documented', function () {
    $documentation = file_get_contents(__DIR__.'/../DOCUMENTATION.md');

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
        $this->assertStringContainsString($detail, $documentation);
    }
});

test('blocking end date expiry risk is documented', function () {
    $documentation = file_get_contents(__DIR__.'/../DOCUMENTATION.md');

    $this->assertStringContainsString('Blocking notice expiry', $documentation);
    $this->assertStringContainsString('stops gating users', $documentation);
    $this->assertStringContainsString('never acknowledged it', $documentation);
});

test('locked notice and superseding correction workflow is documented', function () {
    $documentation = file_get_contents(__DIR__.'/../DOCUMENTATION.md');

    $this->assertStringContainsString('first acknowledgement locks a notice', $documentation);
    $this->assertStringContainsString('read-only', $documentation);
    $this->assertStringContainsString('superseding notice', $documentation);
    $this->assertStringContainsString('historical evidence', $documentation);
});

test('verified compatibility matrix is documented', function () {
    $documentation = file_get_contents(__DIR__.'/../DOCUMENTATION.md');
    $overlay = file_get_contents(__DIR__.'/../resources/js/components/NotificationOverlay.vue');
    $packageLock = json_decode(file_get_contents(__DIR__.'/../package-lock.json'), true, flags: JSON_THROW_ON_ERROR);

    $this->assertStringContainsString('Statamic 6.27', $documentation);
    $this->assertStringContainsString('Laravel 13.25', $documentation);
    $this->assertStringContainsString('statamic/eloquent-driver` 5.11', $documentation);
    expect($packageLock['packages']['node_modules/vue']['version'])->toStartWith('3.');

    foreach (['<ui-card', '<ui-badge', '<ui-button'] as $component) {
        $this->assertStringContainsString($component, $overlay);
    }
});

test('recurring notices are explicitly out of v1 scope', function () {
    $documentation = file_get_contents(__DIR__.'/../DOCUMENTATION.md');
    $installer = file_get_contents(__DIR__.'/../src/Content/NotificationCollectionInstaller.php');

    $this->assertStringContainsString('Notices do not recur', $documentation);
    $this->assertStringContainsString('one `start_date`/`end_date`', $documentation);
    $this->assertStringContainsString('publication', $documentation);
    $this->assertStringNotContainsString("'handle' => 'recurrence'", $installer);
    $this->assertStringNotContainsString("'handle' => 'repeat_rule'", $installer);
});

test('per user timezone scheduling is explicitly out of v1 scope', function () {
    $documentation = file_get_contents(__DIR__.'/../DOCUMENTATION.md');
    $installer = file_get_contents(__DIR__.'/../src/Content/NotificationCollectionInstaller.php');

    $this->assertStringContainsString("application's configured timezone", $documentation);
    $this->assertStringContainsString('does not offer per-user timezone scheduling', $documentation);
    $this->assertStringNotContainsString("'handle' => 'user_timezone'", $installer);
    $this->assertStringNotContainsString("'handle' => 'timezone'", $installer);
});

test('compliance product framing is explicitly out of v1 scope', function () {
    $documentation = file_get_contents(__DIR__.'/../DOCUMENTATION.md');
    $installer = file_get_contents(__DIR__.'/../src/Content/NotificationCollectionInstaller.php');

    $this->assertStringContainsString('operational record of acknowledgements', $documentation);
    $this->assertStringContainsString('not presented as a dedicated compliance or attestation product', $documentation);
    $this->assertStringContainsString('regulated evidence workflow is outside v1', $documentation);
    $this->assertStringNotContainsString("'handle' => 'compliance_framework'", $installer);
    $this->assertStringNotContainsString("'handle' => 'attestation'", $installer);
});
