# ADR-0001: Backend approach

| Status | Date |
|--------|------|
| **Accepted** | 2026-11-07 (proposed 2026-11-06) |

## Context

A queue is shared state. A student's phone, the staff screen and the public display must all see the same tickets, and ticket numbers must never collide. The university should be able to host the application on its own server, and the first release is a small pilot for one department.

The old system solved this with PHP and MySQL, written by hand without a framework. Most of its bugs and security holes came from that hand-written code ([audit](../01-old-system-audit.md)): missing login checks, mismatched status and role names, and unscoped queries.

## Options

### A. Browser-only (localStorage and BroadcastChannel)

All data stays in the browser. Roles are simulated.

- Pros: nothing to set up, works offline, fastest to build, fine for a demo.
- Cons: **no real queue.** Another phone cannot see your ticket. Anyone can edit the data in dev tools, so roles are not real. It breaks the main goal of the project.

### B. Plain JavaScript front end plus a hosted backend service (for example Firebase: Authentication and Firestore)

The page uses the service's JavaScript SDK from a CDN, with no build step. You write no server code. Security comes from the service's rules.

- Pros: real shared data on any device, built-in live updates, transactions for atomic ticket numbers and call-next, real accounts, works on GitHub Pages, and you already used the same approach in TrustHome PH (Firestore transactions and security rules).
- Cons: depends on a vendor, rules must be written and tested carefully, the free tier has limits, and the front end alone is no longer the whole story ("HTML, CSS and JS only" is true for your code, but the data lives in a cloud service). If your instructor requires your own server, this may not qualify.

Practical notes for B:
- Admins cannot create other people's login accounts from the browser without extra services. Use an **invite list**: the admin adds a staff email and department, and the staff member then registers themselves. The rules give them the `staff` role only if their email is on the list. The first admin is created by hand in the console (AUTH-04).
- Firebase web configuration is public by design. Protection comes from the rules, so the access matrix in [04](../04-database-schema.md) must be tested.

### C. Laravel with a relational SQL database, hosted by the university

Laravel provides the application, authentication, authorization and server-side validation. Data lives in MySQL, or MariaDB if that is what the university's hosting provides.

- Pros: one framework gives authentication, authorization policies, validation, CSRF protection, migrations and a test runner. SQL transactions and row locks fit the queue rules (unique daily numbers, no ticket called twice). The university keeps the server and the data.
- Cons: needs PHP hosting with a supported PHP version, which is not yet confirmed. More to set up than a static site, and a deployment process is needed.

## Decision

**C.** Laravel for the application, authentication, authorization and server-side validation, on a relational SQL database. MySQL is the default. If the university's hosting provides MariaDB instead, MariaDB is the compatible deployment choice. The schema is not designed to switch database engines later. Schema changes are made with Laravel migrations, which apply them in a controlled order. The university hosts the application on its own server. GitHub Pages and Firebase are not used, and GitHub is for source control only. A small pilot for one department comes before any expansion.

Options A and B are not used. A cannot share a queue between devices. B depends on a third-party cloud service and does not give the university its own server.

## Consequences

- Authorization policies and the transactional ticket rules (see [04](../04-database-schema.md)) are the most important design work. Write and review them early.
- Every operation that must be atomic (create ticket, call next) is one database transaction with row locks.
- Add a feature test for every line of the access matrix, and run concurrency tests on the same database engine as production.
- Migrations are a controlled way to apply schema changes. They do not remove the need to plan changes and back up the database first.
- Real-time infrastructure is deferred. The MVP refreshes screens periodically.
- The university's hosting details are still open (see [07](../07-deployment.md)). Revisit this decision if they rule out PHP, or rule out both MySQL and MariaDB.

## Open items

TODO(you): confirm the hosting details in [07](../07-deployment.md) and which of Admission, Registrar and Accounting starts the pilot.
