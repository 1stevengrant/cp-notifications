<?php

namespace Ghijk\CpNotifications\Tests\Pest\AudienceResolverTest;

use Ghijk\CpNotifications\Audience\AudienceMatcher;
use Ghijk\CpNotifications\Audience\AudienceResolver;
use Statamic\Auth\UserCollection;
use Statamic\Contracts\Auth\User;
use Statamic\Contracts\Auth\UserRepository;

test('users targeted by overlapping rules appear only once', function () {
    $targeted = user('user-1', roles: ['editor'], groups: ['operations']);
    $other = user('user-2');
    $users = \Mockery::mock(UserRepository::class);
    $users->allows('all')->andReturn(new UserCollection([$targeted, $targeted, $other]));

    $resolved = (new AudienceResolver($users, new AudienceMatcher))->resolve([
        'all' => false,
        'roles' => ['editor'],
        'groups' => ['operations'],
        'users' => ['user-1'],
    ]);

    expect($resolved)->toBeInstanceOf(UserCollection::class);
    expect($resolved)->toHaveCount(1);
    expect($resolved->first()->id())->toBe('user-1');
});

test('all users are returned once by id', function () {
    $first = user('user-1');
    $second = user('user-2');
    $users = \Mockery::mock(UserRepository::class);
    $users->allows('all')->andReturn(new UserCollection([$first, $second, $first]));

    $resolved = (new AudienceResolver($users, new AudienceMatcher))->resolve(['all' => true]);

    expect($resolved->map->id()->all())->toBe(['user-1', 'user-2']);
});

test('each specific selector expands to only matching users', function (array $audience) {
    $targeted = user('user-1', roles: ['editor'], groups: ['operations']);
    $other = user('user-2');
    $users = \Mockery::mock(UserRepository::class);
    $users->allows('all')->andReturn(new UserCollection([$targeted, $other]));

    $resolved = (new AudienceResolver($users, new AudienceMatcher))->resolve($audience);

    expect($resolved->map->id()->all())->toBe(['user-1']);
})->with('specificAudienceCases');

dataset('specificAudienceCases', function () {
    return [
        'role' => [['roles' => ['editor']]],
        'group' => [['groups' => ['operations']]],
        'explicit user' => [['users' => ['user-1']]],
    ];
});

test('audience membership is resolved live on each call', function () {
    $membership = ['user-1' => true, 'user-2' => false];
    $first = dynamicUser('user-1', $membership);
    $second = dynamicUser('user-2', $membership);
    $users = \Mockery::mock(UserRepository::class);
    $users->expects('all')->twice()->andReturn(new UserCollection([$first, $second]));
    $resolver = new AudienceResolver($users, new AudienceMatcher);
    $audience = ['roles' => ['editor']];

    expect($resolver->resolve($audience)->map->id()->all())->toBe(['user-1']);

    $membership = ['user-1' => false, 'user-2' => true];

    expect($resolver->resolve($audience)->map->id()->all())->toBe(['user-2']);
});

function user(string $id, array $roles = [], array $groups = []): User
{
    $user = \Mockery::mock(User::class);
    $user->allows('id')->andReturn($id);
    $user->allows('hasRole')->andReturnUsing(fn (string $role): bool => in_array($role, $roles, true));
    $user->allows('isInGroup')->andReturnUsing(fn (string $group): bool => in_array($group, $groups, true));

    return $user;
}

function dynamicUser(string $id, array &$membership): User
{
    $user = \Mockery::mock(User::class);
    $user->allows('id')->andReturn($id);
    $user->allows('hasRole')->andReturnUsing(
        function (string $role) use ($id, &$membership): bool {
            return $role === 'editor' && ($membership[$id] ?? false);
        },
    );
    $user->allows('isInGroup')->andReturnFalse();

    return $user;
}
