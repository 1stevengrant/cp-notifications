<?php

namespace Ghijk\CpNotifications\Tests\Pest\AcknowledgeNotificationEndpointTest;

use Carbon\CarbonImmutable;
use Ghijk\CpNotifications\Contracts\AcknowledgementRepository;
use Ghijk\CpNotifications\Data\Acknowledgement;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Facades\User;

afterEach(function () {
    CarbonImmutable::setTestNow();
    Entry::query()->where('collection', 'notifications')->get()->each->deleteQuietly();
    Collection::find('notifications')?->delete();

});

test('acknowledgement endpoint is idempotent', function () {
    CarbonImmutable::setTestNow('2026-08-12 12:00:00');
    $this->actingAs(User::make()->id('user-1')->email('user@example.com')->set('super', true));
    Collection::make('notifications')->sites([Site::default()->handle()])->save();
    notice('notice-1')->save();
    $acknowledgement = new Acknowledgement(
        id: 'ack-1',
        notificationId: 'notice-1',
        userId: 'user-1',
        acknowledgedAt: CarbonImmutable::now(),
    );
    $repository = \Mockery::mock(AcknowledgementRepository::class);
    $repository->expects('find')->twice()->with('notice-1', 'user-1')
        ->andReturn(null, $acknowledgement);
    $repository->expects('record')->once()->with('notice-1', 'user-1')
        ->andReturn($acknowledgement);
    $this->app->instance(AcknowledgementRepository::class, $repository);
    $url = cp_route('cp-notifications.api.notifications.acknowledge', 'notice-1');

    $this->postJson($url, ['confirmed' => true])->assertOk()->assertJsonPath('data.id', 'ack-1');
    $this->postJson($url, ['confirmed' => true])->assertOk()->assertJsonPath('data.id', 'ack-1');
});

test('racing requests return the same once only repository winner', function () {
    CarbonImmutable::setTestNow('2026-08-12 12:00:00');
    $this->actingAs(User::make()->id('user-1')->email('user@example.com')->set('super', true));
    Collection::make('notifications')->sites([Site::default()->handle()])->save();
    notice('notice-1')->save();
    $winner = new Acknowledgement(
        id: 'winning-ack',
        notificationId: 'notice-1',
        userId: 'user-1',
        acknowledgedAt: CarbonImmutable::now(),
    );
    $repository = \Mockery::mock(AcknowledgementRepository::class);
    $repository->expects('find')->twice()->with('notice-1', 'user-1')->andReturnNull();
    $repository->expects('record')->twice()->with('notice-1', 'user-1')->andReturn($winner);
    $this->app->instance(AcknowledgementRepository::class, $repository);
    $url = cp_route('cp-notifications.api.notifications.acknowledge', 'notice-1');

    $first = $this->postJson($url, ['confirmed' => true]);
    $second = $this->postJson($url, ['confirmed' => true]);

    $first->assertOk()->assertJsonPath('data.id', 'winning-ack');
    $second->assertOk()->assertJsonPath('data.id', 'winning-ack');
    expect($second->json('data'))->toBe($first->json('data'));
});

test('inactive or untargeted notifications cannot be acknowledged', function () {
    CarbonImmutable::setTestNow('2026-08-12 12:00:00');
    $this->actingAs(User::make()->id('user-1')->email('user@example.com')->set('super', true));
    Collection::make('notifications')->sites([Site::default()->handle()])->save();
    notice('future')->set('start_date', '2026-08-13 09:00')->save();
    $repository = \Mockery::mock(AcknowledgementRepository::class);
    $repository->allows('find')->andReturnNull();
    $repository->shouldNotReceive('record');
    $this->app->instance(AcknowledgementRepository::class, $repository);

    $this->postJson(cp_route('cp-notifications.api.notifications.acknowledge', 'future'), ['confirmed' => true])
        ->assertConflict();
});

test('literal boolean confirmation is required server side', function () {
    $this->actingAs(User::make()->id('user-1')->email('user@example.com')->set('super', true));
    $repository = \Mockery::mock(AcknowledgementRepository::class);
    $repository->shouldNotReceive('record');
    $this->app->instance(AcknowledgementRepository::class, $repository);
    $url = cp_route('cp-notifications.api.notifications.acknowledge', 'notice-1');

    $this->postJson($url)->assertUnprocessable()->assertJsonValidationErrors('confirmed');
    $this->postJson($url, ['confirmed' => 'true'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('confirmed');
    $this->postJson($url, ['confirmed' => false])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('confirmed');
});

function notice(string $id)
{
    return Entry::make()
        ->id($id)
        ->collection('notifications')
        ->locale(Site::default()->handle())
        ->published(true)
        ->data([
            'title' => 'Notice',
            'audience' => ['all' => true],
            'start_date' => '2026-08-12 09:00',
        ]);
}
