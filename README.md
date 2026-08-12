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

Create the routeless Notifications collection and its publish blueprint:

```bash
php artisan cp-notifications:install
```

Publish the configuration when defaults need customization:

```bash
php artisan vendor:publish --tag=cp-notifications-config
```

For the Eloquent persistence driver, publish and run the addon migrations:

```bash
php artisan vendor:publish --tag=cp-notifications-migrations
php artisan migrate
```

The package includes compiled CP assets. Contributors changing Vue or CSS
sources should install JavaScript dependencies and rebuild the checked-in
bundle:

```bash
npm ci
npm run build
```

Use `npm run dev` while developing the CP UI. Production installations do not
need Node.js when using the compiled assets shipped with the package.

## Persistence drivers

`CP_NOTIFICATIONS_DRIVER` controls acknowledgement, snooze, and nudge-delivery
state. Supported values are:

- `file` — YAML records beneath
  `storage/statamic/cp-notifications` (or the configured `file_path`). This is
  the natural choice for standard flat-file Statamic installations. Atomic
  record creation preserves once-only acknowledgements across concurrent PHP
  processes.
- `eloquent` — database tables created by the published addon migrations. Use
  this with database-backed Statamic installations, including
  `statamic/eloquent-driver`.
- `auto` — the default. It selects `eloquent` when Composer reports
  `statamic/eloquent-driver` installed and otherwise selects `file`.

Set the driver in `.env` after publishing configuration:

```dotenv
CP_NOTIFICATIONS_DRIVER=file
```

Changing drivers does not migrate existing records. Move the stored data as a
separate deployment operation before switching a live installation.

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
