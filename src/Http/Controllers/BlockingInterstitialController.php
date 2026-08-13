<?php

namespace Ghijk\CpNotifications\Http\Controllers;

use Ghijk\CpNotifications\Notifications\BlockingNoticeResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Statamic\Facades\User;

final class BlockingInterstitialController
{
    public function __invoke(BlockingNoticeResolver $blocking): View|RedirectResponse
    {
        $user = User::current();
        abort_unless($user, 401);

        $notification = $blocking->resolve($user)->first();

        if (! $notification) {
            return redirect(cp_url('/'));
        }

        return view('cp-notifications::blocking', compact('notification'));
    }
}
