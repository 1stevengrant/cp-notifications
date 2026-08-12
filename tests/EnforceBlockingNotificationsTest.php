<?php

namespace Ghijk\CpNotifications\Tests;

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
use Mockery;
use Statamic\Contracts\Auth\User as UserContract;
use Statamic\Contracts\Auth\UserRepository;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Facades\User;

class EnforceBlockingNotificationsTest extends TestCase
{
    protected function tearDown(): void
    {
        Entry::query()->where('collection', 'notifications')->get()->each->delete();
        Collection::find('notifications')?->delete();

        parent::tearDown();
    }

    public function test_enforcement_middleware_is_registered_in_the_authenticated_cp_group(): void
    {
        $properties = (new \ReflectionClass(ServiceProvider::class))->getDefaultProperties();

        $this->assertSame(
            [EnforceBlockingNotifications::class],
            $properties['middlewareGroups']['statamic.cp.authenticated'],
        );
        $this->assertContains(
            EnforceBlockingNotifications::class,
            $this->app['router']->getMiddlewareGroups()['statamic.cp.authenticated'],
        );
    }

    public function test_middleware_uses_strict_configuration_and_live_blocking_resolution(): void
    {
        $source = file_get_contents(__DIR__.'/../src/Http/Middleware/EnforceBlockingNotifications.php');

        $this->assertStringContainsString("config('cp-notifications.enforcement') !== 'strict'", $source);
        $this->assertStringContainsString('$this->blocking->resolve($user)->isNotEmpty()', $source);
    }

    public function test_user_with_active_blocking_notice_is_redirected_to_interstitial(): void
    {
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
        $user = Mockery::mock(UserContract::class);
        $user->allows('id')->andReturn('user-1');
        $user->allows('hasRole')->andReturnFalse();
        $user->allows('isInGroup')->andReturnFalse();
        $user->allows('can')->with('bypass notifications')->andReturnFalse();
        $originalUsers = User::getFacadeRoot();
        $users = Mockery::mock(UserRepository::class);
        $users->allows('current')->andReturn($user);
        User::swap($users);
        $acknowledgements = Mockery::mock(AcknowledgementRepository::class);
        $acknowledgements->allows('find')->andReturnNull();
        $snoozes = Mockery::mock(SnoozeRepository::class);
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

        $this->assertTrue($response->isRedirect(route('statamic.cp.cp-notifications.acknowledge')));
    }

    public function test_interstitial_actions_logout_session_and_required_asset_routes_are_exempt(): void
    {
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
    }

    public function test_bypass_permission_short_circuits_before_blocking_resolution(): void
    {
        $source = file_get_contents(__DIR__.'/../src/Http/Middleware/EnforceBlockingNotifications.php');
        $bypass = strpos($source, '$user->can(\'bypass notifications\')');
        $resolution = strpos($source, '$this->blocking->resolve($user)');

        $this->assertNotFalse($bypass);
        $this->assertNotFalse($resolution);
        $this->assertLessThan($resolution, $bypass);
    }

    public function test_modal_mode_disables_route_gating_but_keeps_overlay_registration(): void
    {
        config()->set('cp-notifications.enforcement', 'modal');
        $originalUsers = User::getFacadeRoot();
        $users = Mockery::mock(UserRepository::class);
        $users->shouldNotReceive('current');
        User::swap($users);
        $acknowledgements = Mockery::mock(AcknowledgementRepository::class);
        $snoozes = Mockery::mock(SnoozeRepository::class);
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

        $this->assertSame('allowed', $response->getContent());
        $this->assertStringContainsString(
            "append('cp-notification-overlay'",
            file_get_contents(__DIR__.'/../resources/js/addon.js'),
        );
    }
}
