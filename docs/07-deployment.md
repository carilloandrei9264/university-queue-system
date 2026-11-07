# 07. Deployment

| Version | Status | Last updated |
|---------|--------|--------------|
| 0.4 | Draft | 2026-11-08 |

## Environments

| Environment | Where | Data | Purpose |
|-------------|-------|------|---------|
| Local | Your own machine, running Laravel with XAMPP (Apache, PHP and a database) or Laravel's local server (`php artisan serve`) | A local database with fake seed data | Daily work |
| Staging (recommended) | A copy of the production setup on the university server, if the university allows it | A separate database with fake data | Rehearse releases and migrations before the pilot |
| Production | The university-hosted server | MySQL by default, or MariaDB if that is what the host provides | What people use |

Use the same database engine, with a similar version, in every environment, because the queue's locking behaviour depends on it. XAMPP's bundled database is MariaDB. If you develop on XAMPP, either install MySQL for local work or choose MariaDB as the deployment engine, depending on what the university's host provides, so every environment uses the same engine. TODO(you): confirm which engine the university's host provides. Keep the test database separate from the production one, so tests never touch real data.

## Configuration

Settings live in `.env` on each machine (never committed) and in Laravel's `config/` files: application URL, database connection, `APP_ENV`, `APP_DEBUG` (off in production), the Asia/Manila timezone (NFR-09), and the app version shown in the footer (NFR-10). Commit a `.env.example` with no real values. Never commit credentials.

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
12. What audit-log retention and archival period does university policy require?

## Database setup (once)

1. Create the database and a dedicated database user that has only the privileges the application needs. Never use `root`.
2. Put the connection settings in the server's `.env`.
3. Run the migrations (`php artisan migrate`).
4. On local or staging only, run `php artisan db:seed` to create the fake demo admin and other demo accounts. The demo seeder refuses to run in production. On a new production database, the server owner runs `php artisan queue-it:create-admin` interactively from the server terminal. It refuses to create another admin; never create an admin from a public page.
5. Create the pilot department, its services, student accounts and staff accounts through the admin screens. Use fake demo data on staging.
6. On production, provision student records from an approved university source, or have an admin create them through the app. Real records are loaded on the server and never committed to git. Configure external identity only after IT confirms the protocol and ownership mapping ([ADR-0003](decisions/ADR-0003-account-provisioning-and-identity.md)).
7. Confirm each row of the [access matrix](04-database-schema.md) with a feature test before go-live.
8. Set up backups before real data arrives, including an agreed retention policy for the activity log.
9. Keep five-second polling enabled. Reverb is optional and should be deployed only after host support and reconnect fallback are tested.

## Release steps

1. All items in the version's exit criteria pass ([06](06-test-plan.md)).
2. Update the app version, and the version header of any doc that changed.
3. Move the **Unreleased** entries in `CHANGELOG.md` under the new version with today's date.
4. Merge to `main`. Tag: `git tag -a v0.3.0 -m "v0.3.0"` and `git push origin v0.3.0`.
5. Create a GitHub Release from the tag, pasting the changelog section.
6. Back up the production database.
7. Deploy the tagged version to the server, install dependencies (`composer install --no-dev`), run `php artisan migrate --force`, and refresh Laravel's caches (`php artisan optimize`). Use maintenance mode (`php artisan down` and `php artisan up`) when a release changes the schema. The exact deployment method depends on the answers to the hosting questions.
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
