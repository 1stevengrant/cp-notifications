<?php

namespace Ghijk\CpNotifications\Nudges;

use Ghijk\CpNotifications\Audience\AudienceResolver;
use Ghijk\CpNotifications\Contracts\AcknowledgementRepository;
use Ghijk\CpNotifications\Contracts\NudgeDeliveryRepository;
use Ghijk\CpNotifications\Mail\NotificationNudge;
use Illuminate\Contracts\Mail\Factory as MailFactory;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;

final class NotificationNudgeService
{
    public function __construct(
        private AudienceResolver $audience,
        private AcknowledgementRepository $acknowledgements,
        private NudgeDeliveryRepository $deliveries,
        private NudgeEligibility $eligibility,
        private MailFactory $mail,
    ) {}

    public function send(string $notificationId, bool $manual = false): int
    {
        $notification = Entry::find($notificationId);

        if (! $notification
            || $notification->collectionHandle() !== 'notifications'
            || $notification->locale() !== Site::default()->handle()) {
            return 0;
        }

        return $this->audience->resolve($notification)
            ->reject(fn ($user): bool => $this->acknowledgements
                ->find($notificationId, (string) $user->id()) !== null)
            ->filter->email()
            ->filter(function ($user) use ($notification, $notificationId, $manual): bool {
                $delivery = $this->deliveries->find($notificationId, (string) $user->id());

                return $this->eligibility->eligible(
                    $notification,
                    $delivery?->lastSentAt,
                    manual: $manual,
                );
            })
            ->each(fn ($user) => $this->mail->mailer()->to($user->email())->send(
                new NotificationNudge((string) $notification->get('title')),
            ))
            ->each(fn ($user) => $this->deliveries->recordSent($notificationId, (string) $user->id()))
            ->count();
    }
}
