# 03. Architecture

| Version | Status | Last updated |
|---------|--------|--------------|
| 0.3 | Draft | 2026-11-07 |

## Principles

1. **Laravel conventions.** Standard Laravel structure, server-rendered Blade views, plain CSS and light JavaScript. No front-end framework and no front-end build step.
2. **Rules on the server.** Authentication, authorization and validation are done by Laravel on the server. The browser is never trusted.
3. **Pure logic is separate.** Ticket codes, state transitions and wait estimates are plain PHP classes in `app/Domain/`, with unit tests, so controllers stay thin.
4. **Rules live with the data.** Role and department checks are enforced on the server (policies and department-scoped queries), and key rules are backed by database constraints, not only by hiding buttons.
5. **Safe by default.** Blade's escaped output (`{{ }}`) for all user text. Never `innerHTML` or unescaped output.

## Folder structure

Standard Laravel layout, created when the project is set up. The project's own additions are described here.

```
app/
  Domain/                  pure rules: ticket code format, status transitions, wait estimate
  Http/
    Controllers/           thin: receive the request, call a service, return a view
    Requests/              form requests: server-side validation
    Middleware/
  Models/                  User, Department, Service, Ticket, DailyCounter
  Policies/                who may do what (role and department)
  Services/                actions that change data in one transaction: create ticket, call next, complete
config/                    app settings, Asia/Manila timezone
database/
  migrations/              every schema change, applied in order
  seeders/                 fake demo data only, and the first-admin command
  factories/
public/                    web root, with css/ and js/ as plain files (tokens, base, components, page styles, small refresh and state helpers; see 05)
resources/
  views/                   Blade templates: layouts, components, auth/ student/ staff/ admin/ display/
routes/web.php             routes grouped by role
tests/
  Unit/                    domain rules
  Feature/                 authorization, ticket flow, concurrency
  manual-checklist.md
```

## Layers and rules

```
request -> middleware (login, role) -> form request (validation) -> controller
        -> policy check -> service (one transaction) -> models -> database
                                  \-> domain (pure rules)
        <- Blade view (escaped output)
```

- Controllers stay thin. They never contain ticket rules or queries that change tickets.
- Every action that changes data goes through a service that runs in one database transaction.
- `Domain/` does not depend on Laravel. Services may use `Domain/`.

## Application actions (summary)

Each action is a service method called by a controller after validation and authorization. A broken rule returns a clear error: a validation message, `403` forbidden, or `409` conflict.

| Area | Actions |
|------|---------|
| Auth | Sign-in with the university account (seeded records until it is connected, see [ADR-0002](decisions/ADR-0002-university-sign-in.md)) and logout (AUTH-01, AUTH-02) |
| Catalog | List departments and services. Admin creates and edits them (ADM-01, ADM-02) |
| Tickets | `createTicket(student, service)`, `cancelTicket(ticket)` |
| Staff | `callNext(staff)`, `completeTicket(ticket)`, `skipTicket(ticket)`, `recallTicket(ticket)`. `setPaused` is after the MVP |
| Status and display | Read-only queries for the student's own ticket, one department's queue, and the public board (board fields only) |
| Admin | `createStaff(...)`, `setUserActive(...)` |
| Deferred | Feedback and statistics |

`createTicket` and `callNext` must be atomic (see [04](04-database-schema.md)). Staff actions take the department from the logged-in user, never from the request.

## Ticket status model

One list, defined once in `app/Domain/` (a status enum or class):

```
waiting -> serving -> completed
waiting -> cancelled      (student, or staff for a closed queue)
serving -> skipped        (no-show)
skipped -> serving        (recall)
```

Everything else is rejected. This removes the old mismatch between `Complete` and `completed`. The MVP uses all of these transitions.

## Live updates (periodic refresh in the MVP)

- Student, staff and display screens refresh themselves on a short interval (initially every 5 to 10 seconds, to be tuned in the pilot) by fetching a small read-only response from the server. Refreshing pauses while the tab is hidden.
- If a refresh fails, show the offline banner and keep the last known data marked as stale. When the connection returns, refresh again.
- Real-time infrastructure (for example WebSockets) is deferred. Add it only if the pilot shows that the refresh interval is not good enough.

## Error handling and states

Every data screen renders from one state value: `loading`, `empty`, `error`, `offline`, `slow` or `ready`, using shared Blade components, a small JavaScript helper for the refresh states, and the components in [05](05-ui-ux-design.md). No `alert()`.

## Security

- Role and department checks are enforced on the server by Laravel policies and middleware (see the access matrix in [04](04-database-schema.md)). Staff queries are always scoped to the staff user's own department.
- Hiding a button or redirecting in the browser is for user experience only. It is never the only protection.
- Laravel's CSRF protection stays on for every form and state-changing request. The app never stores or logs a plain-text password, and seeded demo accounts use Laravel's hashing ([ADR-0002](decisions/ADR-0002-university-sign-in.md)).
- No secrets in the repo. Settings and credentials live in `.env`, which is not committed.

## Testing approach

- Pure logic in `app/Domain/` has unit tests, run with Laravel's test runner.
- Authorization, ticket flow and concurrency have feature tests that run against the same database engine as production (MySQL, or MariaDB if that is the deployment), because other engines handle row locks differently (SQLite, for example, ignores them).
- Screens and flows use the manual checklist in [06](06-test-plan.md).

## Browser support

Current Chrome, Edge, Firefox and Safari. CSS custom properties and `<dialog>` are fine.
