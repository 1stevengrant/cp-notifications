<?php

namespace Ghijk\CpNotifications\Tests;

use Carbon\CarbonImmutable;
use Ghijk\CpNotifications\Contracts\SnoozeRepository;
use Ghijk\CpNotifications\Data\Snooze;
use Mockery;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Facades\User;

class SnoozeNotificationEndpointTest extends TestCase
{
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        Entry::query()->where('collection', 'notifications')->get()->each->delete();
        Collection::find('notifications')?->delete();

        parent::tearDown();
    }

    public function test_eligible_advisory_can_be_snoozed_once_for_twenty_four_hours(): void
    {
        $this->setUpContent();
        $snooze = new Snooze(
            'notice-1',
            'user-1',
            CarbonImmutable::now('Pacific/Auckland')->addDay(),
        );
        $repository = Mockery::mock(SnoozeRepository::class);
        $repository->expects('find')->twice()->with('notice-1', 'user-1')
            ->andReturn(null, $snooze);
        $repository->expects('record')->once()->with('notice-1', 'user-1')->andReturn($snooze);
        $this->app->instance(SnoozeRepository::class, $repository);
        $url = cp_route('cp-notifications.api.notifications.snooze', 'notice-1');

        $this->postJson($url)
            ->assertCreated()
            ->assertJsonPath('data.snoozed_until', '2026-08-13T12:00:00+12:00');
        $this->postJson($url)->assertConflict();
    }

    public function test_blocking_or_non_snoozeable_notices_cannot_be_snoozed(): void
    {
        $this->setUpContent();
        $this->notice('blocking', blocking: true)->save();
        $this->notice('advisory', snoozeable: false)->save();
        $repository = Mockery::mock(SnoozeRepository::class);
        $repository->shouldNotReceive('record');
        $this->app->instance(SnoozeRepository::class, $repository);

        $this->postJson(cp_route('cp-notifications.api.notifications.snooze', 'blocking'))
            ->assertConflict();
        $this->postJson(cp_route('cp-notifications.api.notifications.snooze', 'advisory'))
            ->assertConflict();
    }

    private function setUpContent(): void
    {
        config()->set('app.timezone', 'Pacific/Auckland');
        CarbonImmutable::setTestNow('2026-08-12 12:00:00 Pacific/Auckland');
        $this->actingAs(User::make()->id('user-1')->email('user@example.com')->set('super', true));
        Collection::make('notifications')->sites([Site::default()->handle()])->save();
        $this->notice('notice-1')->save();
    }

    private function notice(string $id, bool $blocking = false, bool $snoozeable = true)
    {
        return Entry::make()
            ->id($id)
            ->collection('notifications')
            ->locale(Site::default()->handle())
            ->published(true)
            ->data([
                'title' => 'Notice',
                'audience' => ['all' => true],
                'blocking' => $blocking,
                'snoozeable' => $snoozeable,
                'start_date' => '2026-08-12 09:00',
            ]);
    }
}
