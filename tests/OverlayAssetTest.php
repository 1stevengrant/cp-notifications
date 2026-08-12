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
}
