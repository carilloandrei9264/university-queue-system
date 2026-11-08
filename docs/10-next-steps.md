# Admission pilot: next steps

| Version | Status | Last updated |
|---------|--------|--------------|
| 0.1.0 | Draft | 2026-11-08 |

This list focuses on the work needed to move the current local MVP toward a safe, useful Admission-office pilot. Do not treat the application as production-ready until the deployment-specific checks below have been completed.

### Priority 1 progress (local validation, 2026-11-08)

The available XAMPP environment reports PHP 8.5.12, Laravel 13.40.0, and MariaDB 10.4.32. On an isolated, temporary MariaDB database, all 54 tests passed, including migration/setup tests and the two real multi-process concurrency tests. Seed-free `migrate:fresh` also completed on that disposable schema. This validates the local MariaDB setup only; the university host and staging environment have not been confirmed.

## Priority 1: Validate the target database and queue behavior

- [ ] Confirm the university server's supported PHP, Laravel-compatible extensions, and MySQL/MariaDB versions.
- [ ] Configure a staging environment to match those versions and run migrations and seed-free startup there.
- [x] Run the automated suite and review database-specific behavior against the available local MariaDB 10.4.32 engine. Use [`phpunit.mariadb.xml`](../phpunit.mariadb.xml) for repeatable testing; the default PHPUnit profile still uses SQLite.
- [x] Exercise simultaneous ticket creation and simultaneous “call next” actions against local MariaDB. Automated process-level regression tests confirm unique daily numbers, distinct ticket assignments, and queue order.
- [x] No incompatibilities were found in the local MariaDB run; keep the regression tests and rerun them when the target engine is known.
- [ ] Repeat migrations, the full test suite, and both concurrency tests against the university's actual staging database before the pilot.

The host-specific items remain a release gate: SQL behavior and supported versions can differ between this local MariaDB and the database used by the university.

## Priority 2: Confirm the Admission workflow and operating rules

- [ ] Ask Admission staff to confirm the services offered, operating hours, ticket naming, and who can call or manage each queue.
- [ ] Walk through the student and staff workflows with representative users; record confusing steps and accessibility issues.
- [ ] Confirm how staff will receive accounts and temporary passwords, and how lost access will be handled.
- [ ] Review the [manual test checklist](../tests/manual-checklist.md) against the current screens and queue workflow; update obsolete or missing checks before using it as a release gate.

## Priority 3: Agree on hosting, security, and operations

- [ ] Confirm who owns server deployment, updates, database backups, restore testing, monitoring, and incident response.
- [ ] Require HTTPS and agree on session/cookie settings, administrator access, and least-privilege database credentials.
- [ ] Decide the account and password policy, credential delivery channel, audit-log access and retention period, and applicable privacy/data-retention rules.
- [ ] Verify production configuration disables demo logins and demo seeding, and document the approved local-account sign-in setting.
- [ ] Document backup and restore steps, then rehearse them on staging.

Track open infrastructure questions in [deployment guidance](07-deployment.md) and decisions that affect scope in the relevant [ADRs](decisions/).

## Priority 4: Rehearse and run a limited pilot

- [ ] Deploy a release candidate to staging and perform the full smoke, role/access, responsive layout, and queue-operation checks.
- [ ] Train a small group of Admission staff and provision real pilot accounts through the approved secure process.
- [ ] Agree on the pilot start/end dates, support contact, success measures, and a fallback to the office's existing queue process.
- [ ] Start with one Admission service or a deliberately limited service set; monitor issues and collect staff/student feedback.
- [ ] Review pilot results and audit records with the university before expanding to additional services or offices.

## Deferred until the pilot provides evidence

- University identity integration: choose an API/EDI adapter only after the institution's provider, protocol, and access are confirmed.
- Reverb or another push mechanism: the current five-second refresh is sufficient to validate the pilot; revisit if measured latency or load requires it.
- Additional offices, analytics, feedback workflows, and other roadmap features: prioritize based on pilot feedback and operational needs.
