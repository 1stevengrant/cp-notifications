<?php

namespace Ghijk\CpNotifications\Tests;

use Ghijk\CpNotifications\Mail\NotificationNudge;

class NotificationNudgeMailTest extends TestCase
{
    public function test_it_uses_the_configured_sender_and_links_to_the_cp_inbox(): void
    {
        config()->set('cp-notifications.nudge.from_address', 'notifications@example.com');
        $mail = new NotificationNudge('Required reading');

        $this->assertSame('notifications@example.com', $mail->envelope()->from->address);
        $this->assertSame('Reminder: Required reading', $mail->envelope()->subject);
        $mail->assertSeeInHtml('Required reading');
        $mail->assertSeeInHtml(cp_route('cp-notifications.inbox'));
    }

    public function test_null_sender_defers_to_the_application_mail_configuration(): void
    {
        config()->set('cp-notifications.nudge.from_address', null);

        $this->assertNull((new NotificationNudge('Notice'))->envelope()->from);
    }
}
