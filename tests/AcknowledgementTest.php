<?php

namespace Ghijk\CpNotifications\Tests;

use Carbon\CarbonImmutable;
use Ghijk\CpNotifications\Data\Acknowledgement;

class AcknowledgementTest extends TestCase
{
    public function test_it_is_an_immutable_round_trippable_data_object(): void
    {
        $data = [
            'id' => 'ack-id',
            'notification_id' => 'notice-id',
            'user_id' => 'user-id',
            'acknowledged_at' => '2026-08-12T16:55:00+12:00',
        ];

        $acknowledgement = Acknowledgement::fromArray($data);

        $this->assertSame('ack-id', $acknowledgement->id);
        $this->assertSame('notice-id', $acknowledgement->notificationId);
        $this->assertSame('user-id', $acknowledgement->userId);
        $this->assertInstanceOf(CarbonImmutable::class, $acknowledgement->acknowledgedAt);
        $this->assertSame($data, $acknowledgement->toArray());
        $this->assertSame($data, $acknowledgement->jsonSerialize());

        $reflection = new \ReflectionClass($acknowledgement);
        $this->assertTrue($reflection->isFinal());
        $this->assertTrue($reflection->isReadOnly());
    }
}
