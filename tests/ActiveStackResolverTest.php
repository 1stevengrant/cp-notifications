<?php

namespace Ghijk\CpNotifications\Tests;

use Carbon\CarbonImmutable;
use Ghijk\CpNotifications\Audience\AudienceMatcher;
use Ghijk\CpNotifications\Contracts\AcknowledgementRepository;
use Ghijk\CpNotifications\Data\Acknowledgement;
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

        $stack = (new ActiveStackResolver(
            new AudienceMatcher,
            new ActiveWindow,
            $acknowledgements,
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
        $outsideAudience = $this->notice('other')->set('audience', ['users' => ['user-2']]);
        $future = $this->notice('future')->set('start_date', '2026-08-13 09:00');

        $stack = (new ActiveStackResolver(
            new AudienceMatcher,
            new ActiveWindow,
            $acknowledgements,
        ))->resolve([$outsideAudience, $future], $user, '2026-08-12 12:00');

        $this->assertCount(0, $stack);
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
