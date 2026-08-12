<?php

namespace Ghijk\CpNotifications\Tests\Pest\SnoozeRepositoryContractTest;

use Carbon\CarbonImmutable;
use Ghijk\CpNotifications\Contracts\SnoozeRepository;
use Ghijk\CpNotifications\Data\Snooze;
use Ghijk\CpNotifications\Repositories\EloquentSnoozeRepository;
use Ghijk\CpNotifications\Repositories\FileSnoozeRepository;
use Illuminate\Support\Collection;

test('the contract exposes only single use record operations', function () {
    $reflection = new \ReflectionClass(SnoozeRepository::class);

    expect($reflection->isInterface())->toBeTrue();
    expect(array_map(fn (\ReflectionMethod $method) => $method->name, $reflection->getMethods()))->toBe(['find', 'record', 'forNotification', 'forUser']);
    expect((string) $reflection->getMethod('find')->getReturnType())->toBe('?'.Snooze::class);
    expect((string) $reflection->getMethod('record')->getReturnType())->toBe(Snooze::class);
    expect((string) $reflection->getMethod('forNotification')->getReturnType())->toBe(Collection::class);
    expect((string) $reflection->getMethod('forUser')->getReturnType())->toBe(Collection::class);

    $expiry = $reflection->getMethod('record')->getParameters()[2];
    expect($expiry->allowsNull())->toBeTrue();
    expect((string) $expiry->getType())->toBe('?'.CarbonImmutable::class);
});

test('concrete snooze drivers are final', function () {
    expect((new \ReflectionClass(EloquentSnoozeRepository::class))->isFinal())->toBeTrue();
    expect((new \ReflectionClass(FileSnoozeRepository::class))->isFinal())->toBeTrue();
});
