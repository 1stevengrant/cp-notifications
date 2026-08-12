<?php

namespace Ghijk\CpNotifications\Tests;

use Ghijk\CpNotifications\ServiceProvider;
use Statamic\CP\Navigation\Nav as Navigation;
use Statamic\Facades\CP\Nav;

class NavigationTest extends TestCase
{
    public function test_it_registers_inbox_management_and_reporting_navigation(): void
    {
        $registeredNav = new Navigation;
        Nav::swap($registeredNav);
        (new ServiceProvider($this->app))->registerNavigation();

        $reflection = new \ReflectionClass($registeredNav);
        $extensions = $reflection->getProperty('extensions')->getValue($registeredNav);
        $navigation = new Navigation;
        $extension = $extensions[array_key_last($extensions)];

        $extension($navigation);

        $items = collect($navigation->items())->keyBy(fn ($item) => $item->display());

        $this->assertSame(['Inbox', 'Manage', 'Reports'], $items->keys()->all());
        $this->assertSame('Notifications', $items['Inbox']->section());
        $this->assertSame(cp_route('cp-notifications.inbox'), $items['Inbox']->url());
        $this->assertNull($items['Inbox']->authorization());
        $this->assertSame(cp_route('cp-notifications.manage'), $items['Manage']->url());
        $this->assertSame(cp_route('cp-notifications.reports'), $items['Reports']->url());
    }

    public function test_navigation_destinations_are_registered_cp_routes(): void
    {
        $this->assertTrue($this->app['router']->has('statamic.cp.cp-notifications.inbox'));
        $this->assertTrue($this->app['router']->has('statamic.cp.cp-notifications.manage'));
        $this->assertTrue($this->app['router']->has('statamic.cp.cp-notifications.reports'));
    }
}
