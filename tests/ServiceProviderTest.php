<?php

namespace Ghijk\CpNotifications\Tests;

use Ghijk\CpNotifications\Contracts\AcknowledgementRepository;
use Ghijk\CpNotifications\Contracts\SnoozeRepository;
use Ghijk\CpNotifications\Repositories\EloquentAcknowledgementRepository;
use Ghijk\CpNotifications\Repositories\EloquentSnoozeRepository;
use Ghijk\CpNotifications\Repositories\FileAcknowledgementRepository;
use Ghijk\CpNotifications\Repositories\FileSnoozeRepository;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use Illuminate\Support\Facades\Artisan;

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
        $this->assertArrayHasKey('cp-notifications:install', Artisan::all());
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

    public function test_it_binds_file_repositories_as_singletons(): void
    {
        config()->set('cp-notifications.acknowledgements.driver', 'file');
        $this->app->forgetInstance(AcknowledgementRepository::class);
        $this->app->forgetInstance(SnoozeRepository::class);

        $acknowledgements = $this->app->make(AcknowledgementRepository::class);
        $snoozes = $this->app->make(SnoozeRepository::class);

        $this->assertInstanceOf(FileAcknowledgementRepository::class, $acknowledgements);
        $this->assertInstanceOf(FileSnoozeRepository::class, $snoozes);
        $this->assertSame($acknowledgements, $this->app->make(AcknowledgementRepository::class));
        $this->assertSame($snoozes, $this->app->make(SnoozeRepository::class));
    }

    public function test_it_binds_eloquent_repositories_as_singletons(): void
    {
        config()->set('cp-notifications.acknowledgements.driver', 'eloquent');
        $this->app->forgetInstance(AcknowledgementRepository::class);
        $this->app->forgetInstance(SnoozeRepository::class);

        $this->assertInstanceOf(
            EloquentAcknowledgementRepository::class,
            $this->app->make(AcknowledgementRepository::class),
        );
        $this->assertInstanceOf(
            EloquentSnoozeRepository::class,
            $this->app->make(SnoozeRepository::class),
        );
    }
}
