# CP Notifications — Implementation TODO

## 1. Addon foundation

- [x] Scaffold the Statamic 6 / Laravel 13 addon package as `ghijk/cp-notifications`.
- [x] Register the addon service provider, publishable config, routes, migrations, commands, and CP assets.
- [x] Add `config/cp-notifications.php` with acknowledgement driver, enforcement, retention, and nudge settings.
- [x] Register the Inbox CP navigation item and links to management/reporting surfaces.
- [x] Register Statamic permissions:
  - [x] `view notifications` (available to all users by default).
  - [x] `manage notifications`.
  - [x] `view notification reports`.
  - [x] `bypass notifications` (granted to the creator by default).
  - [x] `purge notifications`.

## 2. Notice content model

- [x] Create a routeless, CP-only `notifications` collection.
- [x] Create its blueprint with title, Bard body, severity, blocking, snoozeable, priority, audience, scheduling, and nudge fields.
- [x] Add validation requiring at least one effective audience target before publishing.
- [x] Force or treat `snoozeable` as false whenever `blocking` is true.
- [x] Ensure notices are global across multisite installations.
- [x] Preserve Statamic's native author, revisions, and draft/published lifecycle.

## 3. Acknowledgement and snooze storage

- [x] Define the immutable `Acknowledgement` data object.
- [x] Define the transient, single-use `Snooze` data object.
- [x] Create `AcknowledgementRepository` with `find`, idempotent `record`, `forNotification`, and `forUser` methods.
- [x] Create the equivalent `SnoozeRepository` contract.
- [x] Implement the Eloquent acknowledgement and snooze repositories.
- [x] Add migrations and indexes for acknowledgement and snooze tables.
- [x] Enforce uniqueness per `(notification, user)` at the database level.
- [x] Implement file repositories using one YAML file per notice/user record under the configured storage path.
- [x] Make file writes atomic and safe under concurrent requests.
- [x] Implement `auto`, `eloquent`, and `file` driver resolution; auto-detect `statamic/eloquent-driver`.
- [x] Bind the selected repositories into Laravel's container.
- [x] Ensure acknowledgements cannot be updated, revoked, or deleted through application APIs.
- [x] Ensure a snooze lasts 24 hours and cannot be used a second time for the same notice/user.

## 4. Audience and active-notice resolution

- [x] Resolve audience membership from `all`, roles, groups, and explicit user IDs.
- [x] De-duplicate users targeted through multiple audience rules.
- [x] Resolve the targeted user set at query/view time.
- [x] Implement active-window checks using published status, `start_date`, optional `end_date`, and the site timezone.
- [x] Filter acknowledged notices from the active stack.
- [ ] Filter notices with a currently active snooze from the active stack.
- [ ] Keep bypass users in audience/inbox results while excluding them only from gating.
- [ ] Order notices by explicit priority override, then severity (`critical`, `warning`, `info`), then oldest `start_date`.
- [ ] Define and test the exact precedence/direction for nullable and tied priorities.

## 5. Locking and supersession

- [ ] Compute `locked` as true once the first acknowledgement exists.
- [ ] Prevent edits to locked notice content and settings on the server.
- [ ] Render locked notices as read-only in the publish form.
- [ ] Provide a clear validation/error message directing admins to create a superseding notice.

## 6. CP API and global overlay

- [ ] Add an authenticated endpoint returning the current user's ordered active stack.
- [ ] Add idempotent acknowledgement/confirm endpoint(s).
- [ ] Require the explicit “I have read and understand” confirmation value server-side.
- [ ] Add the single-use 24-hour snooze endpoint for eligible advisory notices.
- [ ] Build and register a global Vue 3 overlay using Statamic UI components.
- [ ] Render the top notice and enforce top-down clearing.
- [ ] Make blocking notices confirm-only and impossible to dismiss or snooze.
- [ ] Allow eligible advisory notices to be confirmed or snoozed.
- [ ] Refresh/advance the stack after each successful action.
- [ ] Handle expired, already-acknowledged, and concurrently updated notices gracefully.

## 7. Strict blocking enforcement

- [ ] Build the acknowledgement interstitial for unresolved blocking notices.
- [ ] Add CP middleware that guards routes when enforcement is `strict`.
- [ ] Redirect users with an active blocking notice to the interstitial.
- [ ] Avoid redirect loops and permit required assets, acknowledgement routes, and logout.
- [ ] Short-circuit route gating for users with `bypass notifications`.
- [ ] In `modal` mode, disable route guarding while retaining the overlay.

## 8. Inbox and management surfaces

