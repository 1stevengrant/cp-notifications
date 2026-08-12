<?php

namespace Ghijk\CpNotifications\Tests;

use Ghijk\CpNotifications\Audience\AudienceMatcher;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Statamic\Contracts\Auth\User;
use Statamic\Entries\Collection;
use Statamic\Entries\Entry;

class AudienceMatcherTest extends TestCase
{
    #[DataProvider('matchingAudiences')]
    public function test_it_matches_each_supported_audience_rule(array $audience): void
    {
        $user = $this->user();

        $this->assertTrue((new AudienceMatcher)->matches($audience, $user));
    }

    public static function matchingAudiences(): array
    {
        return [
            'all users' => [['all' => true]],
            'role' => [['roles' => ['editor']]],
            'group' => [['groups' => ['operations']]],
            'explicit user' => [['users' => ['user-1']]],
        ];
    }

    public function test_it_matches_a_notification_entry(): void
    {
        $notification = (new Entry)
            ->id('notice-1')
            ->collection(Collection::make('notifications'))
            ->set('audience', ['users' => ['user-1']]);

        $this->assertTrue((new AudienceMatcher)->matches($notification, $this->user()));
    }

    public function test_it_rejects_users_outside_the_audience(): void
    {
        $audience = [
            'roles' => ['administrator'],
            'groups' => ['finance'],
            'users' => ['user-2'],
        ];

        $this->assertFalse((new AudienceMatcher)->matches($audience, $this->user()));
    }

    public function test_it_handles_empty_or_malformed_audiences(): void
    {
        $matcher = new AudienceMatcher;

        $this->assertFalse($matcher->matches([], $this->user()));
        $this->assertFalse($matcher->matches(['audience' => 'invalid'], $this->user()));
    }

    private function user(): User
    {
        $user = Mockery::mock(User::class);
        $user->allows('id')->andReturn('user-1');
        $user->allows('hasRole')->andReturnUsing(fn (string $role): bool => $role === 'editor');
        $user->allows('isInGroup')->andReturnUsing(fn (string $group): bool => $group === 'operations');

        return $user;
    }
}
