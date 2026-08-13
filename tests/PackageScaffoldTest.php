<?php

namespace Ghijk\CpNotifications\Tests\Pest\PackageScaffoldTest;

use Ghijk\CpNotifications\ServiceProvider;

test('it has the expected statamic addon metadata', function () {
    $composer = json_decode(
        file_get_contents(__DIR__.'/../composer.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect($composer['name'])->toBe('ghijk/cp-notifications');
    expect($composer['type'])->toBe('statamic-addon');
    expect($composer['license'])->toBe('proprietary');
    expect($composer['require']['php'])->toBe('^8.3');
    expect($composer['require']['statamic/cms'])->toBe('^6.0');
    expect($composer['extra']['laravel']['providers'][0])->toBe(ServiceProvider::class);
});

test('the addon service provider is registered', function () {
    expect($this->app->getProvider(ServiceProvider::class))->toBeInstanceOf(ServiceProvider::class);
});
