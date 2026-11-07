# 09. Roadmap

| Version | Status | Last updated |
|---------|--------|--------------|
| 0.4 | Draft | 2026-11-08 |

Each row is a release. Sizes: S (a few days), M (about a week), L (more than a week). They are guesses. Revisit them after v0.2.0 to see how fast you really are.

## Releases

The MVP is the one-department pilot (v0.6.0). The releases before it build toward the pilot in this order: Laravel setup and migrations, roles and department authorization, queue rules and concurrency tests, student and staff flows, public display, then the pilot itself. The MVP scope is defined in [02](02-requirements.md#mvp-scope-pilot).

| Version | Theme | Scope (requirement IDs) | Size | Done when |
|---------|-------|-------------------------|------|-----------|
| **0.0.0** | Planning | This documentation pack, ADR-0001 accepted (Laravel, MySQL, university hosting), repo and issue templates set up, hosting questions sent to the university | S | Docs reviewed, hosting questions asked |
| **0.1.0** | Laravel setup and migrations | Laravel project and local environment, database connection, migrations for the MVP schema ([04](04-database-schema.md)), fake-data seeders (departments, services, staff, and students with name, student ID, university email and course), terminal-only first-admin command, `app/Domain/` rules with unit tests, `tokens.css` and base styles, footer version (NFR-10) | M | `php artisan migrate:fresh --seed` works on an empty MySQL (or MariaDB) database, unit tests pass, a demo page shows the base styles |
| **0.2.0** | Roles and department authorization | AUTH-01 to AUTH-05, AUTH-07, ADM-01 to ADM-03, ADM-06 to ADM-07 (admin setup for departments, services, students and staff), policies and department scoping, admin-provisioned student accounts and local demo sign-in ([ADR-0003](decisions/ADR-0003-account-provisioning-and-identity.md)) | L | Each role reaches only its own pages, forbidden requests are refused (feature tests for the access matrix), admin can configure offices and provision users without touching code |
| **0.3.0** | Queue rules and concurrency tests | TKT-02, TKT-03, TKT-04, the call-next rule of STF-02, and the cancel, skip and recall rules (TKT-06, STF-04), as services with feature tests, before the screens | M | T-TKT-02, T-TKT-03, T-TKT-04, T-TKT-06, T-STF-02 and T-STF-04 pass against the same database engine as production |
| **0.4.0** | Student and staff flows | TKT-01, TKT-05 (status and people ahead, periodic refresh), TKT-06, STF-01, STF-02, STF-03, STF-04, shared state components | L | A student can take and cancel a ticket, staff of that department can call, complete or skip it, and the student screen follows along |
| **0.5.0** | Public display | DSP-01, DSP-02 (periodic refresh) | S | A call appears on the display within one refresh interval and shows no student identity (T-DSP-01, T-DSP-02) |
| **0.6.0** | One-department pilot (MVP) | ADM-08 audit log, deployment to the university server (staging rehearsal first if possible), an approved account credential method (university identity integration if available, otherwise a reviewed local-account process), backups, responsive and accessibility pass, NFR-01 to NFR-03 and NFR-13 to NFR-14, manual checklist, short pilot in one department | L | Pilot accounts are securely provisioned, key transactions are auditable, and the pilot runs for an agreed period with no `blocker` issues |
| **0.7.0** | Pilot follow-ups | The other two enrollment offices (of Admission, Registrar and Accounting), then what the pilot shows is needed. Likely candidates: STF-05, STF-06, the estimated wait in TKT-05, TKT-08, DSP-03 | M | Pilot findings closed |
| **0.8.0** | Feedback and stats | FBK-01, ADM-04, ADM-05 | M | Stats match the recorded timestamps |
| **0.9.0** | Expansion | Departments beyond the enrollment offices (still one campus), TKT-07, accessibility and cross-browser pass, beta with real users | M | No open `blocker` or `major` issues |
| **1.0.0** | Release | Every **Must** requirement done, docs approved (1.0), schema documentation matches the migrations, demo data loaded | S | Release checklist in 07 complete |
| **1.1.0+** | Deferred and could haves | AUTH-06, STF-07, DSP-04, optional Reverb delivery if pilot latency and hosting support justify it ([ADR-0004](decisions/ADR-0004-live-updates.md)), multi-campus support only if it becomes a goal | - | - |

## Suggested order of work for Phase 0 (v0.0.0)

1. Read [01](01-old-system-audit.md) and review [ADR-0001](decisions/ADR-0001-backend-approach.md) (accepted: Laravel, MySQL by default, university hosting).
2. Answer the open questions in [00](00-project-overview.md) and send the hosting questions in [07](07-deployment.md) to the university's IT office.
3. Adjust priorities in [02](02-requirements.md). Move anything you will not finish out of **Must**.
4. Finalize [04](04-database-schema.md) and the tokens in [05](05-ui-ux-design.md).
5. Create the GitHub repo, milestones for each version, and the first issues for v0.1.0.
6. Tag `v0.0.0`.

## Review log

Add a short entry after each release.

| Version | Date | Shipped | Slipped | Change for next time |
|---------|------|---------|---------|----------------------|
| 0.0.0 | | | | |
