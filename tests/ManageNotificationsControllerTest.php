<?php

namespace Ghijk\CpNotifications\Tests;

use Ghijk\CpNotifications\Contracts\AcknowledgementRepository;
use Ghijk\CpNotifications\Http\Controllers\ManageNotificationsController;
use Ghijk\CpNotifications\Retention\NotificationPurgeService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Mockery;
use Psr\Log\LoggerInterface;
use Statamic\Contracts\Auth\User;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ManageNotificationsControllerTest extends TestCase
{
    public function test_manual_purge_requires_permission_and_literal_confirmation(): void
    {
        $acknowledgements = Mockery::mock(AcknowledgementRepository::class);
        $logger = Mockery::mock(LoggerInterface::class);
        $purge = new NotificationPurgeService($acknowledgements, $logger);
        $controller = new ManageNotificationsController;
        $denied = $this->request(false, false);

        try {
            $controller->purge($denied, $purge);
            $this->fail('Expected unauthorized purge to abort.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $this->expectException(ValidationException::class);
        $controller->purge($this->request(true, false), $purge);
    }

    public function test_confirmed_authorized_purge_uses_the_manual_service(): void
    {
        $acknowledgements = Mockery::mock(AcknowledgementRepository::class);
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->expects('info')->once()->with(
            'CP notification manual purge completed.',
            Mockery::on(fn (array $context): bool => $context['actor_id'] === 'admin-1'),
        );
        $purge = new NotificationPurgeService($acknowledgements, $logger);

        $response = (new ManageNotificationsController)->purge($this->request(true, true), $purge);

        $this->assertTrue($response->isRedirect());
        $this->assertTrue($this->app['router']->has('statamic.cp.cp-notifications.manage.purge'));
    }

    private function request(bool $allowed, bool $confirmed): Request
    {
        $user = Mockery::mock(User::class);
        $user->allows('can')->with('purge notifications')->andReturn($allowed);
        $user->allows('id')->andReturn('admin-1');
        $request = Request::create('/cp/cp-notifications/manage', 'POST', ['confirmed' => $confirmed]);
        $request->headers->set('referer', '/cp/cp-notifications/manage');
        $request->setUserResolver(fn () => $user);

        return $request;
    }
}
