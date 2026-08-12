<?php

namespace Ghijk\CpNotifications\Tests;

use Ghijk\CpNotifications\Notifications\GatingStack;
use Illuminate\Support\Collection;
use Mockery;
use Statamic\Contracts\Auth\User;

class GatingStackTest extends TestCase
{
    public function test_bypass_users_keep_visible_notices_but_have_no_gating_stack(): void
    {
        $notices = collect(['notice-1', 'notice-2']);
        $user = $this->user(canBypass: true);

        $gating = (new GatingStack)->forUser($notices, $user);

        $this->assertSame(['notice-1', 'notice-2'], $notices->all());
        $this->assertInstanceOf(Collection::class, $gating);
        $this->assertCount(0, $gating);
    }

    public function test_non_bypass_users_retain_their_active_gating_stack(): void
    {
        $notices = collect(['notice-1', 'notice-2']);

        $this->assertSame(
            ['notice-1', 'notice-2'],
            (new GatingStack)->forUser($notices, $this->user(canBypass: false))->all(),
        );
    }

    private function user(bool $canBypass): User
    {
        $user = Mockery::mock(User::class);
        $user->expects('can')->with('bypass notifications')->andReturn($canBypass);

        return $user;
    }
}
