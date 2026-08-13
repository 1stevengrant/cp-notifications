<?php

namespace Ghijk\CpNotifications\Tests\Pest\RepositoryDriverResolverTest;

use Composer\InstalledVersions;
use Ghijk\CpNotifications\Repositories\RepositoryDriverResolver;

test('explicit supported drivers are preserved', function () {
    $resolver = new RepositoryDriverResolver;

    expect($resolver->resolve('file', true))->toBe('file');
    expect($resolver->resolve('eloquent', false))->toBe('eloquent');
});

test('auto uses the eloquent driver when installed', function () {
    $resolver = new RepositoryDriverResolver;

    expect($resolver->resolve('auto', true))->toBe('eloquent');
    expect($resolver->resolve('auto', false))->toBe('file');
});

test('auto inspects composer when no detection override is given', function () {
    $resolver = new RepositoryDriverResolver;

    expect($resolver->resolve('auto'))->toBe(InstalledVersions::isInstalled('statamic/eloquent-driver') ? 'eloquent' : 'file');
});

test('unknown drivers are rejected', function () {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Unsupported CP Notifications repository driver [memory].');

    (new RepositoryDriverResolver)->resolve('memory');
});
