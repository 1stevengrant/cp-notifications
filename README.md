# CP Notifications

CP Notifications is a Statamic 6 addon for publishing control-panel notices and
tracking user acknowledgements. It targets PHP 8.3 or newer and applications
running Laravel 12 or 13, including the recommended Laravel 13 stack.

## Features

The planned v1 feature set includes:

- targeted notices inside the Statamic control panel;
- immutable acknowledgement records; and
- optional control-panel gating for blocking notices.

## How to Install

You can install this addon via Composer:

```bash
composer require ghijk/cp-notifications
```

## Scheduled reminders

The addon registers `cp-notifications:nudge` with Laravel's scheduler to run
hourly with overlap prevention. Ensure the application scheduler itself is
running. In production, Laravel recommends one cron entry:

```cron
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

For local development, run:

```bash
php artisan schedule:work
```

Reminder delivery is queued, so a queue worker must also be running unless the
application intentionally uses Laravel's `sync` queue connection:

```bash
php artisan queue:work
```

## Retention and manual clear-out

CP Notifications never removes notices or acknowledgement records
automatically. Inbox history is kept for `retention.inbox_days`; `null` keeps
history indefinitely.

The Manage screen provides the only clear-out workflow. An administrator with
`purge notifications` must explicitly confirm it. Clear-out permanently removes
eligible notification entries rather than archiving them. Eligibility is
limited to published notices that:

- have an `end_date` and are expired;
- are at least `retention.inbox_days` past expiry when that value is configured;
- have no acknowledgement records.

With indefinite Inbox retention, any expired published notice without an
acknowledgement may be manually removed. Acknowledgements themselves are never
removed, and a notice with an acknowledgement cannot be deleted through either
the clear-out workflow or Statamic's native entry actions.
