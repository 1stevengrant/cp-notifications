<?php

namespace Ghijk\CpNotifications\Jobs;

use Ghijk\CpNotifications\Nudges\NotificationNudgeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class SendNotificationNudges implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $notificationId,
        public bool $manual = false,
    ) {
    }

    public function handle(NotificationNudgeService $nudges): void
    {
        $nudges->send($this->notificationId, $this->manual);
    }

    public function uniqueId(): string
    {
        return $this->notificationId;
    }
}
