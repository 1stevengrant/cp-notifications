<?php

namespace Ghijk\CpNotifications\Tests;

use Carbon\CarbonImmutable;
use Ghijk\CpNotifications\Contracts\AcknowledgementRepository;
use Ghijk\CpNotifications\Data\Acknowledgement;
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
}
