<?php

namespace Ghijk\CpNotifications\Http\Controllers;

use Ghijk\CpNotifications\Notifications\InboxNoticeResolver;
use Illuminate\Contracts\View\View;
use Statamic\Facades\User;

final class InboxController
{
    public function __invoke(InboxNoticeResolver $notices): View
    {
        $user = User::current();
        abort_unless($user, 401);

        return view('cp-notifications::inbox', [
            'notifications' => $notices->resolve($user),
        ]);
    }
}
