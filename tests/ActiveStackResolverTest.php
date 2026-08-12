<?php

namespace Ghijk\CpNotifications\Tests;

use Carbon\CarbonImmutable;
use Ghijk\CpNotifications\Audience\AudienceMatcher;
use Ghijk\CpNotifications\Contracts\AcknowledgementRepository;
use Ghijk\CpNotifications\Contracts\SnoozeRepository;
use Ghijk\CpNotifications\Data\Acknowledgement;
use Ghijk\CpNotifications\Data\Snooze;
use Ghijk\CpNotifications\Notifications\ActiveStackResolver;
use Ghijk\CpNotifications\Notifications\ActiveWindow;
use Mockery;
use Statamic\Contracts\Auth\User;
use Statamic\Entries\Collection;
use Statamic\Entries\Entry;

class ActiveStackResolverTest extends TestCase
{
    public function test_acknowledged_notices_are_excluded_from_the_active_stack(): void
    {
        $user = $this->user('user-1');
        $acknowledgements = Mockery::mock(AcknowledgementRepository::class);
        $acknowledgements->allows('find')->with('unread', 'user-1')->andReturnNull();
        $acknowledgements->allows('find')->with('read', 'user-1')->andReturn(new Acknowledgement(
            id: 'ack-1',
            notificationId: 'read',
            userId: 'user-1',
            acknowledgedAt: CarbonImmutable::parse('2026-08-12 09:00'),
        ));
        $snoozes = Mockery::mock(SnoozeRepository::class);
        $snoozes->allows('find')->andReturnNull();

        $stack = (new ActiveStackResolver(
            new AudienceMatcher,
            new ActiveWindow,
            $acknowledgements,
            $snoozes,
        ))->resolve(
            [$this->notice('unread'), $this->notice('read')],
            $user,
            '2026-08-12 12:00',
        );

        $this->assertSame(['unread'], $stack->map->id()->all());
    }

    public function test_notices_outside_the_audience_or_active_window_are_excluded_first(): void
    {
        $user = $this->user('user-1');
        $acknowledgements = Mockery::mock(AcknowledgementRepository::class);
        $acknowledgements->shouldNotReceive('find');
        $snoozes = Mockery::mock(SnoozeRepository::class);
        $snoozes->shouldNotReceive('find');
        $outsideAudience = $this->notice('other')->set('audience', ['users' => ['user-2']]);
        $future = $this->notice('future')->set('start_date', '2026-08-13 09:00');

        $stack = (new ActiveStackResolver(
            new AudienceMatcher,
            new ActiveWindow,
            $acknowledgements,
            $snoozes,
        ))->resolve([$outsideAudience, $future], $user, '2026-08-12 12:00');

        $this->assertCount(0, $stack);
    }

    public function test_only_currently_snoozed_notices_are_excluded(): void
    {
        $user = $this->user('user-1');
        $acknowledgements = Mockery::mock(AcknowledgementRepository::class);
        $acknowledgements->allows('find')->andReturnNull();
        $snoozes = Mockery::mock(SnoozeRepository::class);
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
        ))->resolve([
            $this->notice('active-snooze'),
            $this->notice('expired-snooze'),
            $this->notice('never-snoozed'),
        ], $user, '2026-08-12 12:00');

        $this->assertSame(['expired-snooze', 'never-snoozed'], $stack->map->id()->all());
    }

    private function user(string $id): User
    {
        $user = Mockery::mock(User::class);
        $user->allows('id')->andReturn($id);
        $user->allows('hasRole')->andReturnFalse();
        $user->allows('isInGroup')->andReturnFalse();

        return $user;
    }

    private function notice(string $id): Entry
    {
        return (new Entry)
            ->id($id)
            ->collection(Collection::make('notifications'))
            ->published(true)
            ->set('audience', ['all' => true])
            ->set('start_date', '2026-08-12 09:00');
    }
}
