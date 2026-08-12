# PRD — Statamic CP Notifications addon

**Package (placeholder):** `ghijk/cp-notifications`
**Target:** Statamic 6 / Laravel 13. CP front-end is **Vue 3 + Statamic UI kit** (not React — that stack does not apply inside the control panel).

## 1. Purpose

Let an admin publish a control-panel notice to other CMS users and get back a queryable record of who confirmed they read it. Two jobs in one: an in-CP **notice**, and a **read-attestation log**. The standout capability is gating the whole CP behind an unacknowledged blocking notice — the technical lock that "nobody touch the admin area" instructions normally can't enforce.

## 2. Storage architecture (the crux)

Ride Statamic's own storage rather than reinventing it.

- **Notices are entries** in a routeless, CP-only collection (`notifications`). This inherits, for free: flat-file **or** DB storage (a site running `statamic/eloquent-driver` gets DB entries automatically), Bard, revisions, and the draft→published lifecycle. No custom storage for the notice itself.
- **Acknowledgements and snoozes are records, not content.** They go through a **repository contract** with two drivers, config-selectable, defaulting to auto-detect (use eloquent if `eloquent-driver` is present, else file):
  - `eloquent` → DB tables.
  - `file` → **one YAML file per record** (`acks/{notification}/{user}.yaml`). One file per (notice, user) means no two processes ever write the same file — this is what makes the flat-file path race-safe.

```
Contract:  AcknowledgementRepository
  find(notification, user): ?Acknowledgement
  record(notification, user): Acknowledgement      // idempotent; once-only
  forNotification(notification): Collection<Ack>
  forUser(user): Collection<Ack>

Drivers:   EloquentAcknowledgementRepository | FileAcknowledgementRepository
(Same shape for SnoozeRepository.)
```

## 3. Data model

### 3.1 Notice — collection blueprint (`notifications`)

```json
{
  "title": "string",
  "body": "bard",
  "severity": "info | warning | critical",
  "blocking": "bool — gates the CP; independent of severity",
  "snoozeable": "bool — ignored/forced false when blocking is true",
  "priority": "int, nullable — overrides default ordering",
  "audience": {
    "all": "bool",
    "roles": ["role_handle"],
    "groups": ["group_handle"],
    "users": ["user_id"]
  },
  "start_date": "datetime",
  "end_date": "datetime, nullable",
  "nudge": {
    "enabled": "bool",
    "threshold_hours": "int — hours after start_date with no ack before nudging",
    "cadence_hours": "int, nullable — repeat interval; null = one-shot"
  }
}
```

Native/derived, not blueprint-authored:
- `author` → the **creator** (used for default bypass grant).
- publish status → **draft / published** (native entry state).
- `locked` → computed **true once the first ack exists**; content read-only thereafter.

### 3.2 Acknowledgement (immutable)

```
id · notification (id/handle) · user_id · acknowledged_at
```

### 3.3 Snooze (transient, single-use)

```
notification · user_id · snoozed_until (= now + 24h)
```
Existence of a row = the user has spent their one snooze. No second snooze.

## 4. Config (`config/cp-notifications.php`)

```php
return [
    'acknowledgements' => [
        'driver' => env('CP_NOTIFICATIONS_DRIVER', 'auto'), // auto | eloquent | file
        'file_path' => storage_path('statamic/cp-notifications'),
    ],
    'enforcement' => 'strict', // 'modal' = overlay only; 'strict' = also route-guard blocking notices
    'retention' => [
        'inbox_days' => null,   // null = keep indefinitely
    ],
    'nudge' => [
        'from_address' => null, // falls back to app mail config
    ],
];
```

## 5. Notice lifecycle & ordering

- **Types** are driven by the independent `blocking` flag:
  - **Blocking** → gates CP, cannot be snoozed or dismissed, confirm-only.
  - **Advisory** → non-blocking modal; snooze once for 24h, or dismiss; respects `start_date`/`end_date`.
- **Active** = published AND `now` within `[start_date, end_date]` (open-ended if `end_date` null) AND targeted at the user AND not yet acked AND (no active snooze).
- **Ordering in the stack:** `severity` (critical → warning → info), then `start_date` (oldest first). `priority` overrides.
- **Clearing:** **top-down.** A blocking notice at the top must be confirmed before those beneath are reachable; advisories can be snoozed behind it.
- **Expiry:** past `end_date`, an unacked advisory simply stops showing and is logged as unacknowledged. A blocking notice with an `end_date` should be rare — call it out in docs.

