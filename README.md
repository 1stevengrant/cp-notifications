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

## Compatibility

The release suite is verified against Statamic 6.27, Laravel 13.25, PHP 8.4,
and Vue 3.5. The Composer constraints also retain Laravel 12 coverage through
Testbench 10. The control-panel overlay is a Vue 3 component built with
Statamic UI components (`ui-card`, `ui-badge`, and `ui-button`).

Both persistence paths are covered by the release suite: the default flat-file
repositories are exercised against temporary Statamic storage, while the
Eloquent repositories and published migrations are exercised against a real
database connection. Composer dependency resolution is also checked with
`statamic/eloquent-driver` 5.11 before release; that optional package is not a
runtime dependency of this addon.

## V1 scope

Notices do not recur. Each notice has one `start_date`/`end_date` publication
window; create a new notice when the same message must be published again.

Scheduling uses the application's configured timezone for every recipient.
V1 does not offer per-user timezone scheduling or convert a notice window for
individual users.

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

## Enforcement modes and bypass

Set `CP_NOTIFICATIONS_ENFORCEMENT` to one of:

- `strict` (default) — all targeted active notices appear in the global modal;
  an unresolved blocking notice also redirects authenticated CP routes to the
  acknowledgement interstitial until it is confirmed.
- `modal` — targeted active notices still appear in the same global modal, but
  blocking notices do not guard CP routes.

Users with `bypass notifications` are never route-gated in strict mode. Bypass
does not hide notices, remove them from the user's Inbox, mark them read, or
change audience membership. It should be reserved for operational accounts
that must recover or administer the CP during a blocking-notice incident.

Blocking notices always require explicit confirmation and cannot be dismissed
or snoozed. Eligible advisory notices may be confirmed or snoozed once for 24
hours.

## Notice workflow

Administrators with `manage notifications` create drafts from Notifications →
Manage → Open notification collection. Each notice includes:

- title and Bard body;
- info, warning, or critical severity plus optional numeric priority;
- advisory/blocking behavior and advisory snooze eligibility;
- an audience of all users, roles, groups, explicit users, or overlaps;
- required `start_date` and optional `end_date`, interpreted in the application timezone;
- optional nudge enablement, hours-after-start threshold, and repeat cadence.

A published notice becomes active at `start_date` and stops at `end_date` when
provided. Audiences are expanded live, so later role/group changes immediately
affect visibility and reminders. Explicit priority sorts first, followed by
severity, oldest start, and a deterministic ID tie-break.

> **Blocking notice expiry:** Giving a blocking notice an `end_date` is unusual.
> At that exact time it expires and stops gating users even when they never acknowledged it.
> Leave `end_date` empty when acknowledgement must remain
> mandatory until every targeted user confirms.

Targeted users read active and retained historical notices in Notifications → Inbox.
The global modal presents the same active stack one at a time. Confirming
records an immutable acknowledgement; an eligible advisory can instead use its
single 24-hour snooze.

Administrators with `view notification reports` can open each notice report to
see current recipients, former recipients with preserved acknowledgements,
acknowledgement timestamps, and snooze state. Export CSV downloads those same
rows. “Remind non-ackers” queues the shared reminder workflow immediately;
scheduled reminders use the per-notice threshold and optional cadence.

### Locked notices and corrections

The first acknowledgement locks a notice. Its publish form becomes read-only,
server-side saves are rejected, and the notice cannot be deleted. This preserves
the exact content associated with every recorded acknowledgement.

To correct a locked notice, create and publish a new superseding notice. Give it
the corrected content and audience, and make the relationship clear in its
title/body. If the original is still active and has no suitable end date, keep
it as historical evidence; do not edit or delete it. Reports retain the original
acknowledgements independently from the superseding notice.

Notifications → Manage previews clear-out candidates for administrators with
`purge notifications`. The confirmed action permanently removes only eligible
old, expired, unacknowledged notices and writes a structured audit log. See
“Retention and manual clear-out” for exact safety rules.

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
