<?php

namespace Ghijk\CpNotifications\Tests\Pest\DocumentationTest;

test('installation and operations commands are documented', function () {
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
});

test('commercial production licensing is documented', function () {
    $readme = file_get_contents(__DIR__.'/../README.md');

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
        $this->assertStringContainsString($detail, $readme);
    }
});

test('file eloquent and auto driver behavior is documented', function () {
    $readme = file_get_contents(__DIR__.'/../README.md');

    $this->assertStringContainsString('CP_NOTIFICATIONS_DRIVER', $readme);
    $this->assertStringContainsString('storage/statamic/cp-notifications', $readme);
    $this->assertStringContainsString('statamic/eloquent-driver', $readme);
    $this->assertStringContainsString('auto` — the default', $readme);
    $this->assertStringContainsString('does not migrate existing records', $readme);
});

test('enforcement modes and bypass implications are documented', function () {
    $readme = file_get_contents(__DIR__.'/../README.md');

    $this->assertStringContainsString('CP_NOTIFICATIONS_ENFORCEMENT', $readme);
    $this->assertStringContainsString('`strict` (default)', $readme);
    $this->assertStringContainsString('`modal`', $readme);
    $this->assertStringContainsString('bypass notifications', $readme);
    $this->assertStringContainsString('does not hide notices', $readme);
});

test('notice targeting reporting nudge and purge workflows are documented', function () {
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
});

test('blocking end date expiry risk is documented', function () {
    $readme = file_get_contents(__DIR__.'/../README.md');

    $this->assertStringContainsString('Blocking notice expiry', $readme);
    $this->assertStringContainsString('stops gating users', $readme);
    $this->assertStringContainsString('never acknowledged it', $readme);
});

test('locked notice and superseding correction workflow is documented', function () {
    $readme = file_get_contents(__DIR__.'/../README.md');

    $this->assertStringContainsString('first acknowledgement locks a notice', $readme);
    $this->assertStringContainsString('read-only', $readme);
    $this->assertStringContainsString('superseding notice', $readme);
    $this->assertStringContainsString('historical evidence', $readme);
});

test('verified compatibility matrix is documented', function () {
    $readme = file_get_contents(__DIR__.'/../README.md');
    $overlay = file_get_contents(__DIR__.'/../resources/js/components/NotificationOverlay.vue');
    $packageLock = json_decode(file_get_contents(__DIR__.'/../package-lock.json'), true, flags: JSON_THROW_ON_ERROR);

    $this->assertStringContainsString('Statamic 6.27', $readme);
    $this->assertStringContainsString('Laravel 13.25', $readme);
    $this->assertStringContainsString('statamic/eloquent-driver` 5.11', $readme);
    expect($packageLock['packages']['node_modules/vue']['version'])->toStartWith('3.');

    foreach (['<ui-card', '<ui-badge', '<ui-button'] as $component) {
        $this->assertStringContainsString($component, $overlay);
    }
});

test('recurring notices are explicitly out of v1 scope', function () {
    $readme = file_get_contents(__DIR__.'/../README.md');
    $installer = file_get_contents(__DIR__.'/../src/Content/NotificationCollectionInstaller.php');

    $this->assertStringContainsString('Notices do not recur', $readme);
    $this->assertStringContainsString('one `start_date`/`end_date`', $readme);
    $this->assertStringContainsString('publication', $readme);
    $this->assertStringNotContainsString("'handle' => 'recurrence'", $installer);
    $this->assertStringNotContainsString("'handle' => 'repeat_rule'", $installer);
});

test('per user timezone scheduling is explicitly out of v1 scope', function () {
    $readme = file_get_contents(__DIR__.'/../README.md');
    $installer = file_get_contents(__DIR__.'/../src/Content/NotificationCollectionInstaller.php');

    $this->assertStringContainsString("application's configured timezone", $readme);
    $this->assertStringContainsString('does not offer per-user timezone scheduling', $readme);
    $this->assertStringNotContainsString("'handle' => 'user_timezone'", $installer);
    $this->assertStringNotContainsString("'handle' => 'timezone'", $installer);
});

test('compliance product framing is explicitly out of v1 scope', function () {
    $readme = file_get_contents(__DIR__.'/../README.md');
    $installer = file_get_contents(__DIR__.'/../src/Content/NotificationCollectionInstaller.php');

    $this->assertStringContainsString('operational record of acknowledgements', $readme);
    $this->assertStringContainsString('not presented as a dedicated compliance or attestation product', $readme);
    $this->assertStringContainsString('regulated evidence workflow is outside v1', $readme);
    $this->assertStringNotContainsString("'handle' => 'compliance_framework'", $installer);
    $this->assertStringNotContainsString("'handle' => 'attestation'", $installer);
});
