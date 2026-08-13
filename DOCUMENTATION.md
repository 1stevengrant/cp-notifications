# CP Notifications

CP Notifications is a Statamic 6 addon for publishing control-panel notices and
tracking user acknowledgements. It targets PHP 8.3 or newer and applications
running Laravel 12 or 13, including the recommended Laravel 13 stack.

## Features

The planned v1 feature set includes:

- targeted notices inside the Statamic control panel;
- immutable acknowledgement records; and
- optional control-panel gating for blocking notices.

## Screenshots

### Blocking notice interstitial

A blocking notice gates the control panel until the user acknowledges it.

![Blocking notice shown over the acknowledgement interstitial](docs/screenshots/01-blocking-interstitial.png)

### Blocking notice sequence

The next blocking notice appears after the first notice is confirmed.

![Second blocking notice shown after the first notice was confirmed](docs/screenshots/02-blocking-modal-next-notice.png)

### Advisory notice snooze

Eligible advisory notices offer a single 24 hour snooze.

![Advisory notice with the acknowledge and snooze actions](docs/screenshots/03-advisory-modal-snooze.png)

### Editor Inbox

Editors can review read notices and retained historical notices from their
Inbox.

![Editor Inbox containing read and historical notices](docs/screenshots/04-inbox-editor.png)

### Group targeted Inbox

The Inbox only includes notices that target the current user.

![Marketer Inbox containing a group targeted notice](docs/screenshots/05-inbox-marketer.png)

### Notice sequence after a snooze

The modal advances to the next notice after an eligible advisory notice is
snoozed.

![Next notice shown after an advisory notice was snoozed](docs/screenshots/06-advisory-snoozed-next-notice.png)

### Bypass permission

Users with the `bypass notifications` permission still see notices, but
blocking notices do not gate their control panel routes.

![Control panel dashboard with a notice shown to a user who can bypass route gating](docs/screenshots/07-bypass-dashboard.png)

### Manage screen

The Manage screen shows the clear out preview and the number of eligible
notices.

![Notification Manage screen with the clear out preview](docs/screenshots/08-manage.png)

### Reports index

Administrators can open acknowledgement reports for published notices.

![Notification reports index](docs/screenshots/09-reports-index.png)

### Blocking notice report

Each report distinguishes acknowledged recipients from pending recipients.

![Blocking notice report with acknowledged and pending recipients](docs/screenshots/10-report-blocking-notice.png)

### Former recipients and snoozes

Reports preserve acknowledgements from former recipients and show active snoozes.

![Notice report containing a former recipient acknowledgement and an active snooze](docs/screenshots/11-report-former-recipient-and-snooze.png)

### Notifications collection

The collection listing includes a computed status for each notification.

![Notifications collection listing with the computed status column](docs/screenshots/12-collection-list.png)

### Notice creation

The publish form provides severity, blocking, snooze, and priority settings.

![New notice form with its primary notification settings](docs/screenshots/13-notice-create-form.png)

### Locked notice

The first acknowledgement locks the notice publish form against later changes.

![Read only publish form for a locked notice](docs/screenshots/14-locked-notice.png)

### Completed notice stack

The control panel becomes available after the user works through the complete
notice stack.

![Control panel dashboard after all notices were acknowledged or snoozed](docs/screenshots/15-dashboard-stack-cleared.png)

### Audience targeting

The Audience tab can target all users, roles, groups, individual users, or a
combination of those audiences.

![Notice Audience tab with targeting controls](docs/screenshots/16-notice-audience-tab.png)

### Reminder settings

The Nudges tab configures the reminder threshold and optional repeat cadence.

![Notice Nudges tab with threshold and cadence settings](docs/screenshots/17-notice-nudges-tab.png)

## Commercial license

CP Notifications is proprietary commercial software. A license costs US$100
per production site and must be purchased through the Statamic Marketplace.
One license is required for each production installation.

You may use the complete addon without a license on local development sites.
Before deploying to production, attach the purchased addon license to the Site
in your Statamic account and configure that Site's key in the application:

```dotenv
STATAMIC_LICENSE_KEY=your-site-license-key
```

Statamic validates commercial addon licenses using this shared Site key; CP
Notifications does not use a separate addon license key. See Statamic's
[licensing documentation](https://statamic.dev/getting-started/licensing) for
site setup, production-domain, and offline-environment guidance.

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

### Tests

The suite uses Pest 5, with Playwright-backed browser coverage for the control
panel UI. Install the Chromium runtime once after installing dependencies:

```bash
npm ci
npx playwright install chromium
```

Run the application suite, the real-browser checks, or both in sequence with
Composer:

```bash
composer test
composer test:browser
composer test:all
```

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

Reports and CSV exports provide an operational record of acknowledgements.
They are not presented as a dedicated compliance or attestation product; any
certification, policy mapping, or regulated evidence workflow is outside v1.

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
