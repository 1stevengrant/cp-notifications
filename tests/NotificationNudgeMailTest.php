<?php

namespace Ghijk\CpNotifications\Tests\Pest\NotificationNudgeMailTest;

use Ghijk\CpNotifications\Mail\NotificationNudge;

test('it uses the configured sender and links to the cp inbox', function () {
    config()->set('cp-notifications.nudge.from_address', 'notifications@example.com');
    $mail = new NotificationNudge('Required reading');

    expect($mail->envelope()->from->address)->toBe('notifications@example.com');
    expect($mail->envelope()->subject)->toBe('Reminder: Required reading');
    $mail->assertSeeInHtml('Required reading');
    $mail->assertSeeInHtml(cp_route('cp-notifications.inbox'));
});

test('null sender defers to the application mail configuration', function () {
    config()->set('cp-notifications.nudge.from_address', null);

    expect((new NotificationNudge('Notice'))->envelope()->from)->toBeNull();
});
