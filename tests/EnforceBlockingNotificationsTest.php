<?php

namespace Ghijk\CpNotifications\Tests\Pest\EnforceBlockingNotificationsTest;

use Ghijk\CpNotifications\Audience\AudienceMatcher;
use Ghijk\CpNotifications\Contracts\AcknowledgementRepository;
use Ghijk\CpNotifications\Contracts\SnoozeRepository;
use Ghijk\CpNotifications\Http\Middleware\EnforceBlockingNotifications;
use Ghijk\CpNotifications\Notifications\ActiveStackResolver;
use Ghijk\CpNotifications\Notifications\ActiveWindow;
use Ghijk\CpNotifications\Notifications\BlockingNoticeResolver;
use Ghijk\CpNotifications\Notifications\GatingStack;
use Ghijk\CpNotifications\Notifications\NotificationOrder;
use Ghijk\CpNotifications\ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;
use Statamic\Contracts\Auth\User as UserContract;
use Statamic\Contracts\Auth\UserRepository;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Facades\User;

afterEach(function () {
    Entry::query()->where('collection', 'notifications')->get()->each->delete();
    Collection::find('notifications')?->delete();

});

test('enforcement middleware is registered in the authenticated cp group', function () {
    $properties = (new \ReflectionClass(ServiceProvider::class))->getDefaultProperties();

    expect($properties['middlewareGroups']['statamic.cp.authenticated'])->toBe([EnforceBlockingNotifications::class]);
    expect($this->app['router']->getMiddlewareGroups()['statamic.cp.authenticated'])->toContain(EnforceBlockingNotifications::class);
});

test('middleware uses strict configuration and live blocking resolution', function () {
    $source = file_get_contents(__DIR__.'/../src/Http/Middleware/EnforceBlockingNotifications.php');

    $this->assertStringContainsString("config('cp-notifications.enforcement') !== 'strict'", $source);
    $this->assertStringContainsString('$this->blocking->resolve($user)->isNotEmpty()', $source);
});

test('user with active blocking notice is redirected to interstitial', function () {
    config()->set('cp-notifications.enforcement', 'strict');
    Collection::make('notifications')->sites([Site::default()->handle()])->save();
    Entry::make()
        ->id('blocking-notice')
        ->collection('notifications')
        ->locale(Site::default()->handle())
        ->published(true)
        ->data([
            'audience' => ['all' => true],
            'blocking' => true,
            'start_date' => '2020-01-01 00:00',
        ])->save();
    $user = \Mockery::mock(UserContract::class);
    $user->allows('id')->andReturn('user-1');
    $user->allows('hasRole')->andReturnFalse();
    $user->allows('isInGroup')->andReturnFalse();
    $user->allows('can')->with('bypass notifications')->andReturnFalse();
    $originalUsers = User::getFacadeRoot();
    $users = \Mockery::mock(UserRepository::class);
    $users->allows('current')->andReturn($user);
    User::swap($users);
    $acknowledgements = \Mockery::mock(AcknowledgementRepository::class);
    $acknowledgements->allows('find')->andReturnNull();
    $snoozes = \Mockery::mock(SnoozeRepository::class);
    $snoozes->allows('find')->andReturnNull();
    $active = new ActiveStackResolver(
        new AudienceMatcher,
        new ActiveWindow,
        $acknowledgements,
        $snoozes,
        new NotificationOrder,
    );
    $middleware = new EnforceBlockingNotifications(
        new BlockingNoticeResolver($active, new GatingStack),
    );
    $request = Request::create('/cp/dashboard');
    $request->setRouteResolver(fn () => (new Route('GET', '/cp/dashboard', fn () => null))
        ->name('statamic.cp.index'));

    try {
        $response = $middleware->handle($request, fn () => new Response('allowed'));
    } finally {
        User::swap($originalUsers);
    }

    expect($response->isRedirect(route('statamic.cp.cp-notifications.acknowledge')))->toBeTrue();
});

test('interstitial actions logout session and required asset routes are exempt', function () {
    $source = file_get_contents(__DIR__.'/../src/Http/Middleware/EnforceBlockingNotifications.php');

    foreach ([
        'statamic.cp.cp-notifications.acknowledge',
        'statamic.cp.cp-notifications.api.*',
        'statamic.cp.logout',
        'statamic.cp.token',
        'statamic.cp.extend',
        'statamic.cp.assets.thumbnails.show',
        'statamic.cp.assets.svgs.show',
        'statamic.cp.assets.pdfs.show',
    ] as $route) {
        $this->assertStringContainsString("'{$route}'", $source);
    }
});

test('bypass permission short circuits before blocking resolution', function () {
    $source = file_get_contents(__DIR__.'/../src/Http/Middleware/EnforceBlockingNotifications.php');
    $bypass = strpos($source, '$user->can(\'bypass notifications\')');
    $resolution = strpos($source, '$this->blocking->resolve($user)');

    $this->assertNotFalse($bypass);
    $this->assertNotFalse($resolution);
    expect($bypass)->toBeLessThan($resolution);
});

test('modal mode disables route gating but keeps overlay registration', function () {
    config()->set('cp-notifications.enforcement', 'modal');
    $originalUsers = User::getFacadeRoot();
    $users = \Mockery::mock(UserRepository::class);
    $users->shouldNotReceive('current');
    User::swap($users);
    $acknowledgements = \Mockery::mock(AcknowledgementRepository::class);
    $snoozes = \Mockery::mock(SnoozeRepository::class);
    $active = new ActiveStackResolver(
        new AudienceMatcher,
        new ActiveWindow,
        $acknowledgements,
        $snoozes,
        new NotificationOrder,
    );
    $middleware = new EnforceBlockingNotifications(
        new BlockingNoticeResolver($active, new GatingStack),
    );

    try {
        $response = $middleware->handle(
            Request::create('/cp/dashboard'),
            fn () => new Response('allowed'),
        );
    } finally {
        User::swap($originalUsers);
    }

    expect($response->getContent())->toBe('allowed');
    $this->assertStringContainsString(
        "append('cp-notification-overlay'",
        file_get_contents(__DIR__.'/../resources/js/addon.js'),
    );
});
