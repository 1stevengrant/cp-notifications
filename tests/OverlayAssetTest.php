<?php

namespace Ghijk\CpNotifications\Tests\Pest\OverlayAssetTest;

test('global vue overlay is registered and appended', function () {
    $entry = file_get_contents(__DIR__.'/../resources/js/addon.js');
    $component = file_get_contents(__DIR__.'/../resources/js/components/NotificationOverlay.vue');

    $this->assertStringContainsString("register('cp-notification-overlay'", $entry);
    $this->assertStringContainsString("append('cp-notification-overlay'", $entry);
    $this->assertStringContainsString('<ui-card', $component);
    $this->assertStringContainsString('<ui-badge', $component);
    $this->assertStringContainsString("cp_url('cp-notifications/api/stack')", $component);
    $this->assertStringContainsString('aria-modal="true"', $component);
});

test('overlay renders only the first ordered notice', function () {
    $component = file_get_contents(__DIR__.'/../resources/js/components/NotificationOverlay.vue');

    $this->assertStringContainsString('return this.notices[0] ?? null', $component);
    $this->assertStringNotContainsString('v-for=', $component);
    expect(substr_count($component, 'data-testid="cp-notification-current"'))->toBe(1);
});

test('overlay renders augmented bard html', function () {
    $component = file_get_contents(__DIR__.'/../resources/js/components/NotificationOverlay.vue');

    $this->assertStringContainsString('v-if="current.body_html"', $component);
    $this->assertStringContainsString('v-html="current.body_html"', $component);
    $this->assertStringContainsString('{{ legacyBody }}', $component);
    $this->assertStringContainsString("this.current.body.map(text).filter(Boolean).join('\\n\\n')", $component);
    $this->assertStringNotContainsString('JSON.stringify(this.current.body)', $component);
    $this->assertStringContainsString(':deep(ul)', $component);
    $this->assertStringContainsString(':deep(a)', $component);
});

test('blocking notice has only explicit confirmation controls', function () {
    $component = file_get_contents(__DIR__.'/../resources/js/components/NotificationOverlay.vue');

    $this->assertStringContainsString('I have read and understand', $component);
    $this->assertStringContainsString(':disabled="!confirmed || submitting"', $component);
    $this->assertStringContainsString('JSON.stringify({ confirmed: true })', $component);
    $this->assertStringNotContainsString('Dismiss', $component);
    $this->assertStringContainsString('this.current?.snoozeable && !this.current?.blocking', $component);
});

test('eligible advisory can be confirmed or snoozed', function () {
    $component = file_get_contents(__DIR__.'/../resources/js/components/NotificationOverlay.vue');

    $this->assertStringContainsString('v-if="canSnooze"', $component);
    $this->assertStringContainsString('Snooze for 24 hours', $component);
    $this->assertStringContainsString('async snooze()', $component);
    $this->assertStringContainsString('/snooze`)', $component);
    $this->assertStringContainsString('@click="confirm"', $component);
});

test('each successful action refreshes and advances the stack', function () {
    $component = file_get_contents(__DIR__.'/../resources/js/components/NotificationOverlay.vue');

    expect(substr_count($component, 'await this.handleActionResponse(response);'))->toBe(2);
    $this->assertStringContainsString('this.confirmed = false;', $component);
    $this->assertStringContainsString('await this.refresh();', $component);
    $this->assertStringNotContainsString('this.notices.shift()', $component);
});

test('stale and concurrent actions reconcile gracefully', function () {
    $component = file_get_contents(__DIR__.'/../resources/js/components/NotificationOverlay.vue');

    $this->assertStringContainsString('[404, 409].includes(response.status)', $component);
    $this->assertStringContainsString('role="alert"', $component);
    expect(substr_count($component, 'Check your connection and try again.'))->toBe(2);
    $this->assertStringContainsString("payload.message ?? 'This notification could not be updated", $component);
});
