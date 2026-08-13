<?php

namespace Ghijk\CpNotifications\Console\Commands;

use Ghijk\CpNotifications\Jobs\SendNotificationNudges;
use Ghijk\CpNotifications\Nudges\NudgeEligibility;
use Illuminate\Console\Command;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;

final class NudgeCommand extends Command
{
    protected $signature = 'cp-notifications:nudge';

    protected $description = 'Queue eligible CP notification reminders';

    public function handle(NudgeEligibility $eligibility): int
    {
        $collection = Collection::find('notifications');

        if (! $collection) {
            $this->components->info('No notifications collection is installed.');

            return self::SUCCESS;
        }

        $notifications = $collection->queryEntries()
            ->where('site', Site::default()->handle())
            ->get()
            ->filter(fn ($notification): bool => $eligibility->eligible($notification));

        $notifications->each(fn ($notification) => SendNotificationNudges::dispatch(
            (string) $notification->id(),
        ));

        $this->components->info("Queued reminders for {$notifications->count()} notification(s).");

        return self::SUCCESS;
    }
}
