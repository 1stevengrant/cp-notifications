<?php

namespace Ghijk\CpNotifications\Tests;

use Ghijk\CpNotifications\Audience\AudienceMatcher;
use Ghijk\CpNotifications\Http\Controllers\InboxController;
use Ghijk\CpNotifications\Notifications\InboxNoticeResolver;
use Ghijk\CpNotifications\Notifications\ActiveWindow;
use Ghijk\CpNotifications\Contracts\AcknowledgementRepository;
use Ghijk\CpNotifications\Contracts\SnoozeRepository;
use Ghijk\CpNotifications\Data\Acknowledgement;
use Carbon\CarbonImmutable;
use Mockery;
use Statamic\Contracts\Auth\User;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;

class InboxNoticeResolverTest extends TestCase
{
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        Entry::query()->where('collection', 'notifications')->get()->each->delete();
        Collection::find('notifications')?->delete();

        parent::tearDown();
    }

    public function test_inbox_contains_only_published_notices_targeting_the_user(): void
    {
        Collection::make('notifications')->sites([Site::default()->handle()])->save();
        $this->notice('targeted', true, ['users' => ['user-1']])->save();
        $this->notice('other', true, ['users' => ['user-2']])->save();
        $this->notice('draft', false, ['users' => ['user-1']])->save();
        $user = Mockery::mock(User::class);
        $user->allows('id')->andReturn('user-1');
        $user->allows('hasRole')->andReturnFalse();
        $user->allows('isInGroup')->andReturnFalse();

        $acknowledgements = Mockery::mock(AcknowledgementRepository::class);
        $acknowledgements->allows('find')->andReturnNull();
        $snoozes = Mockery::mock(SnoozeRepository::class);
        $snoozes->allows('find')->andReturnNull();
        $resolved = (new InboxNoticeResolver(
            new AudienceMatcher,
            new ActiveWindow,
            $acknowledgements,
            $snoozes,
        ))->resolve($user);

        $this->assertSame(['targeted'], $resolved->pluck('notification')->map->id()->all());
    }

    public function test_bypass_permission_does_not_hide_targeted_inbox_notices(): void
    {
        Collection::make('notifications')->sites([Site::default()->handle()])->save();
        $this->notice('targeted', true, ['all' => true])->save();
        $user = Mockery::mock(User::class);
        $user->allows('id')->andReturn('user-1');
        $user->allows('hasRole')->andReturnFalse();
        $user->allows('isInGroup')->andReturnFalse();
        $user->allows('can')->with('bypass notifications')->andReturnTrue();
        $acknowledgements = Mockery::mock(AcknowledgementRepository::class);
        $acknowledgements->allows('find')->andReturnNull();
        $snoozes = Mockery::mock(SnoozeRepository::class);
        $snoozes->allows('find')->andReturnNull();

        $items = (new InboxNoticeResolver(
            new AudienceMatcher,
            new ActiveWindow,
            $acknowledgements,
            $snoozes,
        ))->resolve($user);

        $this->assertSame(['targeted'], $items->pluck('notification')->map->id()->all());
        $this->assertCount(0, (new \Ghijk\CpNotifications\Notifications\GatingStack)
            ->forUser($items->pluck('notification'), $user));
    }

    public function test_inbox_route_uses_the_user_specific_controller(): void
    {
        $route = $this->app['router']->getRoutes()->getByName('statamic.cp.cp-notifications.inbox');

        $this->assertSame(InboxController::class, $route->getActionName());
        $this->assertStringContainsString(
            'notification-inbox',
            file_get_contents(__DIR__.'/../resources/views/inbox.blade.php'),
        );
    }

    public function test_active_and_previously_read_notices_are_both_returned(): void
    {
        config()->set('app.timezone', 'Pacific/Auckland');
        CarbonImmutable::setTestNow('2026-08-12 12:00:00 Pacific/Auckland');
        Collection::make('notifications')->sites([Site::default()->handle()])->save();
        $this->notice('active', true, ['all' => true])->save();
        $this->notice('read', true, ['all' => true])->save();
        $user = Mockery::mock(User::class);
        $user->allows('id')->andReturn('user-1');
        $user->allows('hasRole')->andReturnFalse();
        $user->allows('isInGroup')->andReturnFalse();
        $acknowledgements = Mockery::mock(AcknowledgementRepository::class);
        $acknowledgements->allows('find')->with('active', 'user-1')->andReturnNull();
        $acknowledgements->allows('find')->with('read', 'user-1')->andReturn(new Acknowledgement(
            'ack-1',
            'read',
            'user-1',
            CarbonImmutable::now(),
        ));
        $snoozes = Mockery::mock(SnoozeRepository::class);
        $snoozes->allows('find')->andReturnNull();

        $items = (new InboxNoticeResolver(
            new AudienceMatcher,
            new ActiveWindow,
            $acknowledgements,
            $snoozes,
        ))->resolve($user);

        $this->assertSame(['active', 'read'], $items->pluck('notification')->map->id()->all());
        $this->assertSame([true, false], $items->pluck('active')->all());
        $this->assertNotNull($items->last()['acknowledgement']);
    }

    public function test_expired_history_obeys_retention_days_and_null_is_indefinite(): void
    {
        config()->set('app.timezone', 'Pacific/Auckland');
        config()->set('cp-notifications.retention.inbox_days', 30);
        CarbonImmutable::setTestNow('2026-08-12 12:00:00 Pacific/Auckland');
        Collection::make('notifications')->sites([Site::default()->handle()])->save();
        $this->notice('boundary', true, ['all' => true])
            ->set('end_date', '2026-07-13 12:00')->save();
        $this->notice('too-old', true, ['all' => true])
            ->set('end_date', '2026-07-13 11:59:59')->save();
        $user = Mockery::mock(User::class);
        $user->allows('id')->andReturn('user-1');
        $user->allows('hasRole')->andReturnFalse();
        $user->allows('isInGroup')->andReturnFalse();
        $acknowledgements = Mockery::mock(AcknowledgementRepository::class);
        $acknowledgements->allows('find')->andReturnNull();
        $snoozes = Mockery::mock(SnoozeRepository::class);
        $snoozes->allows('find')->andReturnNull();
        $resolver = new InboxNoticeResolver(
            new AudienceMatcher,
            new ActiveWindow,
            $acknowledgements,
            $snoozes,
        );

        $this->assertSame(
            ['boundary'],
            $resolver->resolve($user)->pluck('notification')->map->id()->all(),
        );

        config()->set('cp-notifications.retention.inbox_days', null);

        $this->assertEqualsCanonicalizing(
            ['boundary', 'too-old'],
            $resolver->resolve($user)->pluck('notification')->map->id()->all(),
        );
    }

    public function test_expired_unacknowledged_advisory_is_history_not_active(): void
    {
        config()->set('app.timezone', 'Pacific/Auckland');
        config()->set('cp-notifications.retention.inbox_days', null);
        CarbonImmutable::setTestNow('2026-08-12 12:00:00 Pacific/Auckland');
        Collection::make('notifications')->sites([Site::default()->handle()])->save();
        $this->notice('expired-advisory', true, ['all' => true])
            ->set('blocking', false)
            ->set('end_date', '2026-08-12 11:59:59')
            ->save();
        $user = Mockery::mock(User::class);
        $user->allows('id')->andReturn('user-1');
        $user->allows('hasRole')->andReturnFalse();
        $user->allows('isInGroup')->andReturnFalse();
        $acknowledgements = Mockery::mock(AcknowledgementRepository::class);
        $acknowledgements->allows('find')->with('expired-advisory', 'user-1')->andReturnNull();
        $snoozes = Mockery::mock(SnoozeRepository::class);
        $snoozes->allows('find')->with('expired-advisory', 'user-1')->andReturnNull();

        $items = (new InboxNoticeResolver(
            new AudienceMatcher,
            new ActiveWindow,
            $acknowledgements,
            $snoozes,
        ))->resolve($user);

        $this->assertCount(1, $items);
        $this->assertSame('expired-advisory', $items->first()['notification']->id());
        $this->assertFalse($items->first()['active']);
        $this->assertNull($items->first()['acknowledgement']);
    }

    private function notice(string $id, bool $published, array $audience)
    {
        return Entry::make()
            ->id($id)
            ->collection('notifications')
            ->locale(Site::default()->handle())
            ->published($published)
            ->data([
                'title' => ucfirst($id),
                'audience' => $audience,
                'start_date' => '2026-01-01 00:00',
            ]);
    }
}