- [ ] Build the user's Inbox showing only notices targeted at that user.
- [ ] Show active notices and retained history, including previously read notices.
- [ ] Apply `retention.inbox_days`; treat `null` as indefinite retention.
- [ ] Ensure expired, unacknowledged advisories stop appearing as active but remain reportable.
- [ ] Configure the collection listing and publish form for notice management.
- [ ] Display appropriate status indicators for draft, scheduled, active, expired, and locked notices.

## 9. Reporting and export

- [ ] Build a per-notice report authorized by `view notification reports`.
- [ ] Expand the notice audience live into the current targeted-user set.
- [ ] Display each targeted user's acknowledgement status and `acknowledged_at` value.
- [ ] Display current/used snooze state.
- [ ] Preserve recorded acknowledgements in reporting even if a user later leaves the targeted role/group.
- [ ] Add an authorized CSV export matching the report grid.
- [ ] Add a “Remind non-ackers” action that dispatches the shared nudge job.

## 10. Nudges

- [ ] Implement nudge eligibility from `enabled`, `threshold_hours`, and optional `cadence_hours`.
- [ ] Track enough delivery state to enforce one-shot and repeating cadence without duplicate sends.
- [ ] Create the nudge mailable using the configured sender or the app mail fallback.
- [ ] Create a shared job/service for scheduled and manual reminders.
- [ ] Add the `cp-notifications:nudge` command.
- [ ] Register/document the command's Laravel scheduler integration.
- [ ] Email only currently targeted users who have not acknowledged the notice.
- [ ] Ensure notices themselves remain available only inside the CP.

## 11. Retention and manual purge

- [ ] Never automatically hard-delete notices or acknowledgement records.
- [ ] Build a confirmed manual clear-out action authorized by `purge notifications`.
- [ ] Restrict purge candidates to appropriate old, expired notices.
- [ ] Prevent system deletion of notices that have acknowledgements.
- [ ] Define whether manual purge archives or removes eligible notices and document the behavior.
- [ ] Log the acting admin, affected notices, timestamp, and result of every purge.

## 12. Automated tests

- [ ] Test addon registration, config defaults, navigation, and permissions.
- [ ] Test blueprint validation, including empty audiences and blocking/snoozeable rules.
- [ ] Contract-test both acknowledgement repository drivers.
- [ ] Contract-test both snooze repository drivers.
- [ ] Test parallel file-driver acknowledgements for race safety and valid YAML output.
- [ ] Test idempotent, once-only acknowledgements under concurrent requests.
- [ ] Test database uniqueness under concurrent acknowledgement attempts.
- [ ] Test single-use snoozing and the exact 24-hour expiry boundary.
- [ ] Test audience expansion for all users, roles, groups, explicit users, and overlaps.
- [ ] Test that role/group removal changes current targeting but preserves an existing acknowledgement.
- [ ] Test scheduling boundaries, open-ended notices, expiration, and future blocking notices.
- [ ] Test priority, severity, and start-date ordering plus ties/null values.
- [ ] Test locked notices reject edits after the first acknowledgement.
- [ ] Test overlay top-down behavior for mixed blocking/advisory stacks.
- [ ] Test strict middleware enforcement and modal-only mode.
- [ ] Test bypass users are not gated but can still see notices in their inbox.
- [ ] Test report authorization, live status, and CSV contents.
- [ ] Test scheduled and manual nudge eligibility, one-shot delivery, cadence, and duplicate prevention.
- [ ] Test retention visibility and purge authorization, confirmation, safety, and audit logging.
- [ ] Test global notice behavior in multisite installations.

## 13. Documentation and release readiness

- [ ] Document installation, config publishing, migrations, CP asset build, and scheduler setup.
- [ ] Document file versus Eloquent drivers and `auto` selection behavior.
- [ ] Document `strict` versus `modal` enforcement and bypass permission implications.
- [ ] Document notice creation, targeting, scheduling, reporting, export, nudges, and purge workflows.
- [ ] Warn that blocking notices with an `end_date` are unusual and can expire without acknowledgement.
- [ ] Document locked notices and the superseding-notice correction workflow.
- [ ] Verify compatibility with Statamic 6, Laravel 13, Vue 3, the Statamic UI kit, flat-file installs, and `statamic/eloquent-driver` installs.

## Deferred (not v1)

- [ ] Do not add recurring notices; use start/end windows in v1.
- [ ] Do not add per-user timezone scheduling; use the site timezone in v1.
- [ ] Defer dedicated compliance/attestation product framing beyond the existing report and CSV record.
