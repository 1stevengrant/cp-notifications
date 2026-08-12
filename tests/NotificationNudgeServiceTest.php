<?php

namespace Ghijk\CpNotifications\Tests;

use Carbon\CarbonImmutable;
use Ghijk\CpNotifications\Audience\AudienceMatcher;
use Ghijk\CpNotifications\Audience\AudienceResolver;
use Ghijk\CpNotifications\Contracts\AcknowledgementRepository;
use Ghijk\CpNotifications\Mail\NotificationNudge;
use Ghijk\CpNotifications\Notifications\ActiveWindow;
use Ghijk\CpNotifications\Nudges\NotificationNudgeService;
use Ghijk\CpNotifications\Nudges\NudgeEligibility;
use Ghijk\CpNotifications\Repositories\FileNudgeDeliveryRepository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Statamic\Auth\UserCollection;
use Statamic\Contracts\Auth\User;
use Statamic\Contracts\Auth\UserRepository;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;

class NotificationNudgeServiceTest extends TestCase
{
    private string $deliveryPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->deliveryPath = sys_get_temp_dir().'/cp-notifications-service-'.bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        Entry::query()->where('collection', 'notifications')->get()->each->delete();
        Collection::find('notifications')?->delete();
        (new Filesystem)->deleteDirectory($this->deliveryPath);
        parent::tearDown();
    }

    public function test_one_shot_delivery_state_prevents_duplicate_scheduled_email(): void
    {
        Mail::fake();
        config()->set('app.timezone', 'Pacific/Auckland');
        CarbonImmutable::setTestNow('2026-08-12 12:00 Pacific/Auckland');
        Collection::make('notifications')->sites([Site::default()->handle()])->save();
        Entry::make()->id('notice-1')->collection('notifications')->locale(Site::default()->handle())
            ->data([
                'title' => 'Required reading',
                'audience' => ['all' => true],
                'start_date' => '2026-08-11 12:00',
                'nudge' => ['enabled' => true, 'threshold_hours' => 24],
            ])->save();
        $user = Mockery::mock(User::class);
        $user->allows('id')->andReturn('user-1');
        $user->allows('email')->andReturn('user@example.com');
        $user->allows('hasRole')->andReturnFalse();
        $user->allows('isInGroup')->andReturnFalse();
        $users = Mockery::mock(UserRepository::class);
        $users->allows('all')->andReturn(new UserCollection([$user]));
        $acknowledgements = Mockery::mock(AcknowledgementRepository::class);
        $acknowledgements->allows('find')->andReturnNull();
        $service = new NotificationNudgeService(
            new AudienceResolver($users, new AudienceMatcher),
            $acknowledgements,
            new FileNudgeDeliveryRepository(new Filesystem, $this->deliveryPath),
            new NudgeEligibility(new ActiveWindow),
            $this->app->make(\Illuminate\Contracts\Mail\Factory::class),
        );

        $this->assertSame(1, $service->send('notice-1'));
        $this->assertSame(0, $service->send('notice-1'));
        Mail::assertSent(NotificationNudge::class, 1);
    }
}
