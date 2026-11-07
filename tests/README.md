# Tests

Laravel's test structure is used once the project is set up:

- `Unit/` for pure rules in `app/Domain/` (ticket codes, status transitions, wait estimate).
- `Feature/` for authorization, the ticket flow, and concurrency. Run these against the same database engine as production (MySQL, or MariaDB if that is the deployment). Row locks behave differently on other engines.
- `manual-checklist.md` is run before every release.

Run the automated tests from the project root:

```
php artisan test
```

Write the tests listed in `docs/06-test-plan.md` before writing the matching code.
