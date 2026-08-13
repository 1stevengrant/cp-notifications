<?php

namespace Ghijk\CpNotifications\Tests\Pest\ReportControllerTest;

use Carbon\CarbonImmutable;
use Ghijk\CpNotifications\Audience\AudienceMatcher;
use Ghijk\CpNotifications\Audience\AudienceResolver;
use Ghijk\CpNotifications\Contracts\AcknowledgementRepository;
use Ghijk\CpNotifications\Contracts\SnoozeRepository;
use Ghijk\CpNotifications\Data\Acknowledgement;
use Ghijk\CpNotifications\Data\Snooze;
use Ghijk\CpNotifications\Http\Controllers\ReportController;
use Ghijk\CpNotifications\Jobs\SendNotificationNudges;
use Ghijk\CpNotifications\Reports\NotificationReportResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Statamic\Auth\UserCollection;
use Statamic\Contracts\Auth\User as UserContract;
use Statamic\Contracts\Auth\UserRepository;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Symfony\Component\HttpKernel\Exception\HttpException;

afterEach(function () {
    CarbonImmutable::setTestNow();
    Entry::query()->where('collection', 'notifications')->get()->each->delete();
    Collection::find('notifications')?->delete();

});

test('report routes are authorized and scoped per notice', function () {
    $user = \Mockery::mock(UserContract::class);
    $user->allows('can')->with('view notification reports')->andReturnTrue();
    $request = Request::create('/');
    $request->setUserResolver(fn () => $user);
    Collection::make('notifications')->sites([Site::default()->handle()])->save();
    Entry::make()->id('notice-1')->collection('notifications')->locale(Site::default()->handle())
        ->data(['title' => 'Notice one', 'audience' => ['all' => true]])->save();
    $controller = new ReportController;
    $targeted = \Mockery::mock(UserContract::class);
    $targeted->allows('id')->andReturn('targeted-user');
    $targeted->allows('name')->andReturn('Targeted User');
    $targeted->allows('email')->andReturn('targeted@example.com');
    $targeted->allows('hasRole')->andReturnFalse();
    $targeted->allows('isInGroup')->andReturnFalse();
    $users = \Mockery::mock(UserRepository::class);
    $users->allows('all')->andReturn(new UserCollection([$targeted]));
    $former = \Mockery::mock(UserContract::class);
    $former->allows('id')->andReturn('former-user');
    $former->allows('name')->andReturn('Former User');
    $former->allows('email')->andReturn('former@example.com');
    $users->allows('find')->with('former-user')->andReturn($former);
    $audience = new AudienceResolver($users, new AudienceMatcher);
    $targetedAcknowledgement = new Acknowledgement(
        'ack-1',
        'notice-1',
        'targeted-user',
        CarbonImmutable::parse('2026-08-12 12:00'),
    );
    $formerAcknowledgement = new Acknowledgement(
        'ack-2',
        'notice-1',
        'former-user',
        CarbonImmutable::parse('2026-08-11 12:00'),
    );
    $acknowledgements = \Mockery::mock(AcknowledgementRepository::class);
    $acknowledgements->allows('find')->with('notice-1', 'targeted-user')->andReturn($targetedAcknowledgement);
    $acknowledgements->allows('forNotification')->with('notice-1')->andReturn(collect([
        $targetedAcknowledgement,
        $formerAcknowledgement,
    ]));
    CarbonImmutable::setTestNow('2026-08-12 12:00:00 Pacific/Auckland');
    $snoozes = \Mockery::mock(SnoozeRepository::class);
    $snoozes->allows('find')->with('notice-1', 'targeted-user')->andReturn(
        new Snooze('notice-1', 'targeted-user', CarbonImmutable::parse('2026-08-13 12:00 Pacific/Auckland')),
    );
    $snoozes->allows('find')->with('notice-1', 'former-user')->andReturnNull();
    $reportResolver = new NotificationReportResolver($audience, $acknowledgements, $snoozes, $users);

    $index = $controller->index($request);
    $report = $controller->show($request, 'notice-1', $reportResolver);
    $export = $controller->export($request, 'notice-1', $reportResolver);

    expect($index->getData()['notifications']->map->id()->all())->toBe(['notice-1']);
    expect($report->getData()['notification']->id())->toBe('notice-1');
    expect($report->getData()['rows']->pluck('user')->map->id()->all())->toBe(['targeted-user', 'former-user']);
    expect($report->getData()['rows']->first()['acknowledgement']->acknowledgedAt->format('Y-m-d H:i:s'))->toBe('2026-08-12 12:00:00');
    expect($report->getData()['rows']->first()['snooze_active'])->toBeTrue();
    expect($report->getData()['rows']->last()['currently_targeted'])->toBeFalse();
    expect($this->app['router']->has('statamic.cp.cp-notifications.reports.show'))->toBeTrue();
    expect($this->app['router']->has('statamic.cp.cp-notifications.reports.export'))->toBeTrue();
    $this->assertStringContainsString('notification-notice-1-report.csv', $export->headers->get('Content-Disposition'));

    ob_start();
    $export->sendContent();
    $csv = ob_get_clean();

    $this->assertStringContainsString('User,Email,Audience,Status,"Acknowledged at",Snooze', $csv);
    $this->assertStringContainsString('"Targeted User",targeted@example.com,Current,Acknowledged', $csv);
    $this->assertStringContainsString('"Former User",former@example.com,Former,Acknowledged', $csv);
});

test('report access requires the reporting permission', function () {
    $user = \Mockery::mock(UserContract::class);
    $user->allows('can')->with('view notification reports')->andReturnFalse();
    $request = Request::create('/');
    $request->setUserResolver(fn () => $user);

    $this->expectException(HttpException::class);
    $this->expectExceptionCode(0);

    (new ReportController)->index($request);
});

test('authorized manual reminder dispatches the shared nudge job', function () {
    Bus::fake();
    $user = \Mockery::mock(UserContract::class);
    $user->allows('can')->with('view notification reports')->andReturnTrue();
    $request = Request::create('/');
    $request->headers->set('referer', '/cp/cp-notifications/reports/notice-1');
    $request->setUserResolver(fn () => $user);
    Collection::make('notifications')->sites([Site::default()->handle()])->save();
    Entry::make()->id('notice-1')->collection('notifications')->locale(Site::default()->handle())
        ->data(['title' => 'Notice one', 'audience' => ['all' => true]])->save();

    (new ReportController)->remind($request, 'notice-1');

    Bus::assertDispatched(SendNotificationNudges::class, fn ($job): bool => $job->notificationId === 'notice-1' && $job->manual
    );
});
