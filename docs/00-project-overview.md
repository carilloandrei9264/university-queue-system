# 00. Project overview

| Version | Status | Last updated |
|---------|--------|--------------|
| 0.3 | Draft | 2026-11-07 |

## Vision

Cut the physical line. A student takes a queue ticket on their phone, sees how many people are ahead, and comes to the office when it is nearly their turn. Staff run their own department's queue from one screen. A public display shows who is being served.

## Problem

The original NU Queue-It had working screens but broke down in the details: staff could call tickets from other departments, some actions had no login check, status values did not match between database and code, and nothing updated live. See [01-old-system-audit.md](01-old-system-audit.md).

## Goals

1. A queue that is correct: no duplicate numbers, one department's staff never touch another department's tickets.
2. A queue that stays current: status changes appear without the user reloading the page (periodic refresh in the MVP, real-time later only if needed).
3. A codebase that is easy to read, test and extend, with docs that match the code.
4. A project that shows the full SDLC and versioning workflow.

## Users

| Role | Who | Main job |
|------|-----|----------|
| Student | Enrolled student who signs in with the university account (guest tickets are deferred past the MVP) | Get a ticket, track it, cancel it, rate the service |
| Staff | Office employee assigned to one department | Call next, complete, skip |
| Admin | System administrator | Manage departments, services and staff, read feedback and stats |
| Public screen | Display on a TV or monitor, no login | Show who is being served |

## Scope

**In scope (v1.0.0):** accounts and roles, ticket creation with daily-resetting numbers, student tracking, staff console, public display board, admin management, feedback, basic stats, responsive and accessible UI.

**Out of scope:** SMS or email notifications, payments, appointment booking, native mobile apps, multi-campus support, analytics beyond the basic stats.

### MVP (one-department pilot)

The first release for real users is a pilot that starts with **one department**, before expanding to others. The departments in scope for now are the Admission, Registrar and Accounting offices, which together cover the enrollment process. It includes:

1. Admin setup for one department, its services and staff.
2. Student sign-in (university account, with seeded student records until it is connected), ticket creation, cancelling a waiting ticket, and ticket status viewing.
3. Staff access limited to their assigned department, with call next, complete, and skip for a no-show.
4. A public display showing the ticket being served, without exposing student identity.
5. Correct daily ticket numbering, one active ticket per student, and protection against duplicate numbers or a ticket being called twice when requests arrive at the same time.

Cancel and skip are included because enrollment takes a student through several offices and a student may hold only one active ticket at a time (see [02](02-requirements.md#mvp-scope-pilot)).

Deferred past the MVP: guest tickets, priority tickets, feedback, statistics, multi-campus support, complex opening-hours rules, and real-time infrastructure (simple periodic refresh is acceptable at first). The requirement-level breakdown is in [02](02-requirements.md#mvp-scope-pilot).

## Constraints

- The application is built with Laravel, which handles authentication, authorization and server-side validation.
- Data is stored in a relational SQL database: MySQL by default, or MariaDB if that is what the university's hosting provides. Schema changes are applied with Laravel migrations.
- The university must be able to host it on its own server. GitHub is used for source control only. Hosting details are open questions (see [07](07-deployment.md)).
- Pages are server-rendered with plain CSS and light JavaScript, served as ordinary files. No Node or front-end build step is needed, on the server or for development.
- Time zone for "today" is Asia/Manila.

## Open questions

- Departments in scope for now: Admission, Registrar and Accounting (the enrollment process). Their services come from the old data and are listed in [04](04-database-schema.md). Library and NU Exchange from the old data are not in scope for now. TODO(you): which of the three starts the pilot, and are the services right? If you want all three in the pilot from the start, say so and the MVP wording and roadmap change.
- TODO(you): Is this graded, and are there required deliverables or technologies from your instructor?
- Deferred past the MVP, decide after the pilot: do guests (people without an account) get tickets? See `AUTH-06`.
- Deferred past the MVP, decide after the pilot: do you want a priority lane (seniors, PWD, pregnant)? See `STF-07`.
- Decided in [ADR-0001](decisions/ADR-0001-backend-approach.md): Laravel with MySQL (MariaDB if the host provides it), hosted by the university.
- University hosting: nothing beyond Laravel's own requirements is assumed (confirmed, and Laravel already runs locally with XAMPP). TODO(you): details only the university can give: server access, PHP and database versions (MySQL or MariaDB), HTTPS, how releases are deployed, backups, and who maintains the server. The checklist is in [07](07-deployment.md).
- Decided in [ADR-0002](decisions/ADR-0002-university-sign-in.md): students sign in with the university account, connected on the university server later. Seeded fake records (name, student ID, university email, course) are used until then. TODO(you): which sign-in method does the university provide, and how do pilot users sign in before it is connected?
- Decided: cancel (`TKT-06`) and skip with recall (`STF-04`) are in the MVP, because enrollment sends a student through several offices and a student may hold only one active ticket at a time.
- TODO(you): Database engine. XAMPP bundles MariaDB, while the plan's default is MySQL. Confirm which engine the university's host provides and use that engine everywhere (see [07](07-deployment.md)).
- TODO(you): What may staff see about the student they are serving (name, student ID, course)? Show only what the service needs.

## Risks

| Risk | Impact | Mitigation |
|------|--------|------------|
| Scope grows too large | Never reaches v1.0.0 | Build in small versions, use MoSCoW priorities in 02 |
| University hosting details are unknown | Deployment delays or rework | Ask early (see 07), keep settings in `.env`, rehearse on a staging copy before the pilot |
| University sign-in is connected late | Pilot cannot use real accounts | Keep sign-in isolated ([ADR-0002](decisions/ADR-0002-university-sign-in.md)), agree the timeline with IT early, use seeded accounts on staging meanwhile |
| Real user data leaks into the repo | Privacy problem | Seed only fake data, keep `.env` and dumps out of git |
