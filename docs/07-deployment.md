# 07. Deployment

| Version | Status | Last updated |
|---------|--------|--------------|
| 0.4 | Draft | 2026-11-10 |

## Environments

| Environment | Where | Data | Purpose |
|-------------|-------|------|---------|
| Local | Your own machine, running Laravel with XAMPP (Apache, PHP and a database) or Laravel's local server (`php artisan serve`) | A local database with fake seed data | Daily work |
| Staging (recommended) | A copy of the production setup on the university server, if the university allows it | A separate database with fake data | Rehearse releases and migrations before the pilot |
| Production | The university-hosted server | MySQL by default, or MariaDB if that is what the host provides | What people use |

Use the same database engine, with a similar version, in every environment, because the queue's locking behaviour depends on it. XAMPP's bundled database is MariaDB. If you develop on XAMPP, either install MySQL for local work or choose MariaDB as the deployment engine, depending on what the university's host provides, so every environment uses the same engine. TODO(you): confirm which engine the university's host provides. Keep the test database separate from the production one, so tests never touch real data.

## Staging server baseline

The supplied **Staging Server – Minimum Requirements** is adopted as the planning baseline for a functional-test staging environment, not as a production capacity guarantee:

- VPS or cloud instance preferred; avoid basic shared hosting where possible.
- At least 2 vCPU, 4 GB RAM, and 40–80 GB SSD/NVMe storage; 8 GB RAM is preferred when the application and database share the host or testing is heavier.
- Ubuntu 22.04/24.04 LTS is acceptable, or use the production operating system for closer parity. Match production's PHP/Laravel-compatible extensions, web server, and database engine/version.
- Reserve roughly 1–2 GB RAM for the database when it shares the staging host; size storage for a realistic synthetic dataset, logs, and backups with 30–50% headroom.
- Use a separate staging database and least-privilege database account. Never point staging at production.
- Populate staging only with synthetic or properly anonymized/masked data; never copy live student records into staging.
- Use staging-specific environment values and credentials, sandbox service keys, and disabled or redirected email delivery. Restrict staging access with an appropriate IP allowlist, VPN, or equivalent access control.

The supplied document also lists PostgreSQL as a possible database. This application currently supports MySQL/MariaDB only (see [requirements](02-requirements.md)); PostgreSQL is not an approved deployment choice without a separate compatibility and test effort. Production-engine parity takes precedence over the generic sizing and OS recommendations. The network-speed figure in the supplied document is informational, not an application requirement.

## Configuration

Settings live in `.env` on each machine (never committed) and in Laravel's `config/` files: application URL, database connection, `APP_ENV`, `APP_DEBUG` (off in production), local password sign-in, the Asia/Manila timezone (NFR-09), and the app version shown in the footer (NFR-10). Commit a `.env.example` with no real values. Never commit credentials.

`LOCAL_PASSWORD_LOGIN_ENABLED` explicitly enables or disables sign-in with locally stored passwords. It defaults to enabled in non-production and disabled in production; set it to `true` for the pilot only after HTTPS and secure temporary-password delivery are ready. `DEMO_LOGIN_ENABLED` is separate and controls only seeded demo accounts in non-production environments. Demo accounts are marked in the database, refused in production even if demo login is accidentally enabled, and the demo seeder refuses to run in production.

## Server setup (to be confirmed with the university)

The university hosts the application on its own server. GitHub is used for source control only. Whichever server is used, the Laravel application needs:

- A PHP version and extensions supported by the chosen Laravel release (confirm exact versions when the project is set up).
- A web server whose document root is the application's `public/` folder.
- MySQL, or MariaDB if that is what the host provides, with a dedicated database and database user.
- HTTPS.
- Write access to the `storage/` and `bootstrap/cache/` folders for the application's user.
- A way to install PHP dependencies with Composer and to run `php artisan migrate` during a release.
- No Node or front-end build tools. Assets are plain CSS and JavaScript files.

