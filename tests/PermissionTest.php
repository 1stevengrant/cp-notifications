<?php

namespace Ghijk\CpNotifications\Tests;

use Ghijk\CpNotifications\ServiceProvider;
use Statamic\Auth\Permissions;
use Statamic\Facades\Permission;

class PermissionTest extends TestCase
{
    public function test_view_notifications_is_registered_and_inbox_access_is_default(): void
    {
        $permissions = new Permissions;
        Permission::swap($permissions);
        (new ServiceProvider($this->app))->registerPermissions();

        $registered = $permissions->boot();

        $this->assertSame(
            'View own notification inbox',
            $registered->get('view notifications')->label(),
        );
        $this->assertSame(
            'cp-notifications',
            $registered->get('view notifications')->group(),
        );
    }

    public function test_manage_notifications_is_registered(): void
    {
        $registered = $this->registeredPermissions();

        $this->assertSame(
            'Manage notifications',
            $registered->get('manage notifications')->label(),
        );
        $this->assertSame(
            'cp-notifications',
            $registered->get('manage notifications')->group(),
        );
    }

    public function test_view_notification_reports_is_registered(): void
    {
        $registered = $this->registeredPermissions();

        $this->assertSame(
            'View notification reports',
            $registered->get('view notification reports')->label(),
        );
        $this->assertSame(
            'cp-notifications',
            $registered->get('view notification reports')->group(),
        );
    }

    private function registeredPermissions(): Permissions
    {
        $permissions = new Permissions;
        Permission::swap($permissions);
        (new ServiceProvider($this->app))->registerPermissions();

        return $permissions->boot();
    }
}
