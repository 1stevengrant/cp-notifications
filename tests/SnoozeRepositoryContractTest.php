<?php

namespace Ghijk\CpNotifications\Tests;

use Carbon\CarbonImmutable;
use Ghijk\CpNotifications\Contracts\SnoozeRepository;
use Ghijk\CpNotifications\Data\Snooze;
use Ghijk\CpNotifications\Repositories\EloquentSnoozeRepository;
use Ghijk\CpNotifications\Repositories\FileSnoozeRepository;
use Illuminate\Support\Collection;

class SnoozeRepositoryContractTest extends TestCase
{
    public function test_the_contract_exposes_only_single_use_record_operations(): void
    {
        $reflection = new \ReflectionClass(SnoozeRepository::class);

        $this->assertTrue($reflection->isInterface());
        $this->assertSame(
            ['find', 'record', 'forNotification', 'forUser'],
            array_map(fn (\ReflectionMethod $method) => $method->name, $reflection->getMethods()),
        );
        $this->assertSame('?'.Snooze::class, (string) $reflection->getMethod('find')->getReturnType());
        $this->assertSame(Snooze::class, (string) $reflection->getMethod('record')->getReturnType());
        $this->assertSame(Collection::class, (string) $reflection->getMethod('forNotification')->getReturnType());
        $this->assertSame(Collection::class, (string) $reflection->getMethod('forUser')->getReturnType());

        $expiry = $reflection->getMethod('record')->getParameters()[2];
        $this->assertTrue($expiry->allowsNull());
        $this->assertSame('?'.CarbonImmutable::class, (string) $expiry->getType());
    }

    public function test_concrete_snooze_drivers_are_final(): void
    {
        $this->assertTrue((new \ReflectionClass(EloquentSnoozeRepository::class))->isFinal());
        $this->assertTrue((new \ReflectionClass(FileSnoozeRepository::class))->isFinal());
    }
}
