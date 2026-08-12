<?php

namespace Ghijk\CpNotifications\Tests\Pest\NavigationTest;

use Ghijk\CpNotifications\ServiceProvider;
use Statamic\CP\Navigation\Nav as Navigation;
use Statamic\Facades\CP\Nav;

test('it registers inbox management and reporting navigation', function () {
    $registeredNav = new Navigation;
    Nav::swap($registeredNav);
    (new ServiceProvider($this->app))->registerNavigation();

    $reflection = new \ReflectionClass($registeredNav);
    $extensions = $reflection->getProperty('extensions')->getValue($registeredNav);
    $navigation = new Navigation;
    $extension = $extensions[array_key_last($extensions)];

    $extension($navigation);

    $items = collect($navigation->items())->keyBy(fn ($item) => $item->display());

    expect($items->keys()->all())->toBe(['Inbox', 'Manage', 'Reports']);
    expect($items['Inbox']->section())->toBe('Notifications');
    expect($items['Inbox']->url())->toBe(cp_route('cp-notifications.inbox'));
    expect($items['Inbox']->authorization())->toBeNull();
    expect($items['Manage']->url())->toBe(cp_route('cp-notifications.manage'));
    expect($items['Reports']->url())->toBe(cp_route('cp-notifications.reports'));
});

test('navigation destinations are registered cp routes', function () {
    expect($this->app['router']->has('statamic.cp.cp-notifications.inbox'))->toBeTrue();
    expect($this->app['router']->has('statamic.cp.cp-notifications.manage'))->toBeTrue();
    expect($this->app['router']->has('statamic.cp.cp-notifications.reports'))->toBeTrue();
});
