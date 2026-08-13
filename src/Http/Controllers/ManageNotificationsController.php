<?php

namespace Ghijk\CpNotifications\Http\Controllers;

use Ghijk\CpNotifications\Retention\NotificationPurgeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ManageNotificationsController
{
    public function index(Request $request, NotificationPurgeService $purge): View
    {
        abort_unless($request->user()?->can('manage notifications'), 403);

        return view('cp-notifications::manage', [
            'purgeCandidates' => $request->user()->can('purge notifications')
                ? $purge->candidates()
                : collect(),
            'canPurge' => $request->user()->can('purge notifications'),
        ]);
    }

    public function purge(Request $request, NotificationPurgeService $purge): RedirectResponse
    {
        abort_unless($request->user()?->can('purge notifications'), 403);
        $request->validate(['confirmed' => ['required', 'accepted']]);
        $ids = $purge->purge((string) $request->user()->id());

        return back()->with('success', trans_choice(
            '{0} No notifications were eligible for removal.|{1} One notification was removed.|[2,*] :count notifications were removed.',
            $ids->count(),
            ['count' => $ids->count()],
        ));
    }
}
