<?php

namespace Ghijk\CpNotifications\Tests;

use Ghijk\CpNotifications\Http\Controllers\BlockingInterstitialController;
use Ghijk\CpNotifications\Notifications\BlockingNoticeResolver;

class BlockingInterstitialTest extends TestCase
{
    public function test_interstitial_route_and_view_are_available(): void
    {
        $route = $this->app['router']->getRoutes()->getByName('statamic.cp.cp-notifications.acknowledge');
        $view = file_get_contents(__DIR__.'/../resources/views/blocking.blade.php');

        $this->assertNotNull($route);
        $this->assertSame(BlockingInterstitialController::class, $route->getActionName());
        $this->assertStringContainsString('notification-interstitial', $view);
        $this->assertStringContainsString('Acknowledgement required', $view);
        $this->assertStringContainsString('must be acknowledged', $view);
    }

    public function test_blocking_resolver_is_composed_from_active_and_gating_stacks(): void
    {
        $source = file_get_contents(__DIR__.'/../src/Notifications/BlockingNoticeResolver.php');

        $this->assertStringContainsString('ActiveStackResolver $active', $source);
        $this->assertStringContainsString('GatingStack $gating', $source);
        $this->assertStringContainsString("get('blocking', false)", $source);
    }
}
