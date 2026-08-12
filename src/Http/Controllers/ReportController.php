<?php

namespace Ghijk\CpNotifications\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;

final class ReportController
{
    public function index(Request $request): View
    {
        $this->authorize($request);
        $collection = Collection::find('notifications');

        return view('cp-notifications::reports', [
            'notifications' => $collection
                ? $collection->queryEntries()->where('site', Site::default()->handle())->get()
                : collect(),
        ]);
    }

    public function show(Request $request, string $notification): View
    {
        $this->authorize($request);
        $entry = Entry::find($notification);

        abort_unless(
            $entry
                && $entry->collectionHandle() === 'notifications'
                && $entry->locale() === Site::default()->handle(),
            404,
        );

        return view('cp-notifications::report', ['notification' => $entry]);
    }

    private function authorize(Request $request): void
    {
        abort_unless($request->user()?->can('view notification reports'), 403);
    }
}
