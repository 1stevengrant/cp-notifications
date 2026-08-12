<?php

namespace Ghijk\CpNotifications\Tests;

use Ghijk\CpNotifications\ServiceProvider;

class PackageScaffoldTest extends TestCase
{
    public function test_it_has_the_expected_statamic_addon_metadata(): void
    {
        $composer = json_decode(
            file_get_contents(__DIR__.'/../composer.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertSame('ghijk/cp-notifications', $composer['name']);
        $this->assertSame('statamic-addon', $composer['type']);
        $this->assertSame('^8.3', $composer['require']['php']);
        $this->assertSame('^6.0', $composer['require']['statamic/cms']);
        $this->assertSame(
            ServiceProvider::class,
            $composer['extra']['laravel']['providers'][0],
        );
    }

    public function test_the_addon_service_provider_is_registered(): void
    {
        $this->assertInstanceOf(
            ServiceProvider::class,
            $this->app->getProvider(ServiceProvider::class),
        );
    }
}
