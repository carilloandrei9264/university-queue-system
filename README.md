# NU Queue-It

NU Queue-It is a Laravel-based queue management system for university offices. Students can take a service ticket, staff can manage the waiting queue, and a public display shows the ticket being served. The first intended pilot is the Admission office (AMS).

**Project status:** Functional local MVP; not yet ready for a production or university pilot. See [the next steps](docs/10-next-steps.md) for the remaining validation and deployment work.

## Features

- Role-based student, office staff, and administrator accounts
- Admin-managed departments, services, and accounts
- Generated temporary passwords for provisioned accounts, with a forced change at first sign-in
- Student ticket creation and cancellation; staff call, complete, skip, and recall actions
- Public queue display
- Automatic queue refresh every five seconds
- Read-only administrator activity log
- Responsive interface with loading, empty, offline, error, and validation feedback

The current MVP uses local password accounts. University sign-in adapters and Reverb-based real-time updates are deferred until the pilot institution and hosting requirements are known.

## Technology

- PHP 8.3+
- Laravel 13
- MySQL or MariaDB (the default local configuration uses MariaDB)
- Blade templates, plain CSS, and lightweight JavaScript; no frontend build step

## Requirements

- PHP 8.3+ with the extensions required by Laravel and the selected database driver
- Composer
- MySQL or MariaDB

For running the automated test suite, enable PHP's `pdo_sqlite` extension; the PHPUnit configuration uses SQLite.

## Local development

1. Install dependencies and create the environment file:

   ```powershell
   composer install
   Copy-Item .env.example .env
   ```

2. Create a local database and a dedicated application user. For MariaDB/MySQL, for example:

   ```sql
   CREATE DATABASE university_queue_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'queue_it_app'@'127.0.0.1' IDENTIFIED BY 'use-a-unique-local-password';
   GRANT ALL PRIVILEGES ON university_queue_system.* TO 'queue_it_app'@'127.0.0.1';
   ```

3. Set `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in `.env` to match that database. Set `DEMO_SEED_PASSWORD` to a private password for local demo accounts, then initialize the application:

   ```powershell
   php artisan key:generate
   php artisan migrate --seed
   php artisan serve
   ```

   Open `http://127.0.0.1:8000`.

The demo seeder creates an administrator, office staff, and student accounts. Their email addresses are listed in [DemoSeeder.php](database/seeders/DemoSeeder.php); the seeded accounts use the local `DEMO_SEED_PASSWORD` value. Demo accounts and seeding are for development only; never use them in production.

For a new production database, create the first administrator interactively on the server with `php artisan queue-it:create-admin`. Do not pass passwords as command-line arguments. Account credentials are sensitive: share generated temporary passwords with users through an approved secure channel.

## Tests and code style

```powershell
php artisan test
vendor/bin/pint --test
```

The test suite uses SQLite, while deployment uses the institution's selected MySQL/MariaDB version. Passing SQLite tests is not a substitute for validating migrations, behavior, and concurrent ticket operations against the actual deployment database. See the [test plan](docs/06-test-plan.md) and [manual checklist](tests/manual-checklist.md).

## Documentation

- [Documentation index](docs/README.md)
- [Requirements and MVP scope](docs/02-requirements.md)
- [Architecture](docs/03-architecture.md)
- [Database schema](docs/04-database-schema.md)
- [Deployment guidance](docs/07-deployment.md)
- [Roadmap](docs/09-roadmap.md)
- [Next steps for the Admission pilot](docs/10-next-steps.md)

## Contributing and versioning

See [versioning and workflow](docs/08-versioning-and-workflow.md). Keep implementation and documentation in sync, and run the relevant automated and manual checks before proposing a change.
