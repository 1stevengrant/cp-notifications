<?php

namespace Ghijk\CpNotifications\Tests\Pest\GatingStackTest;

use Ghijk\CpNotifications\Notifications\GatingStack;
use Illuminate\Support\Collection;
use Statamic\Contracts\Auth\User;

test('bypass users keep visible notices but have no gating stack', function () {
    $notices = collect(['notice-1', 'notice-2']);
    $user = user(canBypass: true);

    $gating = (new GatingStack)->forUser($notices, $user);

    expect($notices->all())->toBe(['notice-1', 'notice-2']);
    expect($gating)->toBeInstanceOf(Collection::class);
    expect($gating)->toHaveCount(0);
});

test('non bypass users retain their active gating stack', function () {
    $notices = collect(['notice-1', 'notice-2']);

    expect((new GatingStack)->forUser($notices, user(canBypass: false))->all())->toBe(['notice-1', 'notice-2']);
});

function user(bool $canBypass): User
{
    $user = \Mockery::mock(User::class);
    $user->expects('can')->with('bypass notifications')->andReturn($canBypass);

    return $user;
}
