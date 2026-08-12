<?php

namespace Ghijk\CpNotifications\Tests;

use Ghijk\CpNotifications\Audience\AudienceMatcher;
use Ghijk\CpNotifications\Audience\AudienceResolver;
use Ghijk\CpNotifications\Contracts\AcknowledgementRepository;
use Ghijk\CpNotifications\Contracts\SnoozeRepository;
use Ghijk\CpNotifications\Data\Acknowledgement;
use Ghijk\CpNotifications\Data\Snooze;
use Ghijk\CpNotifications\Http\Controllers\ReportController;
use Ghijk\CpNotifications\Reports\NotificationReportResolver;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Mockery;
use Statamic\Contracts\Auth\User as UserContract;
use Statamic\Contracts\Auth\UserRepository;
use Statamic\Auth\UserCollection;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ReportControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        Entry::query()->where('collection', 'notifications')->get()->each->delete();
        Collection::find('notifications')?->delete();

        parent::tearDown();
    }

    public function test_report_routes_are_authorized_and_scoped_per_notice(): void
    {
        $user = Mockery::mock(UserContract::class);
        $user->allows('can')->with('view notification reports')->andReturnTrue();
        $request = Request::create('/');
        $request->setUserResolver(fn () => $user);
        Collection::make('notifications')->sites([Site::default()->handle()])->save();
        Entry::make()->id('notice-1')->collection('notifications')->locale(Site::default()->handle())
            ->data(['title' => 'Notice one', 'audience' => ['all' => true]])->save();
        $controller = new ReportController;
        $targeted = Mockery::mock(UserContract::class);
        $targeted->allows('id')->andReturn('targeted-user');
        $targeted->allows('hasRole')->andReturnFalse();
        $targeted->allows('isInGroup')->andReturnFalse();
        $users = Mockery::mock(UserRepository::class);
        $users->allows('all')->andReturn(new UserCollection([$targeted]));
        $audience = new AudienceResolver($users, new AudienceMatcher);
        $acknowledgements = Mockery::mock(AcknowledgementRepository::class);
        $acknowledgements->allows('find')->with('notice-1', 'targeted-user')->andReturn(
            new Acknowledgement('ack-1', 'notice-1', 'targeted-user', CarbonImmutable::parse('2026-08-12 12:00')),
        );
        CarbonImmutable::setTestNow('2026-08-12 12:00:00 Pacific/Auckland');
        $snoozes = Mockery::mock(SnoozeRepository::class);
        $snoozes->allows('find')->with('notice-1', 'targeted-user')->andReturn(
            new Snooze('notice-1', 'targeted-user', CarbonImmutable::parse('2026-08-13 12:00 Pacific/Auckland')),
        );
        $reportResolver = new NotificationReportResolver($audience, $acknowledgements, $snoozes);

        $index = $controller->index($request);
        $report = $controller->show($request, 'notice-1', $reportResolver);

        $this->assertSame(['notice-1'], $index->getData()['notifications']->map->id()->all());
        $this->assertSame('notice-1', $report->getData()['notification']->id());
        $this->assertSame(['targeted-user'], $report->getData()['rows']->pluck('user')->map->id()->all());
        $this->assertSame('2026-08-12 12:00:00', $report->getData()['rows']->first()['acknowledgement']->acknowledgedAt->format('Y-m-d H:i:s'));
        $this->assertTrue($report->getData()['rows']->first()['snooze_active']);
        $this->assertTrue($this->app['router']->has('statamic.cp.cp-notifications.reports.show'));
    }

    public function test_report_access_requires_the_reporting_permission(): void
    {
        $user = Mockery::mock(UserContract::class);
        $user->allows('can')->with('view notification reports')->andReturnFalse();
        $request = Request::create('/');
        $request->setUserResolver(fn () => $user);

        $this->expectException(HttpException::class);
        $this->expectExceptionCode(0);

        (new ReportController)->index($request);
    }
}
