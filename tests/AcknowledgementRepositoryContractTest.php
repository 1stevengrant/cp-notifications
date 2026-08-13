<?php

namespace Ghijk\CpNotifications\Tests\Pest\AcknowledgementRepositoryContractTest;

use Carbon\CarbonImmutable;
use Ghijk\CpNotifications\Contracts\AcknowledgementRepository;
use Ghijk\CpNotifications\Data\Acknowledgement;
use Ghijk\CpNotifications\Repositories\EloquentAcknowledgementRepository;
use Ghijk\CpNotifications\Repositories\FileAcknowledgementRepository;
use Illuminate\Support\Collection;

test('the contract exposes only immutable record operations', function () {
    $reflection = new \ReflectionClass(AcknowledgementRepository::class);

    expect($reflection->isInterface())->toBeTrue();
    expect(array_map(fn (\ReflectionMethod $method) => $method->name, $reflection->getMethods()))->toBe(['find', 'record', 'forNotification', 'forUser']);
    expect((string) $reflection->getMethod('find')->getReturnType())->toBe('?'.Acknowledgement::class);
    expect((string) $reflection->getMethod('record')->getReturnType())->toBe(Acknowledgement::class);
    expect((string) $reflection->getMethod('forNotification')->getReturnType())->toBe(Collection::class);
    expect((string) $reflection->getMethod('forUser')->getReturnType())->toBe(Collection::class);

    $timestamp = $reflection->getMethod('record')->getParameters()[2];
    expect($timestamp->allowsNull())->toBeTrue();
    expect((string) $timestamp->getType())->toBe('?'.CarbonImmutable::class);
});

test('concrete drivers cannot expose acknowledgement mutation apis', function () {
    foreach ([EloquentAcknowledgementRepository::class, FileAcknowledgementRepository::class] as $driver) {
        $reflection = new \ReflectionClass($driver);
        $publicMethods = collect($reflection->getMethods(\ReflectionMethod::IS_PUBLIC))
            ->reject(fn (\ReflectionMethod $method): bool => $method->isConstructor())
            ->map(fn (\ReflectionMethod $method): string => $method->name)
            ->values()
            ->all();

        expect($reflection->isFinal())->toBeTrue();
        expect($publicMethods)->toEqualCanonicalizing(['find', 'record', 'forNotification', 'forUser']);
        expect(array_intersect(['update', 'revoke', 'delete', 'remove'], $publicMethods))->toBeEmpty();
    }
});
