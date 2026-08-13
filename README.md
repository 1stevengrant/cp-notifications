# CP Notifications

CP Notifications is a Statamic 6 addon for publishing targeted notices inside
the control panel and tracking user acknowledgements. Important messages reach
editors where they work, without relying on email or chat.

Notices can be informational, advisory, or blocking. Target all users or a
specific combination of roles, groups, and individual accounts; schedule when
a notice is active; remind people who have not responded; and review or export
the acknowledgement record.

## Features

- Target notices to all users, roles, groups, or individual accounts.
- Schedule publication windows and order notices by priority and severity.
- Require explicit acknowledgement before targeted users continue in the
  control panel.
- Allow eligible advisory notices to be snoozed for 24 hours.
- Send scheduled or manual reminders to users who have not acknowledged.
- Review acknowledgement reports and export them to CSV.
- Store state in flat files or a database to match the Statamic installation.

## Common use cases

### Require acknowledgement of a policy update

Publish a blocking notice when editors must accept a new content policy,
security procedure, or internal guideline before continuing to use the control
panel. Each confirmation is recorded with a timestamp for later review.

### Warn editors about planned maintenance

Schedule an advisory notice ahead of a deployment, migration, or maintenance
window. Target only the affected roles or groups and let users snooze the
message when immediate acknowledgement is unnecessary.

### Coordinate a content freeze

Show a critical blocking notice before a major launch or migration, instructing
editors not to make changes. Keep it active until each targeted user confirms
they understand the restriction.

### Announce changes to an editorial workflow

Explain a new publishing process, revised approval requirements, renamed
fields, or changes to entry structure. Send reminders to targeted editors who
have not yet acknowledged the update.

### Communicate incidents and temporary workarounds

Notify control-panel users about a broken integration, unavailable feature, or
known publishing issue. Include workaround instructions in the notice and set
an end date for temporary messages.

### Onboard a team or user group

Deliver role-specific instructions when a team gains access to the site or a
new feature. Audiences are evaluated live, so users later added to a targeted
role or group will see the active notice automatically.

## Requirements

- Statamic 6
- PHP 8.3 or newer
- Laravel 12 or 13

## License

CP Notifications is proprietary commercial software. A license costs US$100 per
production site and must be purchased through the Statamic Marketplace. The
complete addon may be used without a license in local development.

## Documentation

See [DOCUMENTATION.md](DOCUMENTATION.md) for installation, configuration,
notice workflows, persistence drivers, reminders, retention, and testing. See
[RELEASE_NOTES.md](RELEASE_NOTES.md) for changes and compatibility by version.
