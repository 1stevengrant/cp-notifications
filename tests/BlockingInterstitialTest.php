<?php

namespace Ghijk\CpNotifications\Tests\Pest\BlockingInterstitialTest;

use Ghijk\CpNotifications\Http\Controllers\BlockingInterstitialController;

test('interstitial route and view are available', function () {
    $route = $this->app['router']->getRoutes()->getByName('statamic.cp.cp-notifications.acknowledge');
    $view = file_get_contents(__DIR__.'/../resources/views/blocking.blade.php');

    expect($route)->not->toBeNull();
    expect($route->getActionName())->toBe(BlockingInterstitialController::class);
    $this->assertStringContainsString('notification-interstitial', $view);
    $this->assertStringContainsString('Acknowledgement required', $view);
    $this->assertStringContainsString('must be acknowledged', $view);
});

test('blocking resolver is composed from active and gating stacks', function () {
    $source = file_get_contents(__DIR__.'/../src/Notifications/BlockingNoticeResolver.php');

    $this->assertStringContainsString('ActiveStackResolver $active', $source);
    $this->assertStringContainsString('GatingStack $gating', $source);
    $this->assertStringContainsString("get('blocking', false)", $source);
});
