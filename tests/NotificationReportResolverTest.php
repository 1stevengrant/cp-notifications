<?php

namespace Ghijk\CpNotifications\Tests\Pest\NotificationReportResolverTest;

use Carbon\CarbonImmutable;
use Ghijk\CpNotifications\Audience\AudienceMatcher;
use Ghijk\CpNotifications\Audience\AudienceResolver;
use Ghijk\CpNotifications\Contracts\AcknowledgementRepository;
use Ghijk\CpNotifications\Contracts\SnoozeRepository;
use Ghijk\CpNotifications\Data\Acknowledgement;
use Ghijk\CpNotifications\Reports\NotificationReportResolver;
use Statamic\Auth\UserCollection;
use Statamic\Contracts\Auth\User;
use Statamic\Contracts\Auth\UserRepository;
use Statamic\Entries\Collection;
use Statamic\Entries\Entry;

test('acknowledgement status is resolved live on each report', function () {
    $user = \Mockery::mock(User::class);
    $user->allows('id')->andReturn('user-1');
    $user->allows('hasRole')->andReturnFalse();
    $user->allows('isInGroup')->andReturnFalse();
    $users = \Mockery::mock(UserRepository::class);
    $users->allows('all')->andReturn(new UserCollection([$user]));
    $acknowledgement = null;
    $acknowledgements = \Mockery::mock(AcknowledgementRepository::class);
    $acknowledgements->allows('find')->andReturnUsing(
        function () use (&$acknowledgement) {
            return $acknowledgement;
        },
    );
    $acknowledgements->allows('forNotification')->andReturn(collect());
    $snoozes = \Mockery::mock(SnoozeRepository::class);
    $snoozes->allows('find')->andReturnNull();
    $resolver = new NotificationReportResolver(
        new AudienceResolver($users, new AudienceMatcher),
        $acknowledgements,
        $snoozes,
        $users,
    );
    $notice = (new Entry)
        ->id('notice-1')
        ->collection(Collection::make('notifications'))
        ->set('audience', ['all' => true]);

    expect($resolver->resolve($notice)->sole()['acknowledgement'])->toBeNull();

    $acknowledgement = new Acknowledgement(
        'ack-1',
        'notice-1',
        'user-1',
        CarbonImmutable::parse('2026-08-12 12:00'),
    );

    expect($resolver->resolve($notice)->sole()['acknowledgement']->id)->toBe('ack-1');
});

test('role removal changes targeting but preserves the acknowledgement', function () {
    $hasRole = true;
    $user = \Mockery::mock(User::class);
    $user->allows('id')->andReturn('user-1');
    $user->allows('hasRole')->andReturnUsing(
        function (string $role) use (&$hasRole): bool {
            return $role === 'editor' && $hasRole;
        },
    );
    $user->allows('isInGroup')->andReturnFalse();
    $users = \Mockery::mock(UserRepository::class);
    $users->allows('all')->andReturn(new UserCollection([$user]));
    $users->allows('find')->with('user-1')->andReturn($user);
    $acknowledgement = new Acknowledgement(
        'ack-1',
        'notice-1',
        'user-1',
        CarbonImmutable::parse('2026-08-12 12:00'),
    );
    $acknowledgements = \Mockery::mock(AcknowledgementRepository::class);
    $acknowledgements->allows('find')->andReturn($acknowledgement);
    $acknowledgements->allows('forNotification')->andReturn(collect([$acknowledgement]));
    $snoozes = \Mockery::mock(SnoozeRepository::class);
    $snoozes->allows('find')->andReturnNull();
    $resolver = new NotificationReportResolver(
        new AudienceResolver($users, new AudienceMatcher),
        $acknowledgements,
        $snoozes,
        $users,
    );
    $notice = (new Entry)
        ->id('notice-1')
        ->collection(Collection::make('notifications'))
        ->set('audience', ['roles' => ['editor']]);

    $current = $resolver->resolve($notice)->sole();
    expect($current['currently_targeted'])->toBeTrue();
    expect($current['acknowledgement']->id)->toBe('ack-1');

    $hasRole = false;

    $former = $resolver->resolve($notice)->sole();
    expect($former['currently_targeted'])->toBeFalse();
    expect($former['acknowledgement']->id)->toBe('ack-1');
});
