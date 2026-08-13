<?php

namespace Ghijk\CpNotifications\Tests\Pest\SnoozeNotificationEndpointTest;

use Carbon\CarbonImmutable;
use Ghijk\CpNotifications\Contracts\SnoozeRepository;
use Ghijk\CpNotifications\Data\Snooze;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Facades\User;

afterEach(function () {
    CarbonImmutable::setTestNow();
    Entry::query()->where('collection', 'notifications')->get()->each->delete();
    Collection::find('notifications')?->delete();

});

test('eligible advisory can be snoozed once for twenty four hours', function () {
    setUpContent($this);
    $snooze = new Snooze(
        'notice-1',
        'user-1',
        CarbonImmutable::now('Pacific/Auckland')->addDay(),
    );
    $repository = \Mockery::mock(SnoozeRepository::class);
    $repository->expects('find')->twice()->with('notice-1', 'user-1')
        ->andReturn(null, $snooze);
    $repository->expects('record')->once()->with('notice-1', 'user-1')->andReturn($snooze);
    $this->app->instance(SnoozeRepository::class, $repository);
    $url = cp_route('cp-notifications.api.notifications.snooze', 'notice-1');

    $this->postJson($url)
        ->assertCreated()
        ->assertJsonPath('data.snoozed_until', '2026-08-13T12:00:00+12:00');
    $this->postJson($url)->assertConflict();
});

test('blocking or non snoozeable notices cannot be snoozed', function () {
    setUpContent($this);
    notice('blocking', blocking: true)->save();
    notice('advisory', snoozeable: false)->save();
    $repository = \Mockery::mock(SnoozeRepository::class);
    $repository->shouldNotReceive('record');
    $this->app->instance(SnoozeRepository::class, $repository);

    $this->postJson(cp_route('cp-notifications.api.notifications.snooze', 'blocking'))
        ->assertConflict();
    $this->postJson(cp_route('cp-notifications.api.notifications.snooze', 'advisory'))
        ->assertConflict();
});

function setUpContent($test): void
{
    config()->set('app.timezone', 'Pacific/Auckland');
    CarbonImmutable::setTestNow('2026-08-12 12:00:00 Pacific/Auckland');
    $test->actingAs(User::make()->id('user-1')->email('user@example.com')->set('super', true));
    Collection::make('notifications')->sites([Site::default()->handle()])->save();
    notice('notice-1')->save();
}

function notice(string $id, bool $blocking = false, bool $snoozeable = true)
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
