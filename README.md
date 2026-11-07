# NU Queue-It v2

A queue management system for university offices (starting with the enrollment offices: Admission, Registrar and Accounting). Students take a ticket from their phone, office staff call the next person, and a public screen shows who is being served.

This is a rebuild of the original NU Queue-It (group project, 2025). The old system is audited in [`docs/01-old-system-audit.md`](docs/01-old-system-audit.md).

**Status:** Laravel foundation in progress (v0.1.0).
**Stack:** Laravel 13, PHP 8.3+, and MySQL/MariaDB. Local development uses the MariaDB bundled with XAMPP; the production engine must match the university host. See [ADR-0001](docs/decisions/ADR-0001-backend-approach.md).
**MVP:** a pilot for one department. See [`docs/02-requirements.md`](docs/02-requirements.md).

## Where to start

Read [`docs/README.md`](docs/README.md). It lists every document, what it is for, and the order to fill them in.

## Local setup

1. Install PHP 8.3 or later, Composer, and MySQL or MariaDB.
2. Create a local database and a dedicated app user (do not use `root` for the application):

   ```sql
   CREATE DATABASE university_queue_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'queue_it_app'@'127.0.0.1' IDENTIFIED BY 'replace-with-a-long-local-password';
   GRANT ALL PRIVILEGES ON university_queue_system.* TO 'queue_it_app'@'127.0.0.1';
   ```

3. Run `composer install`, then copy `.env.example` to `.env`.
4. Set `DB_PASSWORD` and a private, local-only `DEMO_SEED_PASSWORD` in `.env`, then run `php artisan key:generate`.
5. Run `php artisan migrate --seed` to create the MVP tables and fake demo records.
6. Start the local server with `php artisan serve`.

The demo seeder creates one admin, one staff account per office, and three students. Their emails and fake records are listed in [`database/seeders/DemoSeeder.php`](database/seeders/DemoSeeder.php); all demo accounts use the password set in `.env`. Never use the demo seeder or its accounts in production. The seed data covers Admission, Registrar and Accounting; the pilot still starts with one office.

Local demo sign-in is enabled by `DEMO_LOGIN_ENABLED=true` in `.env.example`. The login is intentionally unavailable in production until the university identity provider is implemented.

On a new production database, run `php artisan queue-it:create-admin` from the server terminal to interactively create the first administrator. The command accepts no credentials as command-line arguments, prompts for a confirmed password, and refuses to create another admin once one exists. After signing in locally as an admin, use the Departments, Services, and Office staff links to manage the catalog. Staff passwords are local demo credentials only; production sign-in will use the university identity provider.

## Project layout

Standard Laravel structure with the project-specific code and documentation alongside it.

```
queue-it-v2/
  app/
    Domain/             ticket rules (code format, status transitions)
    Http/               controllers, form requests (validation), middleware
    Models/
    Policies/           who may do what
    Services/           actions that use one transaction (create ticket, call next, complete)
  config/
  database/
    migrations/         schema changes, applied in order
    seeders/            fake demo data only
  public/               web root, with css/ and js/ as plain files (no build step)
  resources/
    views/              Blade templates
  routes/
  tests/                unit and feature tests, manual checklist
  docs/                 SDLC documents (versioned together with the code)
```

## Versioning

Semantic Versioning, Conventional Commits, GitHub Flow. Details: [`docs/08-versioning-and-workflow.md`](docs/08-versioning-and-workflow.md). Release history: [`CHANGELOG.md`](CHANGELOG.md).
