<?php

namespace Ghijk\CpNotifications\Notifications;

use Illuminate\Support\Collection;
use Statamic\Contracts\Auth\User;

final class GatingStack
{
    public function forUser(Collection $activeNotices, User $user): Collection
    {
        return $user->can('bypass notifications')
            ? collect()
            : $activeNotices->values();
    }
}
