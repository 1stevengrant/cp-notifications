<?php

namespace Ghijk\CpNotifications\Tests\Pest\PermissionTest;

use Ghijk\CpNotifications\ServiceProvider;
use Statamic\Auth\Permissions;
use Statamic\Facades\Permission;

test('view notifications is registered and inbox access is default', function () {
    $permissions = new Permissions;
    Permission::swap($permissions);
    (new ServiceProvider($this->app))->registerPermissions();

    $registered = $permissions->boot();

    expect($registered->get('view notifications')->label())->toBe('View own notification inbox');
    expect($registered->get('view notifications')->group())->toBe('cp-notifications');
});

test('manage notifications is registered', function () {
    $registered = registeredPermissions($this->app);

    expect($registered->get('manage notifications')->label())->toBe('Manage notifications');
    expect($registered->get('manage notifications')->group())->toBe('cp-notifications');
});

test('view notification reports is registered', function () {
    $registered = registeredPermissions($this->app);

    expect($registered->get('view notification reports')->label())->toBe('View notification reports');
    expect($registered->get('view notification reports')->group())->toBe('cp-notifications');
});

test('bypass notifications is registered', function () {
    $registered = registeredPermissions($this->app);

    expect($registered->get('bypass notifications')->label())->toBe('Bypass notification enforcement');
    expect($registered->get('bypass notifications')->group())->toBe('cp-notifications');
});

test('purge notifications is registered', function () {
    $registered = registeredPermissions($this->app);

    expect($registered->get('purge notifications')->label())->toBe('Purge expired notifications');
    expect($registered->get('purge notifications')->group())->toBe('cp-notifications');
});

function registeredPermissions($app): Permissions
{
    $permissions = new Permissions;
    Permission::swap($permissions);
    (new ServiceProvider($app))->registerPermissions();

    return $permissions->boot();
}
