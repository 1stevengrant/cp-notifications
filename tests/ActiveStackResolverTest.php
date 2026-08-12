<?php

namespace Ghijk\CpNotifications\Tests\Pest\ActiveStackResolverTest;

use Carbon\CarbonImmutable;
use Ghijk\CpNotifications\Audience\AudienceMatcher;
use Ghijk\CpNotifications\Contracts\AcknowledgementRepository;
use Ghijk\CpNotifications\Contracts\SnoozeRepository;
use Ghijk\CpNotifications\Data\Acknowledgement;
use Ghijk\CpNotifications\Data\Snooze;
use Ghijk\CpNotifications\Notifications\ActiveStackResolver;
use Ghijk\CpNotifications\Notifications\ActiveWindow;
use Ghijk\CpNotifications\Notifications\NotificationOrder;
use Statamic\Contracts\Auth\User;
use Statamic\Entries\Collection;
use Statamic\Entries\Entry;

test('acknowledged notices are excluded from the active stack', function () {
    $user = user('user-1');
    $acknowledgements = \Mockery::mock(AcknowledgementRepository::class);
    $acknowledgements->allows('find')->with('unread', 'user-1')->andReturnNull();
    $acknowledgements->allows('find')->with('read', 'user-1')->andReturn(new Acknowledgement(
        id: 'ack-1',
        notificationId: 'read',
        userId: 'user-1',
        acknowledgedAt: CarbonImmutable::parse('2026-08-12 09:00'),
    ));
    $snoozes = \Mockery::mock(SnoozeRepository::class);
    $snoozes->allows('find')->andReturnNull();

    $stack = (new ActiveStackResolver(
        new AudienceMatcher,
        new ActiveWindow,
        $acknowledgements,
        $snoozes,
        new NotificationOrder,
    ))->resolve(
        [notice('unread'), notice('read')],
        $user,
        '2026-08-12 12:00',
    );

    expect($stack->map->id()->all())->toBe(['unread']);
});

test('notices outside the audience or active window are excluded first', function () {
    $user = user('user-1');
    $acknowledgements = \Mockery::mock(AcknowledgementRepository::class);
    $acknowledgements->shouldNotReceive('find');
    $snoozes = \Mockery::mock(SnoozeRepository::class);
    $snoozes->shouldNotReceive('find');
    $outsideAudience = notice('other')->set('audience', ['users' => ['user-2']]);
    $future = notice('future')->set('start_date', '2026-08-13 09:00');

    $stack = (new ActiveStackResolver(
        new AudienceMatcher,
        new ActiveWindow,
        $acknowledgements,
        $snoozes,
        new NotificationOrder,
    ))->resolve([$outsideAudience, $future], $user, '2026-08-12 12:00');

    expect($stack)->toHaveCount(0);
});

test('only currently snoozed notices are excluded', function () {
    $user = user('user-1');
    $acknowledgements = \Mockery::mock(AcknowledgementRepository::class);
    $acknowledgements->allows('find')->andReturnNull();
    $snoozes = \Mockery::mock(SnoozeRepository::class);
    $snoozes->allows('find')->with('active-snooze', 'user-1')->andReturn(new Snooze(
        notificationId: 'active-snooze',
        userId: 'user-1',
        snoozedUntil: CarbonImmutable::parse('2026-08-13 12:00'),
    ));
    $snoozes->allows('find')->with('expired-snooze', 'user-1')->andReturn(new Snooze(
        notificationId: 'expired-snooze',
        userId: 'user-1',
        snoozedUntil: CarbonImmutable::parse('2026-08-12 12:00'),
    ));
    $snoozes->allows('find')->with('never-snoozed', 'user-1')->andReturnNull();

    $stack = (new ActiveStackResolver(
        new AudienceMatcher,
        new ActiveWindow,
        $acknowledgements,
        $snoozes,
        new NotificationOrder,
    ))->resolve([
        notice('active-snooze'),
        notice('expired-snooze'),
        notice('never-snoozed'),
    ], $user, '2026-08-12 12:00');

    expect($stack->map->id()->all())->toBe(['expired-snooze', 'never-snoozed']);
});

test('mixed blocking and advisory stack keeps server order for top down overlay', function () {
    $acknowledgements = \Mockery::mock(AcknowledgementRepository::class);
    $acknowledgements->allows('find')->andReturnNull();
    $snoozes = \Mockery::mock(SnoozeRepository::class);
    $snoozes->allows('find')->andReturnNull();
    $blocking = notice('blocking')->set('blocking', true)->set('severity', 'critical');
    $advisory = notice('advisory')->set('blocking', false)->set('priority', 1);

    $stack = (new ActiveStackResolver(
        new AudienceMatcher,
        new ActiveWindow,
        $acknowledgements,
        $snoozes,
        new NotificationOrder,
    ))->resolve([$blocking, $advisory], user('user-1'), '2026-08-12 12:00');

    expect($stack->map->id()->all())->toBe(['advisory', 'blocking']);
    expect($stack->first()->id())->toBe('advisory');
    $component = file_get_contents(__DIR__.'/../resources/js/components/NotificationOverlay.vue');
    $this->assertStringContainsString('return this.notices[0] ?? null', $component);
    $this->assertStringNotContainsString('v-for=', $component);
});

function user(string $id): User
{
    $user = \Mockery::mock(User::class);
    $user->allows('id')->andReturn($id);
    $user->allows('hasRole')->andReturnFalse();
    $user->allows('isInGroup')->andReturnFalse();

    return $user;
}

function notice(string $id): Entry
{
    return (new Entry)
        ->id($id)
        ->collection(Collection::make('notifications'))
        ->published(true)
        ->set('audience', ['all' => true])
        ->set('start_date', '2026-08-12 09:00');
}
