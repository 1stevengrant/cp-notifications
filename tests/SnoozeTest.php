<?php

namespace Ghijk\CpNotifications\Tests;

use Carbon\CarbonImmutable;
use Ghijk\CpNotifications\Data\Snooze;

class SnoozeTest extends TestCase
{
    public function test_it_is_an_immutable_round_trippable_data_object(): void
    {
        $data = [
            'notification_id' => 'notice-id',
            'user_id' => 'user-id',
            'snoozed_until' => '2026-08-13T16:55:00+12:00',
        ];

        $snooze = Snooze::fromArray($data);

        $this->assertSame('notice-id', $snooze->notificationId);
        $this->assertSame('user-id', $snooze->userId);
        $this->assertInstanceOf(CarbonImmutable::class, $snooze->snoozedUntil);
        $this->assertSame($data, $snooze->toArray());
        $this->assertSame($data, $snooze->jsonSerialize());
        $this->assertTrue((new \ReflectionClass($snooze))->isReadOnly());
    }

    public function test_it_is_inactive_at_the_exact_expiry_boundary(): void
    {
        $until = CarbonImmutable::parse('2026-08-13T16:55:00+12:00');
        $snooze = new Snooze('notice-id', 'user-id', $until);

        $this->assertTrue($snooze->isActiveAt($until->subMicrosecond()));
        $this->assertFalse($snooze->isActiveAt($until));
        $this->assertFalse($snooze->isActiveAt($until->addMicrosecond()));
    }
}
