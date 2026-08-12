<?php

namespace Ghijk\CpNotifications\Notifications;

use Ghijk\CpNotifications\Audience\AudienceMatcher;
use Illuminate\Support\Collection as SupportCollection;
use Statamic\Contracts\Auth\User;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;

final class InboxNoticeResolver
{
    public function __construct(private AudienceMatcher $audience)
    {
    }

    public function resolve(User $user): SupportCollection
    {
        $collection = Collection::find('notifications');

        if (! $collection) {
            return collect();
        }

        return $collection->queryEntries()
            ->where('site', Site::default()->handle())
            ->get()
            ->filter(fn ($notification): bool => $notification->published())
            ->filter(fn ($notification): bool => $this->audience->matches($notification, $user))
            ->values();
    }
}
