<?php

namespace Ghijk\CpNotifications\Notifications;

use Illuminate\Support\Collection as SupportCollection;
use Statamic\Contracts\Auth\User;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;

final class BlockingNoticeResolver
{
    public function __construct(
        private ActiveStackResolver $active,
        private GatingStack $gating,
    ) {}

    public function resolve(User $user): SupportCollection
    {
        $collection = Collection::find('notifications');
        $notifications = $collection
            ? $collection->queryEntries()->where('site', Site::default()->handle())->get()
            : collect();

        return $this->gating
            ->forUser($this->active->resolve($notifications, $user), $user)
            ->filter(fn ($notification): bool => (bool) $notification->get('blocking', false))
            ->values();
    }
}
