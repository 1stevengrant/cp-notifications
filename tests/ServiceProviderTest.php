<?php

namespace Ghijk\CpNotifications\Tests;

use Illuminate\Console\Command;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProviderTest extends TestCase
{
    public function test_it_merges_and_publishes_its_config(): void
    {
        $this->assertSame('auto', config('cp-notifications.acknowledgements.driver'));
        $this->assertSame(
            storage_path('statamic/cp-notifications'),
            config('cp-notifications.acknowledgements.file_path'),
        );
        $this->assertSame('strict', config('cp-notifications.enforcement'));
        $this->assertNull(config('cp-notifications.retention.inbox_days'));
        $this->assertNull(config('cp-notifications.nudge.from_address'));

        $this->assertContains(
            'cp-notifications-config',
            LaravelServiceProvider::publishableGroups(),
        );
        $this->assertContains(
            config_path('cp-notifications.php'),
            array_values(LaravelServiceProvider::pathsToPublish(
                group: 'cp-notifications-config',
            )),
        );
    }

    public function test_it_registers_its_install_command(): void
    {
        $command = $this->artisan('cp-notifications:install');

        $command->expectsOutputToContain('CP Notifications is registered.')
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_it_registers_migration_publishing(): void
    {
        $this->assertContains(
            'cp-notifications-migrations',
            LaravelServiceProvider::publishableGroups(),
        );
        $this->assertContains(
            database_path('migrations'),
            array_values(LaravelServiceProvider::pathsToPublish(
                group: 'cp-notifications-migrations',
            )),
        );
    }

    public function test_it_registers_cp_routes_and_vite_assets(): void
    {
        $provider = $this->app->getProvider(\Ghijk\CpNotifications\ServiceProvider::class);
        $reflection = new \ReflectionClass($provider);

        $routes = $reflection->getProperty('routes')->getValue($provider);
        $vite = $reflection->getProperty('vite')->getValue($provider);

        $this->assertFileExists($routes['cp']);
        $this->assertSame(
            ['resources/js/addon.js', 'resources/css/addon.css'],
            $vite['input'],
        );
        $this->assertFileExists(__DIR__.'/../resources/js/addon.js');
        $this->assertFileExists(__DIR__.'/../resources/css/addon.css');
    }
}
