# Admission pilot: next steps

| Version | Status | Last updated |
|---------|--------|--------------|
| 0.1.0 | Draft | 2026-11-10 |

This list focuses on the work needed to move the current local MVP toward a safe, useful Admission-office pilot. Do not treat the application as production-ready until the deployment-specific checks below have been completed.

The 2026-11-10 project review identified the follow-up work below. These are planned improvements and verification tasks, not evidence that the production environment has been configured or that the checks have passed.

### Validation progress (local environments, 2026-11-08)

The available XAMPP environment reports PHP 8.5.12, Laravel 13.40.0, and MariaDB 10.4.32. On the isolated MariaDB test database, all 59 tests passed, including migration/setup and both real multi-process concurrency tests. The SQLite suite passed 57 tests and skipped the two MariaDB-only concurrency tests. Seed-free `migrate:fresh` also completed on the disposable MariaDB schema. This validates local environments only; the university host and staging environment have not been confirmed.

The confirmed multi-window workflow is implemented: admins configure 1–20 windows per office and assign staff, each window can serve one ticket at a time, the display and student ticket show the serving window, and staff can take a break after completing or skipping the active ticket. Shrinking a window count or changing a staff assignment is blocked while a ticket is serving or remains recallable.

The admin student directory now supports a create-only CSV roster workflow: download a template, upload and preview up to 500 UTF-8 rows, review duplicates and validation errors, confirm creation of ready rows, and export only roster fields. Imports do not overwrite existing records or reveal passwords. Each imported account's temporary password must be issued individually and delivered securely. Use only a university-authorized roster source and the approved credential-delivery process for real accounts.

The supplied staging-server requirements are adopted as the planning baseline: a small VPS/cloud staging host, separate least-privilege database, production-like software versions, restricted access, sandbox integrations, disabled/redirected email, and synthetic or masked data only. The document's PostgreSQL option does not apply to the current MySQL/MariaDB-only application. NU has agreed to the privacy proposal for the pilot, provided the append-only activity log remains available to authorized administrators and records the actor (or System), action, affected record, and timestamp without secrets or unnecessary personal data. Log retention and reviewer access still need an operational decision.

## Priority 1: Validate the target database and queue behavior

- [ ] Confirm the university server's supported PHP, Laravel-compatible extensions, and MySQL/MariaDB versions.
- [ ] Configure a staging environment using the adopted [staging baseline](07-deployment.md), confirm the university's supported PHP/extensions and MySQL/MariaDB version, and run migrations and seed-free startup there.
- [ ] Once the production database engine and version are confirmed, rerun migrations, the full suite, and multi-process queue concurrency tests against a separate, disposable staging/test database using that engine.
- [x] Run the automated suite and review database-specific behavior against the available local MariaDB 10.4.32 engine. Use [`phpunit.mariadb.xml`](../phpunit.mariadb.xml) for repeatable testing; the default PHPUnit profile still uses SQLite.
- [x] Exercise simultaneous ticket creation and simultaneous “call next” actions against local MariaDB. Automated process-level regression tests confirm unique daily numbers, distinct ticket assignments across windows, and oldest-first queue assignment.
- [x] No incompatibilities were found in the local MariaDB run; keep the regression tests and rerun them when the target engine is known.
- [ ] Add direct unit tests for ticket status transitions, ticket-code formatting, and Manila queue-date boundaries, alongside the existing feature coverage.
- [ ] Add pull-request CI to install from the lockfile and run the test suite and Pint; resolve any existing style-check failures before treating the CI check as a release signal.
- [ ] Run Composer's locked dependency audit when package repository access is available and resolve or document any advisories.

The host-specific items remain a release gate: SQL behavior and supported versions can differ between this local MariaDB and the database used by the university.

## Priority 2: Confirm the Admission workflow and operating rules

- [ ] Ask Admission staff to confirm the services offered, operating hours, ticket naming, and who can call or manage each queue.
- [ ] Walk through the student and staff workflows with representative users; record confusing steps and accessibility issues.
- [ ] Review the current interface at phone (including 360px), tablet, and desktop sizes, and validate ticket visibility, contrast, refresh/recovery, and viewing distance on the actual public display device.
- [ ] Reduce repetitive screen-reader announcements during routine five-second polling; announce meaningful queue changes and status transitions instead.
- [ ] Evaluate replacing the public board's five-second full-page refresh with an accessible targeted update; test on the target display before changing the current behavior.
- [ ] Confirm how staff will receive accounts and temporary passwords, and how lost access will be handled.
- [x] Update the [manual test checklist](../tests/manual-checklist.md) for the current multi-window, break, and completion flows.

## Priority 3: Confirm hosting, security, and operations

- [ ] Confirm who owns server deployment, updates, database backups, restore testing, monitoring, and incident response.
- [ ] Require HTTPS and least-privilege database credentials. Set `SESSION_SECURE_COOKIE=true` explicitly in every production environment and verify the resulting session cookie has `Secure`, `HttpOnly`, and the intended `SameSite` value.
- [ ] Verify trusted-proxy HTTPS detection, production `APP_DEBUG=false`, demo-login/seeding restrictions, administrator access, and local-password sign-in configuration before enabling real accounts.
- [ ] Complete the agreed privacy proposal's operational details: authorize activity-log reviewers and set its retention/archival period and the related personal-data retention rules.
- [ ] Agree on temporary-password delivery and roster-file handling using university-approved secure channels before importing real student data.
- [ ] Document backup and restore steps, then rehearse them on staging.
- [ ] Review response security headers for the deployment (including `X-Content-Type-Options`, referrer policy, and a compatible Content Security Policy); enable HSTS only after HTTPS is confirmed and the host/domain scope is understood.

Track open infrastructure questions in [deployment guidance](07-deployment.md) and decisions that affect scope in the relevant [ADRs](decisions/).

## Priority 4: Rehearse and run a limited pilot

- [ ] Deploy a release candidate to staging and perform the full smoke, role/access, responsive layout, and queue-operation checks.
- [ ] Verify the production session-cookie configuration in a production-like HTTPS/proxy setup before enabling local-password sign-in; keep the test as a deployment release check.
- [ ] Train a small group of Admission staff and provision real pilot accounts through the approved secure process.
- [ ] Agree on the pilot start/end dates, support contact, success measures, and a fallback to the office's existing queue process.
- [ ] Start with one Admission service or a deliberately limited service set; monitor issues and collect staff/student feedback.
- [ ] Review pilot results and audit records with the university before expanding to additional services or offices.

## Deferred until the pilot provides evidence

- University identity integration: choose an API/EDI adapter only after the institution's provider, protocol, and access are confirmed.
- Reverb or another push mechanism: the current five-second refresh is sufficient to validate the pilot; revisit if measured latency or load requires it.
- Additional offices, analytics, feedback workflows, and other roadmap features: prioritize based on pilot feedback and operational needs.
