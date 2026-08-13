<?php

namespace Ghijk\CpNotifications\Tests\Pest\ActiveStackEndpointTest;

use Carbon\CarbonImmutable;
use Ghijk\CpNotifications\Contracts\AcknowledgementRepository;
use Ghijk\CpNotifications\Contracts\SnoozeRepository;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Facades\User;

afterEach(function () {
    CarbonImmutable::setTestNow();
    Entry::query()->where('collection', 'notifications')->get()->each->deleteQuietly();
    Collection::find('notifications')?->delete();

});

test('authenticated user receives their ordered active stack', function () {
    CarbonImmutable::setTestNow('2026-08-12 12:00:00');
    $user = User::make()->id('user-1')->email('user@example.com')->set('super', true);
    $this->actingAs($user);
    Collection::make('notifications')->sites([Site::default()->handle()])->save();
    notice('second', 2)->save();
    notice('first', 1)->save();
    $this->mock(AcknowledgementRepository::class)->allows('find')->andReturnNull();
    $this->mock(SnoozeRepository::class)->allows('find')->andReturnNull();

    $response = $this->getJson(cp_route('cp-notifications.api.stack'));

    $response->assertOk()
        ->assertJsonPath('data.0.id', 'first')
        ->assertJsonPath('data.1.id', 'second')
        ->assertJsonPath('data.0.blocking', false);
});

test('stack route uses the authenticated cp route group', function () {
    $route = $this->app['router']->getRoutes()->getByName('statamic.cp.cp-notifications.api.stack');

    expect($route)->not->toBeNull();
    expect($route->gatherMiddleware())->toContain('statamic.cp.authenticated');
});

test('bard body is returned as rendered html', function () {
    CarbonImmutable::setTestNow('2026-08-12 12:00:00');
    $user = User::make()->id('user-1')->email('user@example.com')->set('super', true);
    $this->actingAs($user);
    Collection::make('notifications')->sites([Site::default()->handle()])->save();
    $notice = notice('policy', 1);
    $notice->set('body', [[
        'type' => 'paragraph',
        'content' => [
            ['type' => 'text', 'text' => 'Read the '],
            ['type' => 'text', 'marks' => [['type' => 'bold']], 'text' => 'updated policy'],
            ['type' => 'text', 'text' => '.'],
        ],
    ]])->save();
    $this->mock(AcknowledgementRepository::class)->allows('find')->andReturnNull();
    $this->mock(SnoozeRepository::class)->allows('find')->andReturnNull();

    $response = $this->getJson(cp_route('cp-notifications.api.stack'));

    $response->assertOk()
        ->assertJsonPath('data.0.body_html', '<p>Read the <strong>updated policy</strong>.</p>')
        ->assertJsonPath('data.0.body', 'Read the updated policy.');
});

function notice(string $id, int $priority)
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
