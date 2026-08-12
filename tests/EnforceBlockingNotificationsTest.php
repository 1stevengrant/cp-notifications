<?php

namespace Ghijk\CpNotifications\Tests;

use Ghijk\CpNotifications\Http\Middleware\EnforceBlockingNotifications;
use Ghijk\CpNotifications\ServiceProvider;

class EnforceBlockingNotificationsTest extends TestCase
{
    public function test_enforcement_middleware_is_registered_in_the_authenticated_cp_group(): void
    {
        $properties = (new \ReflectionClass(ServiceProvider::class))->getDefaultProperties();

        $this->assertSame(
            [EnforceBlockingNotifications::class],
            $properties['middlewareGroups']['statamic.cp.authenticated'],
        );
        $this->assertContains(
            EnforceBlockingNotifications::class,
            $this->app['router']->getMiddlewareGroups()['statamic.cp.authenticated'],
        );
    }

    public function test_middleware_uses_strict_configuration_and_live_blocking_resolution(): void
    {
        $source = file_get_contents(__DIR__.'/../src/Http/Middleware/EnforceBlockingNotifications.php');

        $this->assertStringContainsString("config('cp-notifications.enforcement') !== 'strict'", $source);
        $this->assertStringContainsString('$this->blocking->resolve($user)->isNotEmpty()', $source);
    }
}