**Open hosting questions** (TODO(you): ask the university's IT office). Nothing about the server is assumed beyond the list above, which is Laravel's own requirements (confirmed).

1. Who owns and maintains the server, and who can deploy to it?
2. How is access given: SSH, a control panel, a deployment pipeline, or file transfer only?
3. Which operating system, web server, PHP versions and database (MySQL or MariaDB, and which version) are available?
4. Is Composer available, or must dependencies be installed elsewhere and uploaded?
5. Is HTTPS provided, and what will the domain name be?
6. Is the application reachable from the whole internet or from the campus network only? Student phones and the display board must both reach it.
7. How are backups made and restored, and how often?
8. Can a staging copy be set up for rehearsals?
9. Is a scheduled task (cron) or background worker available? Neither is needed for the MVP.
10. If Reverb is considered after the pilot: are persistent WebSocket processes supported, and can the reverse proxy, firewall, TLS certificate, process manager, and monitoring be configured for them?
11. What student/staff identity provider and protocol are available (for example OIDC/OAuth 2.0, SAML 2.0, or LDAP), and is there a separate student-information API or supported data export? Do not assume EDI provides authentication.
12. What audit-log retention and archival period does university policy require, and which roles may review it? NU has agreed to the privacy proposal for the pilot, with the application activity log as an accountability requirement; record the operational retention and access decisions before real data is loaded.

## Database setup (once)

1. Create the database and a dedicated database user that has only the privileges the application needs. Never use `root`.
2. Put the connection settings in the server's `.env`.
3. Run the migrations (`php artisan migrate`).
4. On local or staging only, run `php artisan db:seed` to create the fake demo admin and other demo accounts. The demo seeder refuses to run in production. On a new production database, set `LOCAL_PASSWORD_LOGIN_ENABLED=true` only when local-password sign-in is approved, then the server owner runs `php artisan queue-it:create-admin` interactively from the server terminal. It refuses to create another admin; never create an admin from a public page.
5. Create the Admission (AMS) pilot department, its approved services, student accounts and staff accounts through the admin screens. Use fake demo data on staging.
6. On production, provision student records only from a university-authorized source. Admins may import a UTF-8 CSV using `student_id`, `full_name`, `email`, and optional `course`; preview and validate it before creating accounts. Imports never update existing records. Exports contain only these roster fields and are audited. Real roster files must be kept off git, shared drives, and unapproved devices; follow NU's approved storage, retention, and secure credential-delivery rules. Imported accounts receive undisclosed random passwords; reset each account individually before delivering access. Configure external identity only after IT confirms the protocol and ownership mapping ([ADR-0003](decisions/ADR-0003-account-provisioning-and-identity.md)).
7. Confirm each row of the [access matrix](04-database-schema.md) with a feature test before go-live.
8. Set up backups before real data arrives, including an agreed retention policy for the activity log. The current admin activity log records the actor (or System), action, affected record, date/time, and department where applicable. Verify these entries remain available to authorized reviewers; do not record passwords, tokens, or unnecessary personal data.
9. Keep five-second polling enabled. Reverb is optional and should be deployed only after host support and reconnect fallback are tested.

## Release steps

1. All items in the version's exit criteria pass ([06](06-test-plan.md)).
2. Update the app version, and the version header of any doc that changed.
3. Move the **Unreleased** entries in `CHANGELOG.md` under the new version with today's date.
4. Merge to `main`. Tag: `git tag -a v0.3.0 -m "v0.3.0"` and `git push origin v0.3.0`.
5. Create a GitHub Release from the tag, pasting the changelog section.
6. Back up the production database.
7. Deploy the tagged version to the server and install dependencies (`composer install --no-dev`). Before applying the service-window migration, let staff complete or skip all tickets currently being served that day; the migration refuses to proceed while any remain. Then use maintenance mode (`php artisan down`), run `php artisan migrate --force`, refresh Laravel's caches (`php artisan optimize`), and return to service (`php artisan up`). The exact deployment method depends on the answers to the hosting questions.
8. Open the live site, confirm the footer shows the new version, and run the smoke path.

## Rollback

Redeploy the previous tag. If the release ran migrations, either roll them back (only when that migration's `down` step is safe for the data) or restore the database backup taken before the release. Migrations are a controlled way to apply schema changes, not a guarantee that every change can be undone. Do not delete tags. Publish a patch version instead.

## Display board (kiosk) setup

1. Open `/display` in the TV's browser, choose the office (or go directly to `/display/AMS` with its prefix), and press F11 for full screen. The board refreshes every five seconds and displays ticket codes, not student identities.
2. Disable sleep on the device.
3. When sound exists (DSP-04, after the MVP), enable it once with the on-screen button, because browsers block autoplay.
4. The page keeps refreshing and recovers by itself after a network drop.

## Hygiene

- Never commit `.env` or database dumps (already in `.gitignore`).
- Seed data uses fake names and `example.com` emails only.
- Real student records are never committed to the repository.
- Review the repository for personal data before making it public.
