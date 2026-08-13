<?php

namespace Ghijk\CpNotifications\Tests\Pest\InboxNoticeResolverTest;

use Carbon\CarbonImmutable;
use Ghijk\CpNotifications\Audience\AudienceMatcher;
use Ghijk\CpNotifications\Contracts\AcknowledgementRepository;
use Ghijk\CpNotifications\Contracts\SnoozeRepository;
use Ghijk\CpNotifications\Data\Acknowledgement;
use Ghijk\CpNotifications\Http\Controllers\InboxController;
use Ghijk\CpNotifications\Notifications\ActiveWindow;
use Ghijk\CpNotifications\Notifications\GatingStack;
use Ghijk\CpNotifications\Notifications\InboxNoticeResolver;
use Statamic\Contracts\Auth\User;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;

afterEach(function () {
    CarbonImmutable::setTestNow();
    Entry::query()->where('collection', 'notifications')->get()->each->delete();
    Collection::find('notifications')?->delete();

});

test('inbox contains only published notices targeting the user', function () {
    Collection::make('notifications')->sites([Site::default()->handle()])->save();
    notice('targeted', true, ['users' => ['user-1']])->save();
    notice('other', true, ['users' => ['user-2']])->save();
    notice('draft', false, ['users' => ['user-1']])->save();
    $user = \Mockery::mock(User::class);
    $user->allows('id')->andReturn('user-1');
    $user->allows('hasRole')->andReturnFalse();
    $user->allows('isInGroup')->andReturnFalse();

    $acknowledgements = \Mockery::mock(AcknowledgementRepository::class);
    $acknowledgements->allows('find')->andReturnNull();
    $snoozes = \Mockery::mock(SnoozeRepository::class);
    $snoozes->allows('find')->andReturnNull();
    $resolved = (new InboxNoticeResolver(
        new AudienceMatcher,
        new ActiveWindow,
        $acknowledgements,
        $snoozes,
    ))->resolve($user);

    expect($resolved->pluck('notification')->map->id()->all())->toBe(['targeted']);
});

test('bypass permission does not hide targeted inbox notices', function () {
    Collection::make('notifications')->sites([Site::default()->handle()])->save();
    notice('targeted', true, ['all' => true])->save();
    $user = \Mockery::mock(User::class);
    $user->allows('id')->andReturn('user-1');
    $user->allows('hasRole')->andReturnFalse();
    $user->allows('isInGroup')->andReturnFalse();
    $user->allows('can')->with('bypass notifications')->andReturnTrue();
    $acknowledgements = \Mockery::mock(AcknowledgementRepository::class);
    $acknowledgements->allows('find')->andReturnNull();
    $snoozes = \Mockery::mock(SnoozeRepository::class);
    $snoozes->allows('find')->andReturnNull();

    $items = (new InboxNoticeResolver(
        new AudienceMatcher,
        new ActiveWindow,
        $acknowledgements,
        $snoozes,
    ))->resolve($user);

    expect($items->pluck('notification')->map->id()->all())->toBe(['targeted']);
    expect((new GatingStack)
        ->forUser($items->pluck('notification'), $user))->toHaveCount(0);
});

test('multisite inbox reads only the canonical default site', function () {
    config()->set('statamic.system.multisite', true);
    Site::setSites([
        'default' => ['name' => 'Default', 'url' => '/', 'locale' => 'en_US'],
        'secondary' => ['name' => 'Secondary', 'url' => '/secondary/', 'locale' => 'en_US'],
    ]);
    Collection::make('notifications')->sites(['default'])->propagate(false)->save();
    notice('canonical', true, ['all' => true])->locale('default')->save();
    notice('secondary-copy', true, ['all' => true])->locale('secondary')->save();
    $user = \Mockery::mock(User::class);
    $user->allows('id')->andReturn('user-1');
    $user->allows('hasRole')->andReturnFalse();
    $user->allows('isInGroup')->andReturnFalse();
    $acknowledgements = \Mockery::mock(AcknowledgementRepository::class);
    $acknowledgements->allows('find')->andReturnNull();
    $snoozes = \Mockery::mock(SnoozeRepository::class);
    $snoozes->allows('find')->andReturnNull();

    $items = (new InboxNoticeResolver(
        new AudienceMatcher,
        new ActiveWindow,
        $acknowledgements,
        $snoozes,
    ))->resolve($user);

    expect($items->pluck('notification')->map->id()->all())->toBe(['canonical']);
});

test('inbox route uses the user specific controller', function () {
    $route = $this->app['router']->getRoutes()->getByName('statamic.cp.cp-notifications.inbox');

    expect($route->getActionName())->toBe(InboxController::class);
    $this->assertStringContainsString(
        'notification-inbox',
        file_get_contents(__DIR__.'/../resources/views/inbox.blade.php'),
    );
});

