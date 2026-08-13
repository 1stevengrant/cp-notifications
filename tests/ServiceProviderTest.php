<?php

namespace Ghijk\CpNotifications\Tests\Pest\ServiceProviderTest;

use Ghijk\CpNotifications\Contracts\AcknowledgementRepository;
use Ghijk\CpNotifications\Contracts\SnoozeRepository;
use Ghijk\CpNotifications\Repositories\EloquentAcknowledgementRepository;
use Ghijk\CpNotifications\Repositories\EloquentSnoozeRepository;
use Ghijk\CpNotifications\Repositories\FileAcknowledgementRepository;
use Ghijk\CpNotifications\Repositories\FileSnoozeRepository;
use Ghijk\CpNotifications\ServiceProvider;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

test('it merges and publishes its config', function () {
    expect(config('cp-notifications.acknowledgements.driver'))->toBe('auto');
    expect(config('cp-notifications.acknowledgements.file_path'))->toBe(storage_path('statamic/cp-notifications'));
    expect(config('cp-notifications.enforcement'))->toBe('strict');
    expect(config('cp-notifications.retention.inbox_days'))->toBeNull();
    expect(config('cp-notifications.nudge.from_address'))->toBeNull();

    expect(LaravelServiceProvider::publishableGroups())->toContain('cp-notifications-config');
    expect(array_values(LaravelServiceProvider::pathsToPublish(
        group: 'cp-notifications-config',
    )))->toContain(config_path('cp-notifications.php'));
});

test('it registers its install command', function () {
    expect(Artisan::all())->toHaveKey('cp-notifications:install');
});

test('it registers migration publishing', function () {
    expect(LaravelServiceProvider::publishableGroups())->toContain('cp-notifications-migrations');
    expect(array_values(LaravelServiceProvider::pathsToPublish(
        group: 'cp-notifications-migrations',
    )))->toContain(database_path('migrations'));
});

test('it registers cp routes and vite assets', function () {
    $provider = $this->app->getProvider(ServiceProvider::class);
    $reflection = new \ReflectionClass($provider);

    $routes = $reflection->getProperty('routes')->getValue($provider);
    $vite = $reflection->getProperty('vite')->getValue($provider);

    expect($routes['cp'])->toBeFile();
    expect($vite['input'])->toBe(['resources/js/addon.js', 'resources/css/addon.css']);
    expect(__DIR__.'/../resources/js/addon.js')->toBeFile();
    expect(__DIR__.'/../resources/css/addon.css')->toBeFile();
});

test('it binds file repositories as singletons', function () {
    config()->set('cp-notifications.acknowledgements.driver', 'file');
    $this->app->forgetInstance(AcknowledgementRepository::class);
    $this->app->forgetInstance(SnoozeRepository::class);

    $acknowledgements = $this->app->make(AcknowledgementRepository::class);
    $snoozes = $this->app->make(SnoozeRepository::class);

    expect($acknowledgements)->toBeInstanceOf(FileAcknowledgementRepository::class);
    expect($snoozes)->toBeInstanceOf(FileSnoozeRepository::class);
    expect($this->app->make(AcknowledgementRepository::class))->toBe($acknowledgements);
    expect($this->app->make(SnoozeRepository::class))->toBe($snoozes);
});

test('it binds eloquent repositories as singletons', function () {
    config()->set('cp-notifications.acknowledgements.driver', 'eloquent');
    $this->app->forgetInstance(AcknowledgementRepository::class);
    $this->app->forgetInstance(SnoozeRepository::class);

    expect($this->app->make(AcknowledgementRepository::class))->toBeInstanceOf(EloquentAcknowledgementRepository::class);
    expect($this->app->make(SnoozeRepository::class))->toBeInstanceOf(EloquentSnoozeRepository::class);
});
