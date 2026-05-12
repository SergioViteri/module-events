# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this module is

`Zaca_Events` — Magento 2 module (vendor `zaca`, namespace `Zaca\Events`, identifier `Zaca_Events`) for managing in-store board-game events at Zacatrus. Composer name `zaca/events`. Module is **always installed via Composer path repository**, never copied to `app/code/`.

Companion to other Zaca modules in `/home/sergio/dev/magento/` (`bot`, `box`, `card`, `extra`, `sort`…). `agents.md` documents the workspace-wide conventions used for new CRUDs — read it before creating new admin entities.

## Deploying changes to the local container

The Magento install runs in the `magento-web24` container; module is installed at `/var/www/zacatruses/vendor/zaca/events/`. Do **not** run `composer update` for path-repo updates — the user manages Composer manually. To reflect a local change:

```bash
docker cp <host-file> magento-web24:/var/www/zacatruses/vendor/zaca/events/<same-relative-path>
docker exec magento-web24 chown www-data:www-data /var/www/zacatruses/vendor/zaca/events/<same-relative-path>
```

Then refresh per the kind of change (see global instructions for the full matrix; common ones):
- PHP class edits: `docker exec magento-web24 bin/magento cache:flush`
- `di.xml` / constructor signatures: `bin/magento setup:di:compile && bin/magento cache:flush`
- `db_schema.xml`, `module.xml` version, new `Setup/Patch/*`: `bin/magento setup:upgrade && bin/magento cache:flush`
- Layout XML / `.phtml`: `cache:flush`
- JS/CSS / `requirejs-config.js`: also `bin/magento setup:static-content:deploy -f` (slow; confirm with user first)

`setup:di:compile` and `setup:upgrade` take 1–3 min — don't run "just in case".

## Console command

```bash
docker exec magento-web24 bin/magento events:send-reminders
```

Same code path as the daily 9 AM cron (`zaca_events_send_reminders`, see `etc/crontab.xml` → `Cron/SendReminders.php`). Useful for manually replaying reminder dispatch.

## Domain model — the big picture

The schema lives in `etc/db_schema.xml`. Core entities and their relationships:

- **`zaca_events_meet`** — the central entity. A "meet" is a scheduled event instance at a `Location`, optionally typed (`meet_type`: `casual` / `league` / `special`) and themed. Carries `max_slots`, `max_attendees_per_registration`, `recurrence_type` (`none` / `quincenal` / `semanal`), and CSV `reminder_days` (e.g., `"7,3,1"`).
- **`zaca_events_registration`** — a customer's signup for a meet. `status` is `confirmed` or `waitlist`. `attendee_count` lets one customer bring N people on a single registration (bounded by the meet's `max_attendees_per_registration`). Has a unique `(meet_id, customer_id)` constraint and a unique `unsubscribe_code` used in email unsubscribe links.
- **`zaca_events_attendance`** — one row per (registration, date). Unique on `(registration_id, attendance_date)` — a registration can only "attend" once per day. Created by QR-scan check-in flow.
- **`zaca_events_location`** — store/venue. Has a `code` used to authenticate QR-scan attendance requests (case-sensitive).
- **`zaca_events_event_type`**, **`zaca_events_theme`** — taxonomy.
- **`zaca_events_reminder_sent`** — idempotency log for the reminder cron, unique on `(registration_id, reminder_days)`.
- **`zacatrus_events_store`** / **`zacatrus_events_league`** — legacy "Zacatrus League" tables with the older `zacatrus_events_*` prefix; meets join leagues via `zaca_events_meet_league`.

**Naming caveat (matters when grepping):** two table prefixes coexist — `zacatrus_events_*` (legacy: store, league) and `zaca_events_*` (everything newer). Same with admin routes: `etc/adminhtml/routes.xml` registers both `zaca_events` (used by all current admin controllers) and `zacatrus_events` (legacy, still referenced by `view/adminhtml/layout/zacatrus_events_registration_*.xml` and the UI listing `view/adminhtml/ui_component/zacatrus_events_registration_listing.xml`). The `event_id` → `meet_id` rename is handled by `Setup/Patch/Schema/RenameRegistrationEventIdToMeetId.php` — that patch is defensive and tolerates partial prior state.

## Recurrence

Two layers, easy to confuse:

