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
