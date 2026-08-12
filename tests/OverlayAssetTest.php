<?php

namespace Ghijk\CpNotifications\Tests;

class OverlayAssetTest extends TestCase
{
    public function test_global_vue_overlay_is_registered_and_appended(): void
    {
        $entry = file_get_contents(__DIR__.'/../resources/js/addon.js');
        $component = file_get_contents(__DIR__.'/../resources/js/components/NotificationOverlay.vue');

        $this->assertStringContainsString("register('cp-notification-overlay'", $entry);
        $this->assertStringContainsString("append('cp-notification-overlay'", $entry);
        $this->assertStringContainsString('<ui-card', $component);
        $this->assertStringContainsString('<ui-badge', $component);
        $this->assertStringContainsString("cp_url('cp-notifications/api/stack')", $component);
        $this->assertStringContainsString('aria-modal="true"', $component);
    }

    public function test_overlay_renders_only_the_first_ordered_notice(): void
    {
        $component = file_get_contents(__DIR__.'/../resources/js/components/NotificationOverlay.vue');

        $this->assertStringContainsString('return this.notices[0] ?? null', $component);
        $this->assertStringNotContainsString('v-for=', $component);
        $this->assertSame(1, substr_count($component, 'data-testid="cp-notification-current"'));
    }

    public function test_blocking_notice_has_only_explicit_confirmation_controls(): void
    {
        $component = file_get_contents(__DIR__.'/../resources/js/components/NotificationOverlay.vue');

        $this->assertStringContainsString('I have read and understand', $component);
        $this->assertStringContainsString(':disabled="!confirmed || submitting"', $component);
        $this->assertStringContainsString('JSON.stringify({ confirmed: true })', $component);
        $this->assertStringNotContainsString('Dismiss', $component);
        $this->assertStringNotContainsString('current.blocking', $component);
        $this->assertStringNotContainsString('snooze(', $component);
    }
}
