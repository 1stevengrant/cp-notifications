<?php

namespace Ghijk\CpNotifications\Tests;

use Carbon\CarbonImmutable;
use Ghijk\CpNotifications\Contracts\AcknowledgementRepository;
use Ghijk\CpNotifications\Contracts\SnoozeRepository;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Facades\User;

class ActiveStackEndpointTest extends TestCase
{
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        Entry::query()->where('collection', 'notifications')->get()->each->delete();
        Collection::find('notifications')?->delete();

        parent::tearDown();
    }

    public function test_authenticated_user_receives_their_ordered_active_stack(): void
    {
        CarbonImmutable::setTestNow('2026-08-12 12:00:00');
        $user = User::make()->id('user-1')->email('user@example.com')->set('super', true);
        $this->actingAs($user);
        Collection::make('notifications')->sites([Site::default()->handle()])->save();
        $this->notice('second', 2)->save();
        $this->notice('first', 1)->save();
        $this->mock(AcknowledgementRepository::class)->allows('find')->andReturnNull();
        $this->mock(SnoozeRepository::class)->allows('find')->andReturnNull();

        $response = $this->getJson(cp_route('cp-notifications.api.stack'));

        $response->assertOk()
            ->assertJsonPath('data.0.id', 'first')
            ->assertJsonPath('data.1.id', 'second')
            ->assertJsonPath('data.0.blocking', false);
    }

    public function test_stack_route_uses_the_authenticated_cp_route_group(): void
    {
        $route = $this->app['router']->getRoutes()->getByName('statamic.cp.cp-notifications.api.stack');

        $this->assertNotNull($route);
        $this->assertContains('statamic.cp.authenticated', $route->gatherMiddleware());
    }

    private function notice(string $id, int $priority)
    {
        return Entry::make()
            ->id($id)
            ->collection('notifications')
            ->locale(Site::default()->handle())
            ->published(true)
            ->data([
                'title' => ucfirst($id),
                'body' => [['type' => 'paragraph', 'content' => []]],
                'severity' => 'info',
                'blocking' => false,
                'snoozeable' => true,
                'priority' => $priority,
                'audience' => ['all' => true],
                'start_date' => '2026-08-12 09:00',
            ]);
    }
}
