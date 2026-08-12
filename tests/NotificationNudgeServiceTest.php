<?php

namespace Ghijk\CpNotifications\Tests\Pest\NotificationNudgeServiceTest;

use Carbon\CarbonImmutable;
use Ghijk\CpNotifications\Audience\AudienceMatcher;
use Ghijk\CpNotifications\Audience\AudienceResolver;
use Ghijk\CpNotifications\Contracts\AcknowledgementRepository;
use Ghijk\CpNotifications\Data\Acknowledgement;
use Ghijk\CpNotifications\Mail\NotificationNudge;
use Ghijk\CpNotifications\Notifications\ActiveWindow;
use Ghijk\CpNotifications\Nudges\NotificationNudgeService;
use Ghijk\CpNotifications\Nudges\NudgeEligibility;
use Ghijk\CpNotifications\Repositories\FileNudgeDeliveryRepository;
use Illuminate\Contracts\Mail\Factory;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Mail;
use Statamic\Auth\UserCollection;
use Statamic\Contracts\Auth\User;
use Statamic\Contracts\Auth\UserRepository;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;

beforeEach(function () {
    $this->deliveryPath = sys_get_temp_dir().'/cp-notifications-service-'.bin2hex(random_bytes(4));
});

afterEach(function () {
    CarbonImmutable::setTestNow();
    Entry::query()->where('collection', 'notifications')->get()->each->delete();
    Collection::find('notifications')?->delete();
    (new Filesystem)->deleteDirectory($this->deliveryPath);
});

test('one shot delivery state prevents duplicate scheduled email', function () {
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
    $user = \Mockery::mock(User::class);
    $user->allows('id')->andReturn('user-1');
    $user->allows('email')->andReturn('user@example.com');
    $user->allows('hasRole')->andReturnFalse();
    $user->allows('isInGroup')->andReturnFalse();
    $users = \Mockery::mock(UserRepository::class);
    $users->allows('all')->andReturn(new UserCollection([$user]));
    $acknowledgements = \Mockery::mock(AcknowledgementRepository::class);
    $acknowledgements->allows('find')->andReturnNull();
    $service = new NotificationNudgeService(
        new AudienceResolver($users, new AudienceMatcher),
        $acknowledgements,
        new FileNudgeDeliveryRepository(new Filesystem, $this->deliveryPath),
        new NudgeEligibility(new ActiveWindow),
        $this->app->make(Factory::class),
    );

    expect($service->send('notice-1'))->toBe(1);
    expect($service->send('notice-1'))->toBe(0);
    Mail::assertSent(NotificationNudge::class, 1);
});

test('email goes only to currently targeted users without an acknowledgement', function () {
    Mail::fake();
    config()->set('app.timezone', 'Pacific/Auckland');
    CarbonImmutable::setTestNow('2026-08-12 12:00 Pacific/Auckland');
    Collection::make('notifications')->sites([Site::default()->handle()])->save();
    Entry::make()->id('notice-1')->collection('notifications')->locale(Site::default()->handle())
        ->data([
            'title' => 'Required reading',
            'body' => 'Sensitive control-panel-only content',
            'audience' => ['users' => ['pending', 'acknowledged']],
            'start_date' => '2026-08-11 12:00',
            'nudge' => ['enabled' => true, 'threshold_hours' => 24],
        ])->save();
    $pending = user('pending', 'pending@example.com');
    $acknowledged = user('acknowledged', 'acknowledged@example.com');
    $outside = user('outside', 'outside@example.com');
    $users = \Mockery::mock(UserRepository::class);
    $users->allows('all')->andReturn(new UserCollection([$pending, $acknowledged, $outside]));
    $acknowledgements = \Mockery::mock(AcknowledgementRepository::class);
    $acknowledgements->allows('find')->andReturnUsing(
        fn (string $notificationId, string $userId) => $userId === 'acknowledged'
            ? new Acknowledgement('ack-1', $notificationId, $userId, CarbonImmutable::now())
            : null,
    );
    $service = new NotificationNudgeService(
        new AudienceResolver($users, new AudienceMatcher),
        $acknowledgements,
        new FileNudgeDeliveryRepository(new Filesystem, $this->deliveryPath),
        new NudgeEligibility(new ActiveWindow),
        $this->app->make(Factory::class),
    );

    expect($service->send('notice-1'))->toBe(1);
    Mail::assertSent(NotificationNudge::class, fn ($mail): bool => $mail->hasTo('pending@example.com')
        && ! str_contains($mail->render(), 'Sensitive control-panel-only content')
        && str_contains($mail->render(), cp_route('cp-notifications.inbox'))
    );
    Mail::assertNotSent(NotificationNudge::class, fn ($mail): bool => $mail->hasTo('acknowledged@example.com') || $mail->hasTo('outside@example.com')
    );
});

test('repeating delivery waits for the exact cadence boundary', function () {
    Mail::fake();
    config()->set('app.timezone', 'Pacific/Auckland');
    CarbonImmutable::setTestNow('2026-08-12 12:00 Pacific/Auckland');
    Collection::make('notifications')->sites([Site::default()->handle()])->save();
    Entry::make()->id('notice-1')->collection('notifications')->locale(Site::default()->handle())
        ->data([
            'title' => 'Required reading',
            'audience' => ['all' => true],
            'start_date' => '2026-08-11 12:00',
            'nudge' => ['enabled' => true, 'threshold_hours' => 24, 'cadence_hours' => 6],
        ])->save();
    $user = user('user-1', 'user@example.com');
    $users = \Mockery::mock(UserRepository::class);
    $users->allows('all')->andReturn(new UserCollection([$user]));
    $acknowledgements = \Mockery::mock(AcknowledgementRepository::class);
    $acknowledgements->allows('find')->andReturnNull();
    $service = new NotificationNudgeService(
        new AudienceResolver($users, new AudienceMatcher),
        $acknowledgements,
        new FileNudgeDeliveryRepository(new Filesystem, $this->deliveryPath),
        new NudgeEligibility(new ActiveWindow),
        $this->app->make(Factory::class),
    );

    expect($service->send('notice-1'))->toBe(1);
    CarbonImmutable::setTestNow('2026-08-12 17:59:59 Pacific/Auckland');
    expect($service->send('notice-1'))->toBe(0);
    CarbonImmutable::setTestNow('2026-08-12 18:00:00 Pacific/Auckland');
    expect($service->send('notice-1'))->toBe(1);
    Mail::assertSent(NotificationNudge::class, 2);
});

function user(string $id, string $email): User
{
    $user = \Mockery::mock(User::class);
    $user->allows('id')->andReturn($id);
    $user->allows('email')->andReturn($email);
    $user->allows('hasRole')->andReturnFalse();
    $user->allows('isInGroup')->andReturnFalse();

    return $user;
}
