<?php

namespace Ghijk\CpNotifications\Tests;

use Carbon\CarbonImmutable;
use Ghijk\CpNotifications\Contracts\AcknowledgementRepository;
use Ghijk\CpNotifications\Data\Acknowledgement;
use Ghijk\CpNotifications\Repositories\EloquentAcknowledgementRepository;
use Ghijk\CpNotifications\Repositories\FileAcknowledgementRepository;
use Illuminate\Support\Collection;

class AcknowledgementRepositoryContractTest extends TestCase
{
    public function test_the_contract_exposes_only_immutable_record_operations(): void
    {
        $reflection = new \ReflectionClass(AcknowledgementRepository::class);

        $this->assertTrue($reflection->isInterface());
        $this->assertSame(
            ['find', 'record', 'forNotification', 'forUser'],
            array_map(fn (\ReflectionMethod $method) => $method->name, $reflection->getMethods()),
        );
        $this->assertSame('?'.Acknowledgement::class, (string) $reflection->getMethod('find')->getReturnType());
        $this->assertSame(Acknowledgement::class, (string) $reflection->getMethod('record')->getReturnType());
        $this->assertSame(Collection::class, (string) $reflection->getMethod('forNotification')->getReturnType());
        $this->assertSame(Collection::class, (string) $reflection->getMethod('forUser')->getReturnType());

        $timestamp = $reflection->getMethod('record')->getParameters()[2];
        $this->assertTrue($timestamp->allowsNull());
        $this->assertSame('?'.CarbonImmutable::class, (string) $timestamp->getType());
    }

    public function test_concrete_drivers_cannot_expose_acknowledgement_mutation_apis(): void
    {
        foreach ([EloquentAcknowledgementRepository::class, FileAcknowledgementRepository::class] as $driver) {
            $reflection = new \ReflectionClass($driver);
            $publicMethods = collect($reflection->getMethods(\ReflectionMethod::IS_PUBLIC))
                ->reject(fn (\ReflectionMethod $method): bool => $method->isConstructor())
                ->map(fn (\ReflectionMethod $method): string => $method->name)
                ->values()
                ->all();

            $this->assertTrue($reflection->isFinal());
            $this->assertEqualsCanonicalizing(
                ['find', 'record', 'forNotification', 'forUser'],
                $publicMethods,
            );
            $this->assertEmpty(array_intersect(['update', 'revoke', 'delete', 'remove'], $publicMethods));
        }
    }
}
