<?php

namespace Ghijk\CpNotifications\Content;

use RuntimeException;
use Statamic\Contracts\Entries\Collection as CollectionContract;
use Statamic\Facades\Collection;

class NotificationCollectionInstaller
{
    public function install(): CollectionContract
    {
        if ($collection = Collection::find('notifications')) {
            if ($collection->routes()->filter()->isNotEmpty()) {
                throw new RuntimeException(
                    'The existing notifications collection is routed and cannot be managed by CP Notifications.',
                );
            }

            return $collection;
        }

        return tap(
            Collection::make('notifications')
                ->title('Notifications')
                ->routes([])
                ->requiresSlugs(false),
            fn (CollectionContract $collection) => $collection->save(),
        );
    }
}
