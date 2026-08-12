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

    public function test_overlay_renders_augmented_bard_html(): void
    {
        $component = file_get_contents(__DIR__.'/../resources/js/components/NotificationOverlay.vue');

        $this->assertStringContainsString('v-html="current.body_html"', $component);
        $this->assertStringNotContainsString('JSON.stringify(this.current.body)', $component);
        $this->assertStringContainsString(':deep(ul)', $component);
        $this->assertStringContainsString(':deep(a)', $component);
    }

    public function test_blocking_notice_has_only_explicit_confirmation_controls(): void
    {
        $component = file_get_contents(__DIR__.'/../resources/js/components/NotificationOverlay.vue');

        $this->assertStringContainsString('I have read and understand', $component);
        $this->assertStringContainsString(':disabled="!confirmed || submitting"', $component);
        $this->assertStringContainsString('JSON.stringify({ confirmed: true })', $component);
        $this->assertStringNotContainsString('Dismiss', $component);
        $this->assertStringContainsString('this.current?.snoozeable && !this.current?.blocking', $component);
    }

    public function test_eligible_advisory_can_be_confirmed_or_snoozed(): void
    {
        $component = file_get_contents(__DIR__.'/../resources/js/components/NotificationOverlay.vue');

        $this->assertStringContainsString('v-if="canSnooze"', $component);
        $this->assertStringContainsString('Snooze for 24 hours', $component);
        $this->assertStringContainsString('async snooze()', $component);
        $this->assertStringContainsString('/snooze`)', $component);
        $this->assertStringContainsString('@click="confirm"', $component);
    }

    public function test_each_successful_action_refreshes_and_advances_the_stack(): void
    {
        $component = file_get_contents(__DIR__.'/../resources/js/components/NotificationOverlay.vue');

        $this->assertSame(2, substr_count($component, 'await this.handleActionResponse(response);'));
        $this->assertStringContainsString('this.confirmed = false;', $component);
        $this->assertStringContainsString('await this.refresh();', $component);
        $this->assertStringNotContainsString('this.notices.shift()', $component);
    }

    public function test_stale_and_concurrent_actions_reconcile_gracefully(): void
    {
        $component = file_get_contents(__DIR__.'/../resources/js/components/NotificationOverlay.vue');

        $this->assertStringContainsString('[404, 409].includes(response.status)', $component);
        $this->assertStringContainsString('role="alert"', $component);
        $this->assertSame(2, substr_count($component, 'Check your connection and try again.'));
        $this->assertStringContainsString("payload.message ?? 'This notification could not be updated", $component);
    }
}
