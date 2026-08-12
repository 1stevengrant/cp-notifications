<?php

namespace Ghijk\CpNotifications\Tests;

use Ghijk\CpNotifications\Audience\AudienceMatcher;
use Ghijk\CpNotifications\Http\Controllers\InboxController;
use Ghijk\CpNotifications\Notifications\InboxNoticeResolver;
use Mockery;
use Statamic\Contracts\Auth\User;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;

class InboxNoticeResolverTest extends TestCase
{
    protected function tearDown(): void
    {
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

        $resolved = (new InboxNoticeResolver(new AudienceMatcher))->resolve($user);

        $this->assertSame(['targeted'], $resolved->map->id()->all());
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
