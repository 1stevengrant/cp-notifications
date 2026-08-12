<?php

namespace Ghijk\CpNotifications\Tests\Pest\AudienceMatcherTest;

use Ghijk\CpNotifications\Audience\AudienceMatcher;
use Statamic\Contracts\Auth\User;
use Statamic\Entries\Collection;
use Statamic\Entries\Entry;

test('it matches each supported audience rule', function (array $audience) {
    $user = user();

    expect((new AudienceMatcher)->matches($audience, $user))->toBeTrue();
})->with('matchingAudiences');

dataset('matchingAudiences', function () {
    return [
        'all users' => [['all' => true]],
        'role' => [['roles' => ['editor']]],
        'group' => [['groups' => ['operations']]],
        'explicit user' => [['users' => ['user-1']]],
    ];
});

test('it matches a notification entry', function () {
    $notification = (new Entry)
        ->id('notice-1')
        ->collection(Collection::make('notifications'))
        ->set('audience', ['users' => ['user-1']]);

    expect((new AudienceMatcher)->matches($notification, user()))->toBeTrue();
});

test('it rejects users outside the audience', function () {
    $audience = [
        'roles' => ['administrator'],
        'groups' => ['finance'],
        'users' => ['user-2'],
    ];

    expect((new AudienceMatcher)->matches($audience, user()))->toBeFalse();
});

test('it handles empty or malformed audiences', function () {
    $matcher = new AudienceMatcher;

    expect($matcher->matches([], user()))->toBeFalse();
    expect($matcher->matches(['audience' => 'invalid'], user()))->toBeFalse();
});

function user(): User
{
    $user = \Mockery::mock(User::class);
    $user->allows('id')->andReturn('user-1');
    $user->allows('hasRole')->andReturnUsing(fn (string $role): bool => $role === 'editor');
    $user->allows('isInGroup')->andReturnUsing(fn (string $group): bool => $group === 'operations');

    return $user;
}
