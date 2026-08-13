<?php

namespace Ghijk\CpNotifications\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class NotificationNudge extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $notificationTitle) {}

    public function envelope(): Envelope
    {
        $from = config('cp-notifications.nudge.from_address');

        return new Envelope(
            from: $from ? new Address((string) $from) : null,
            subject: 'Reminder: '.$this->notificationTitle,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'cp-notifications::mail.nudge',
            with: ['inboxUrl' => cp_route('cp-notifications.inbox')],
        );
    }
}
