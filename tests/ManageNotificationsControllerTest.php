<?php

namespace Ghijk\CpNotifications\Tests\Pest\ManageNotificationsControllerTest;

use Ghijk\CpNotifications\Contracts\AcknowledgementRepository;
use Ghijk\CpNotifications\Http\Controllers\ManageNotificationsController;
use Ghijk\CpNotifications\Retention\NotificationPurgeService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Psr\Log\LoggerInterface;
use Statamic\Contracts\Auth\User;
use Symfony\Component\HttpKernel\Exception\HttpException;

test('manual purge requires permission and literal confirmation', function () {
    $acknowledgements = \Mockery::mock(AcknowledgementRepository::class);
    $logger = \Mockery::mock(LoggerInterface::class);
    $purge = new NotificationPurgeService($acknowledgements, $logger);
    $controller = new ManageNotificationsController;
    $denied = request(false, false);

    try {
        $controller->purge($denied, $purge);
        $this->fail('Expected unauthorized purge to abort.');
    } catch (HttpException $exception) {
        expect($exception->getStatusCode())->toBe(403);
    }

    $this->expectException(ValidationException::class);
    $controller->purge(request(true, false), $purge);
});

test('confirmed authorized purge uses the manual service', function () {
    $acknowledgements = \Mockery::mock(AcknowledgementRepository::class);
    $logger = \Mockery::mock(LoggerInterface::class);
    $logger->expects('info')->once()->with(
        'CP notification manual purge completed.',
        \Mockery::on(fn (array $context): bool => $context['actor_id'] === 'admin-1'),
    );
    $purge = new NotificationPurgeService($acknowledgements, $logger);

    $response = (new ManageNotificationsController)->purge(request(true, true), $purge);

    expect($response->isRedirect())->toBeTrue();
    expect($this->app['router']->has('statamic.cp.cp-notifications.manage.purge'))->toBeTrue();
});

function request(bool $allowed, bool $confirmed): Request
{
    $user = \Mockery::mock(User::class);
    $user->allows('can')->with('purge notifications')->andReturn($allowed);
    $user->allows('id')->andReturn('admin-1');
    $request = Request::create('/cp/cp-notifications/manage', 'POST', ['confirmed' => $confirmed]);
    $request->headers->set('referer', '/cp/cp-notifications/manage');
    $request->setUserResolver(fn () => $user);

    return $request;
}