test('active and previously read notices are both returned', function () {
    config()->set('app.timezone', 'Pacific/Auckland');
    CarbonImmutable::setTestNow('2026-08-12 12:00:00 Pacific/Auckland');
    Collection::make('notifications')->sites([Site::default()->handle()])->save();
    notice('active', true, ['all' => true])->save();
    notice('read', true, ['all' => true])->save();
    $user = \Mockery::mock(User::class);
    $user->allows('id')->andReturn('user-1');
    $user->allows('hasRole')->andReturnFalse();
    $user->allows('isInGroup')->andReturnFalse();
    $acknowledgements = \Mockery::mock(AcknowledgementRepository::class);
    $acknowledgements->allows('find')->with('active', 'user-1')->andReturnNull();
    $acknowledgements->allows('find')->with('read', 'user-1')->andReturn(new Acknowledgement(
        'ack-1',
        'read',
        'user-1',
        CarbonImmutable::now(),
    ));
    $snoozes = \Mockery::mock(SnoozeRepository::class);
    $snoozes->allows('find')->andReturnNull();

    $items = (new InboxNoticeResolver(
        new AudienceMatcher,
        new ActiveWindow,
        $acknowledgements,
        $snoozes,
    ))->resolve($user);

    expect($items->pluck('notification')->map->id()->all())->toBe(['active', 'read']);
    expect($items->pluck('active')->all())->toBe([true, false]);
    expect($items->last()['acknowledgement'])->not->toBeNull();
});

test('expired history obeys retention days and null is indefinite', function () {
    config()->set('app.timezone', 'Pacific/Auckland');
    config()->set('cp-notifications.retention.inbox_days', 30);
    CarbonImmutable::setTestNow('2026-08-12 12:00:00 Pacific/Auckland');
    Collection::make('notifications')->sites([Site::default()->handle()])->save();
    notice('boundary', true, ['all' => true])
        ->set('end_date', '2026-07-13 12:00')->save();
    notice('too-old', true, ['all' => true])
        ->set('end_date', '2026-07-13 11:59:59')->save();
    $user = \Mockery::mock(User::class);
    $user->allows('id')->andReturn('user-1');
    $user->allows('hasRole')->andReturnFalse();
    $user->allows('isInGroup')->andReturnFalse();
    $acknowledgements = \Mockery::mock(AcknowledgementRepository::class);
    $acknowledgements->allows('find')->andReturnNull();
    $snoozes = \Mockery::mock(SnoozeRepository::class);
    $snoozes->allows('find')->andReturnNull();
    $resolver = new InboxNoticeResolver(
        new AudienceMatcher,
        new ActiveWindow,
        $acknowledgements,
        $snoozes,
    );

    expect($resolver->resolve($user)->pluck('notification')->map->id()->all())->toBe(['boundary']);

    config()->set('cp-notifications.retention.inbox_days', null);

    expect($resolver->resolve($user)->pluck('notification')->map->id()->all())->toEqualCanonicalizing(['boundary', 'too-old']);
});

test('expired unacknowledged advisory is history not active', function () {
    config()->set('app.timezone', 'Pacific/Auckland');
    config()->set('cp-notifications.retention.inbox_days', null);
    CarbonImmutable::setTestNow('2026-08-12 12:00:00 Pacific/Auckland');
    Collection::make('notifications')->sites([Site::default()->handle()])->save();
    notice('expired-advisory', true, ['all' => true])
        ->set('blocking', false)
        ->set('end_date', '2026-08-12 11:59:59')
        ->save();
    $user = \Mockery::mock(User::class);
    $user->allows('id')->andReturn('user-1');
    $user->allows('hasRole')->andReturnFalse();
    $user->allows('isInGroup')->andReturnFalse();
    $acknowledgements = \Mockery::mock(AcknowledgementRepository::class);
    $acknowledgements->allows('find')->with('expired-advisory', 'user-1')->andReturnNull();
    $snoozes = \Mockery::mock(SnoozeRepository::class);
    $snoozes->allows('find')->with('expired-advisory', 'user-1')->andReturnNull();

    $items = (new InboxNoticeResolver(
        new AudienceMatcher,
        new ActiveWindow,
        $acknowledgements,
        $snoozes,
    ))->resolve($user);

    expect($items)->toHaveCount(1);
    expect($items->first()['notification']->id())->toBe('expired-advisory');
    expect($items->first()['active'])->toBeFalse();
    expect($items->first()['acknowledgement'])->toBeNull();
});

function notice(string $id, bool $published, array $audience)
{
    return Entry::make()
        ->id($id)
        ->collection('notifications')
        ->locale(Site::default()->handle())
        ->published($published)
        ->data([
            'title' => ucfirst($id),
            'audience' => $audience,
            'start_date' => '2026-01-01 00:00',
        ]);
}