1. **`Model/Meet/RecurrenceGenerator.php`** — when a `quincenal` meet is saved, eagerly creates child `Meet` rows every 15 days for 6 months ahead. Child meets are written with `recurrence_type = none` so they don't recurse. Existence is checked by `(location_id, start_date, name)` before insert (idempotent).
2. **`Service/AttendanceValidator::isDateValidForMeet`** — computes valid recurrence dates on the fly when validating a QR-scan check-in. Supports both `quincenal` (15-day) and `semanal` (7-day) cadence, but `RecurrenceGenerator` only generates `quincenal` — if you add a new recurrence type, both files need to change.

## Frontend routing & the configurable path

Frontend front-name is `events` (`etc/frontend/routes.xml`), but the public URL is configurable via admin config `zaca_events/general/route_path` (`etc/adminhtml/system.xml`, default `events`). `Plugin/Router/Base.php` wraps `Magento\Framework\App\Router\Base::match`:

- When the configured path is `events`, it does nothing — standard routing applies.
- When the configured path is something else, it rewrites incoming requests on the configured path to the `events` front-name internally, **and** intercepts the literal `/events/...` path to dispatch to `Controller/Index/Redirect.php` (301 to the configured path).

So: don't hardcode `/events/` in templates or controllers — go through `Helper\Data::getRoutePath()`.

The other helper, `getSlotsDisplayMode()`, drives how the public list renders available capacity (`available_total` is the default).

## Admin UI — mixed legacy + modern, on purpose

This module uses **two** admin UI styles side-by-side. Match the style of the entity you're editing:

- **Legacy Block-based grids/forms** (`Block/Adminhtml/<Entity>/...` + matching `Controller/Adminhtml/<Entity>/*` actions: `Index`, `Edit`, `Save`, `Delete`, `Grid`, `NewAction`, `MassDelete`). Used by `EventType`, `Location`, `Meet`, `Participant`, `Theme`. Layouts named `zaca_events_<entity>_*.xml`.
- **Modern UI Components** (`Ui/Component/...` + `ui_component/*.xml`). Used only by the `Registration` listing under the legacy `zacatrus_events` route — see `view/adminhtml/ui_component/zacatrus_events_registration_listing.xml` and the grid virtual types in `etc/di.xml`.

The `participant` admin section is the UI for `zaca_events_registration` rows (controllers are under `Controller/Adminhtml/Participant/`). Mass actions include attendance toggling (`MassConfirmAttendance`, `MassRemoveAttendance`) and CSV export (`ExportCsv`).

ACL: top-level resource is `Zaca_Events::events`, with children `participants`, `locations`, `event_types`, `themes`, `meets` (see `etc/acl.xml`, `etc/adminhtml/menu.xml`).

## Attendance / QR-code flow

`Controller/Index/QrCode.php` renders a QR per registration (uses `endroid/qr-code`, a hard composer dep). The QR encodes a URL to `Controller/Index/Attendance.php`, which:

1. Validates the URL's location code via `Service\AttendanceValidator::validateLocationCode` (case-sensitive against `zaca_events_location.code`).
2. Validates today's date is a valid occurrence of the meet via `isDateValidForMeet` (accounts for recurrence).
3. Checks no `zaca_events_attendance` row already exists for `(registration_id, today)`.
4. Records attendance and increments `registration.attendance_count`.

`Block/Attendance/Check.php` renders the staff-facing check-in screen.

## Email templates

Defined in `etc/email_templates.xml`, HTML lives under `view/frontend/email/`:
- `registration_confirmed`, `registration_waitlist`, `confirmed_to_waitlist`, `waitlist_promoted`, `unregistration`, `reminder`.

`Helper/Email.php` is the single entry point — repositories and the cron call it, not template sending directly.

## REST API

`etc/webapi.xml` exposes `GET /V1/zacatrus-events/my-registrations` (self-scoped) and an admin-scoped variant under `Zaca_Events::registrations` ACL. Both delegate to `RegistrationRepositoryInterface::getList`. There's currently no public CRUD API for meets/locations from REST.

## Conventions worth knowing

- PHP 8.0–8.3 (`composer.json`).
- Translations: only `i18n/es_ES.csv` ships — there's no `en_US.csv` (this is a Spanish-language module).
- All preferences are declared in `etc/di.xml` (`MeetRepository`, `RegistrationRepository`, `EventTypeRepository`, `ThemeRepository`, plus their `Data\*Interface` → model class bindings).
- Reference modules for similar patterns: `bot` (canonical CRUD), `box`, `card` — they live as siblings under `/home/sergio/dev/magento/`.
