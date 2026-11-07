# 00. Project overview

| Version | Status | Last updated |
|---------|--------|--------------|
| 0.4 | Draft | 2026-11-08 |

## Vision

Cut the physical line. A student takes a queue ticket on their phone, sees how many people are ahead, and comes to the office when it is nearly their turn. Staff run their own department's queue from one screen. A public display shows who is being served.

## Problem

The original NU Queue-It had working screens but broke down in the details: staff could call tickets from other departments, some actions had no login check, status values did not match between database and code, and nothing updated live. See [01-old-system-audit.md](01-old-system-audit.md).

## Goals

1. A queue that is correct: no duplicate numbers, one department's staff never touch another department's tickets.
2. A queue that stays current: status changes appear without the user reloading the page (periodic refresh in the MVP; evaluate Laravel Reverb when hosting is known).
3. A codebase that is easy to read, test and extend, with docs that match the code.
4. A project that shows the full SDLC and versioning workflow.

## Users

| Role | Who | Main job |
|------|-----|----------|
| Student | Enrolled student with an admin-provisioned account (guest tickets are deferred past the MVP) | Sign in, get a ticket, track it, cancel it, rate the service |
| Staff | Office employee assigned to one department | Call next, complete, skip |
| Admin | System administrator | Manage departments, services, student and staff accounts; review the activity log |
| Public screen | Display on a TV or monitor, no login | Show who is being served |

## Scope

**In scope (v1.0.0):** accounts and roles, ticket creation with daily-resetting numbers, student tracking, staff console, public display board, admin management, feedback, basic stats, responsive and accessible UI.

**Out of scope:** SMS or email notifications, payments, appointment booking, native mobile apps, multi-campus support, analytics beyond the basic stats.

### MVP (one-department pilot)

The first release for real users is a pilot that starts with **Admission (AMS)**, before expanding to Registrar and Accounting. It includes:

1. Admin setup for one department, its services, and student and staff accounts.
2. Student sign-in through the configured authentication method, ticket creation, cancelling a waiting ticket, and ticket status viewing.
3. Staff access limited to their assigned department, with call next, complete, and skip for a no-show.
4. A public display showing the ticket being served, without exposing student identity.
5. Correct daily ticket numbering, one active ticket per student, and protection against duplicate numbers or a ticket being called twice when requests arrive at the same time.
6. An admin-visible activity log for successful account, catalog, and queue transactions.

Cancel and skip are included because enrollment takes a student through several offices and a student may hold only one active ticket at a time (see [02](02-requirements.md#mvp-scope-pilot)). Student accounts are provisioned by an admin. University sign-in remains an integration option, not a promise that every university protocol can be supported without configuration.

Deferred past the MVP: guest tickets, priority tickets, feedback, statistics, multi-campus support, complex opening-hours rules, and Reverb/WebSocket delivery (simple periodic refresh is acceptable at first). The requirement-level breakdown is in [02](02-requirements.md#mvp-scope-pilot).

## Constraints

- The application is built with Laravel, which handles authentication, authorization and server-side validation.
- Data is stored in a relational SQL database: MySQL by default, or MariaDB if that is what the university's hosting provides. Schema changes are applied with Laravel migrations.
- The university must be able to host it on its own server. GitHub is used for source control only. Hosting details are open questions (see [07](07-deployment.md)).
- Pages are server-rendered with plain CSS and light JavaScript, served as ordinary files. No Node or front-end build step is needed, on the server or for development.
- Time zone for "today" is Asia/Manila.

## Open questions

- Pilot office: Admission (AMS). Registrar and Accounting (the enrollment process) are later expansion candidates. Their initial services come from the old data and are listed in [04](04-database-schema.md); confirm the final service list with Admission before a real pilot. Library and NU Exchange from the old data are not in scope.
- TODO(you): Is this graded, and are there required deliverables or technologies from your instructor?
- Deferred past the MVP, decide after the pilot: do guests (people without an account) get tickets? See `AUTH-06`.
- Deferred past the MVP, decide after the pilot: do you want a priority lane (seniors, PWD, pregnant)? See `STF-07`.
- Decided in [ADR-0001](decisions/ADR-0001-backend-approach.md): Laravel with MySQL (MariaDB if the host provides it), hosted by the university.
- University hosting: nothing beyond Laravel's own requirements is assumed (confirmed, and Laravel already runs locally with XAMPP). TODO(you): details only the university can give: server access, PHP and database versions (MySQL or MariaDB), HTTPS, how releases are deployed, backups, and who maintains the server. The checklist is in [07](07-deployment.md).
- Decided in [ADR-0003](decisions/ADR-0003-account-provisioning-and-identity.md): admins provision student and staff accounts; an identity adapter keeps the app ready for an institution's supported sign-in method. TODO(you): confirm whether "EDI" meant a student-information data exchange; EDI itself is not a standard authentication protocol. Confirm which identity and student-data interfaces are available before selecting an integration.
- Decided for the pilot: use admin-provisioned local accounts and generated one-time temporary passwords until a university identity provider is confirmed. Admins cannot choose or retrieve passwords; local sign-in can be explicitly disabled by deployment configuration.
- Decided in [ADR-0004](decisions/ADR-0004-live-updates.md): keep the current five-second refresh for now and assess Reverb only after the university host's WebSocket, TLS, firewall, and process-supervision support is known.
- Decided in [ADR-0005](decisions/ADR-0005-activity-log.md): retain an admin-visible, append-only record of successful business transactions, with sensitive data excluded. Confirm the retention period with the university.
- Decided: cancel (`TKT-06`) and skip with recall (`STF-04`) are in the MVP, because enrollment sends a student through several offices and a student may hold only one active ticket at a time.
- TODO(you): Database engine. XAMPP bundles MariaDB, while the plan's default is MySQL. Confirm which engine the university's host provides and use that engine everywhere (see [07](07-deployment.md)).
- TODO(you): What may staff see about the student they are serving (name, student ID, course)? Show only what the service needs.

## Risks

| Risk | Impact | Mitigation |
|------|--------|------------|
| Scope grows too large | Never reaches v1.0.0 | Build in small versions, use MoSCoW priorities in 02 |
| University hosting details are unknown | Deployment delays or rework | Ask early (see 07), keep settings in `.env`, rehearse on a staging copy before the pilot |
| University identity integration is unavailable or incompatible | Pilot cannot use institutional credentials | Admin-provision accounts for the pilot, keep identity adapters separate ([ADR-0003](decisions/ADR-0003-account-provisioning-and-identity.md)), and choose credential issuance securely before production |
| A university offers an unfamiliar sign-in or data interface | Integration may take longer than expected | Confirm protocols and documentation with IT; do not promise universal compatibility or treat EDI as authentication |
| WebSockets are blocked or unreliable on the university host | Realtime updates cannot connect | Keep periodic refresh as a fallback and defer Reverb until deployment capabilities are verified |
| Real user data leaks into the repo | Privacy problem | Seed only fake data, keep `.env` and dumps out of git |