## 6. Gating & the modal stack

- CP JS injects a global Vue overlay that fetches the active stack for the current user and renders Statamic-UI modals.
- **Enforcement `strict`:** a CP middleware also redirects to an ack interstitial while any blocking notice is unresolved, so it can't be bypassed via devtools. Use this for upgrade-morning criticality.
- **Enforcement `modal`:** overlay only — lighter, fine for advisory-heavy sites.
- **Bypass:** users with the `bypass notifications` permission skip the gate entirely (creator holds it by default).

## 7. Acknowledgement

Explicit confirm — a checkbox ("I have read and understand") + confirm button. `record()` is idempotent and once-only; **no re-prompt after ack.** Acks are never revocable or deletable.

## 8. Nudges

- Per-notice: `enabled`, `threshold_hours`, optional `cadence_hours`.
- Scheduled command `cp-notifications:nudge` (wire into the Laravel scheduler) emails targeted users who haven't acked past the threshold, repeating on cadence.
- Manual **"Remind non-ackers"** button in the report view dispatches the same job on demand.
- Email is the only external channel; the notices themselves remain strictly in-CP.

## 9. Reporting

- Live read/unread view per notice: targeted users, ack status, `acknowledged_at`, snooze state.
- Resolve the **targeted** user set from `audience` at view time (roles/groups expand to members).
- CSV export of the grid.

## 10. Retention & deletion

- **Nothing is ever hard-deleted automatically**, and notices with acks are never system-deleted.
- `retention.inbox_days` governs how long expired notices remain visible in a user's inbox (default: indefinite).
- **Manual clear-out** is the only deletion path: a permissioned, confirmed admin action (`purge notifications`) that archives/removes old expired notices. Deliberate, human, logged.

## 11. Permissions (Statamic)

- `view notifications` — see own inbox (default: all users).
- `manage notifications` — create / edit / publish.
- `view notification reports` — read/unread view + export.
- `bypass notifications` — skip the gate (creator default).
- `purge notifications` — manual clear-out.

## 12. CP surfaces

- **Inbox** — dedicated CP nav item; shows only notices **targeted at that user** (active + history, subject to retention). Re-read anything past.
- **Manage** — the `notifications` collection listing + publish form (Bard body, targeting, schedule, flags, nudge config).
- **Report** — per-notice read/unread grid with export + remind button.
- **Global overlay** — the modal stack, on every CP page.

## 13. Build task list

- [ ] Scaffold addon; register service provider, config, CP nav, permissions.
- [ ] Create routeless CP-only `notifications` collection + blueprint.
- [ ] `AcknowledgementRepository` + `SnoozeRepository` contracts; `eloquent` and `file` drivers; `auto` resolution.
- [ ] Migrations for the eloquent driver (acks, snoozes).
- [ ] Active-notice resolver (targeting expansion, window, ack/snooze filtering, ordering).
- [ ] `locked` enforcement — set on first ack, make content read-only in the publish form.
- [ ] CP overlay Vue component + stack endpoint; top-down clearing; confirm + snooze actions.
- [ ] `strict` middleware route-guard for blocking notices; `bypass notifications` short-circuit.
- [ ] Report view + CSV export.
- [ ] `cp-notifications:nudge` command + manual remind action + mailable.
- [ ] Retention config + manual purge action behind `purge notifications`.
- [ ] Tests: race-safety of file driver (parallel acks), targeting expansion, ordering, blocking enforcement, once-only snooze, idempotent ack.

## 14. Edge cases to cover

- User targeted via a role, then removed from it mid-window → drops out of "targeted" set; keep any ack already recorded.
- Notice edited attempt after lock → blocked; correction path is **supersede** (new notice, fresh ack round).
- Bypass user should still *see* notices in their inbox; they're just not gated.
- Multisite: notices are global (shown across all sites' CP); roles/groups are already global in Statamic.
- Empty audience (nobody targeted) → validation error on publish.
- Blocking notice whose `start_date` is future → not active until then; don't gate early.

## 15. Deferred / speculative (flagged, not in v1)

- Attestation-as-compliance framing (exportable "user X accepted policy Y on Z") — a plausible upsell, but v1 already produces the record.
- Recurring notices — out; use start/end windows.
- Per-user timezone display of schedule — site tz for v1.
