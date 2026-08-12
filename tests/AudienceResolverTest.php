<?php

namespace Ghijk\CpNotifications\Tests;

use Ghijk\CpNotifications\Audience\AudienceMatcher;
use Ghijk\CpNotifications\Audience\AudienceResolver;
use Mockery;
use Statamic\Auth\UserCollection;
use Statamic\Contracts\Auth\User;
use Statamic\Contracts\Auth\UserRepository;
use PHPUnit\Framework\Attributes\DataProvider;

class AudienceResolverTest extends TestCase
{
    public function test_users_targeted_by_overlapping_rules_appear_only_once(): void
    {
        $targeted = $this->user('user-1', roles: ['editor'], groups: ['operations']);
        $other = $this->user('user-2');
        $users = Mockery::mock(UserRepository::class);
        $users->allows('all')->andReturn(new UserCollection([$targeted, $targeted, $other]));

        $resolved = (new AudienceResolver($users, new AudienceMatcher))->resolve([
            'all' => false,
            'roles' => ['editor'],
            'groups' => ['operations'],
            'users' => ['user-1'],
        ]);

        $this->assertInstanceOf(UserCollection::class, $resolved);
        $this->assertCount(1, $resolved);
        $this->assertSame('user-1', $resolved->first()->id());
    }

    public function test_all_users_are_returned_once_by_id(): void
    {
        $first = $this->user('user-1');
        $second = $this->user('user-2');
        $users = Mockery::mock(UserRepository::class);
        $users->allows('all')->andReturn(new UserCollection([$first, $second, $first]));

        $resolved = (new AudienceResolver($users, new AudienceMatcher))->resolve(['all' => true]);

        $this->assertSame(['user-1', 'user-2'], $resolved->map->id()->all());
    }

    #[DataProvider('specificAudienceCases')]
    public function test_each_specific_selector_expands_to_only_matching_users(array $audience): void
    {
        $targeted = $this->user('user-1', roles: ['editor'], groups: ['operations']);
        $other = $this->user('user-2');
        $users = Mockery::mock(UserRepository::class);
        $users->allows('all')->andReturn(new UserCollection([$targeted, $other]));

        $resolved = (new AudienceResolver($users, new AudienceMatcher))->resolve($audience);

        $this->assertSame(['user-1'], $resolved->map->id()->all());
    }

    public static function specificAudienceCases(): array
    {
        return [
            'role' => [['roles' => ['editor']]],
            'group' => [['groups' => ['operations']]],
            'explicit user' => [['users' => ['user-1']]],
        ];
    }

    public function test_audience_membership_is_resolved_live_on_each_call(): void
    {
        $membership = ['user-1' => true, 'user-2' => false];
        $first = $this->dynamicUser('user-1', $membership);
        $second = $this->dynamicUser('user-2', $membership);
        $users = Mockery::mock(UserRepository::class);
        $users->expects('all')->twice()->andReturn(new UserCollection([$first, $second]));
        $resolver = new AudienceResolver($users, new AudienceMatcher);
        $audience = ['roles' => ['editor']];

        $this->assertSame(['user-1'], $resolver->resolve($audience)->map->id()->all());

        $membership = ['user-1' => false, 'user-2' => true];

        $this->assertSame(['user-2'], $resolver->resolve($audience)->map->id()->all());
    }

    private function user(string $id, array $roles = [], array $groups = []): User
    {
        $user = Mockery::mock(User::class);
        $user->allows('id')->andReturn($id);
        $user->allows('hasRole')->andReturnUsing(fn (string $role): bool => in_array($role, $roles, true));
        $user->allows('isInGroup')->andReturnUsing(fn (string $group): bool => in_array($group, $groups, true));

        return $user;
    }

    private function dynamicUser(string $id, array &$membership): User
    {
        $user = Mockery::mock(User::class);
        $user->allows('id')->andReturn($id);
        $user->allows('hasRole')->andReturnUsing(
            function (string $role) use ($id, &$membership): bool {
                return $role === 'editor' && ($membership[$id] ?? false);
            },
        );
        $user->allows('isInGroup')->andReturnFalse();

        return $user;
    }
}
