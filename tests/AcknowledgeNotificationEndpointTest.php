<?php

namespace Ghijk\CpNotifications\Tests;

use Carbon\CarbonImmutable;
use Ghijk\CpNotifications\Contracts\AcknowledgementRepository;
use Ghijk\CpNotifications\Data\Acknowledgement;
use Mockery;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Facades\User;

class AcknowledgeNotificationEndpointTest extends TestCase
{
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        Entry::query()->where('collection', 'notifications')->get()->each->deleteQuietly();
        Collection::find('notifications')?->delete();

        parent::tearDown();
    }

    public function test_acknowledgement_endpoint_is_idempotent(): void
    {
        CarbonImmutable::setTestNow('2026-08-12 12:00:00');
        $this->actingAs(User::make()->id('user-1')->email('user@example.com')->set('super', true));
        Collection::make('notifications')->sites([Site::default()->handle()])->save();
        $this->notice('notice-1')->save();
        $acknowledgement = new Acknowledgement(
            id: 'ack-1',
            notificationId: 'notice-1',
            userId: 'user-1',
            acknowledgedAt: CarbonImmutable::now(),
        );
        $repository = Mockery::mock(AcknowledgementRepository::class);
        $repository->expects('find')->twice()->with('notice-1', 'user-1')
            ->andReturn(null, $acknowledgement);
        $repository->expects('record')->once()->with('notice-1', 'user-1')
            ->andReturn($acknowledgement);
        $this->app->instance(AcknowledgementRepository::class, $repository);
        $url = cp_route('cp-notifications.api.notifications.acknowledge', 'notice-1');

        $this->postJson($url, ['confirmed' => true])->assertOk()->assertJsonPath('data.id', 'ack-1');
        $this->postJson($url, ['confirmed' => true])->assertOk()->assertJsonPath('data.id', 'ack-1');
    }

    public function test_inactive_or_untargeted_notifications_cannot_be_acknowledged(): void
    {
        CarbonImmutable::setTestNow('2026-08-12 12:00:00');
        $this->actingAs(User::make()->id('user-1')->email('user@example.com')->set('super', true));
        Collection::make('notifications')->sites([Site::default()->handle()])->save();
        $this->notice('future')->set('start_date', '2026-08-13 09:00')->save();
        $repository = Mockery::mock(AcknowledgementRepository::class);
        $repository->allows('find')->andReturnNull();
        $repository->shouldNotReceive('record');
        $this->app->instance(AcknowledgementRepository::class, $repository);

        $this->postJson(cp_route('cp-notifications.api.notifications.acknowledge', 'future'), ['confirmed' => true])
            ->assertConflict();
    }

    public function test_literal_boolean_confirmation_is_required_server_side(): void
    {
        $this->actingAs(User::make()->id('user-1')->email('user@example.com')->set('super', true));
        $repository = Mockery::mock(AcknowledgementRepository::class);
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
    }

    private function notice(string $id)
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
}
