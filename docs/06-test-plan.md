# 06. Test plan

| Version | Status | Last updated |
|---------|--------|--------------|
| 0.4 | Draft | 2026-11-08 |

## Strategy

| Level | What | How | When |
|-------|------|-----|------|
| Unit | Pure logic in `app/Domain/` (ticket code, status transitions, wait estimate) | Laravel's test runner, `tests/Unit` | Every change to `Domain/` |
| Manual functional | Screens and flows per role | [`tests/manual-checklist.md`](../tests/manual-checklist.md) | Before every release |
| Concurrency | Two people acting at the same moment | `QueueConcurrencyTest` runs queue operations in separate PHP processes against a disposable MySQL/MariaDB test database, plus two browser windows by hand | Built in v0.3.0, rerun before every release |
| Accessibility | Keyboard, contrast, screen reader basics | Checklist in 05 plus browser dev tools audit | Before v0.6.0 (MVP screens) and v1.0.0 |
| Cross-browser | Chrome, Edge, Firefox, Safari | Run the smoke path in each | Before v1.0.0 |
| Security | Role and department rules | Feature tests that send each forbidden request as each role (see below) | Every version that changes rules |
| Migrations | Schema changes apply cleanly | `php artisan migrate:fresh --seed` on an empty database, and the new migrations on a copy of the existing data | Every version that changes the schema |

## Test cases

Write each case before building the feature. IDs link back to [requirements](02-requirements.md).

| ID | Requirement | Steps | Expected |
|----|-------------|-------|----------|
| T-AUTH-01 | AUTH-01 | Sign in as a person who is not in the student records | Refused with a clear message |
| T-AUTH-05a | AUTH-05 | Logged in as student, open the staff page | Refused or redirected away |
| T-AUTH-05b | AUTH-05 | As student, send the "call next" request directly | Refused (403) |
| T-AUTH-05c | AUTH-05 | As staff of department A, try to call next in department B | Refused (`forbidden`) |
| T-AUTH-04 | AUTH-04 | Search the app for any admin-creation path | None exists |
| T-AUTH-07 | AUTH-07 | Create/reset a local account, sign in with its one-time password, change it, then inspect behavior in production and non-production configurations | Generated credential is shown once, cached responses are disabled, password is hashed and forced to change, no secret is logged or retrievable, demo accounts cannot sign in in production, and provisioned local accounts can sign in only when enabled |
| T-AUTH-08 | AUTH-08 | Sign in through a configured test identity adapter using a stable external subject | Existing local role is preserved; unknown or inactive users are refused |
| T-ADM-07 | ADM-07 | Admin creates and edits student accounts with unique identity data; try duplicates and role escalation | Valid student account is managed; duplicates and privilege escalation are rejected |
| T-TKT-02 | TKT-02 | Take tickets on two different days (change the test clock) | Number restarts at 001, per department |
| T-TKT-03 | TKT-03 | Two students submit in the same department at the same moment | Different numbers, no gaps or duplicates |
| T-TKT-04 | TKT-04 | Student with an active ticket tries to take another | Existing ticket shown, no new ticket |
| T-TKT-04b | TKT-04, TKT-06 | Finish or cancel a ticket at one office, then take one at another office (enrollment) | Allowed |
| T-TKT-05 | TKT-05 | Staff calls the student's ticket | Student screen updates by itself within one refresh interval, without reloading |
| T-TKT-06 | TKT-06 | Cancel while waiting, then try to cancel while serving | First works, second refused |
| T-STF-01 | STF-01 | Staff opens console | Only own department's tickets visible |
| T-STF-02 | STF-02 | Two staff of the same department press Call next together | Two different tickets, none called twice |
| T-STF-03 | STF-03 | Complete the serving ticket | Status `completed`, `completedAt` set |
| T-STF-04 | STF-04 | Skip, then recall | Ticket returns to serving |
| T-DSP-01 | DSP-01 | Open the public display while tickets exist | No student name, ID number or email appears in the page or in its refresh response |
| T-DSP-02 | DSP-02 | Call a ticket with the display open | Display updates by itself within one refresh interval |
| T-ADM-06 | ADM-06 | Deactivate a department that has old tickets | Old tickets still show the department name |
| T-ADM-08 | ADM-08 | Create/update/deactivate accounts and catalog records; create, call, complete, cancel, skip and recall tickets; inspect the admin log | Each successful mutation is recorded once with actor/action/target/time; no password, token or unnecessary personal values appear; refresh polling does not create entries |
| T-ADM-08b | ADM-08 | Attempt to edit or delete an audit entry through an application route | No mutation route exists; entries remain unchanged |
| T-FBK-01 | FBK-01 | Rate a completed ticket twice | Second rejected |
| T-NFR-03a | NFR-03 | Turn off the network on each data screen | Offline banner, last data marked stale |
| T-NFR-03b | NFR-03 | Throttle to slow 3G | "Still loading" message after 4 seconds |
| T-NFR-03c | NFR-03 | Open a screen with no data | Empty state with next action |
| T-NFR-01 | NFR-01 | View each screen at 360px | No horizontal scrolling |
| T-NFR-06 | NFR-06 | Seed a student whose name is `<img src=x onerror=alert(1)>` | Shown as plain text everywhere |
| T-NFR-11 | NFR-11 | Send each form with missing or invalid fields directly, skipping the browser checks | Rejected by the server with a clear message |

Cases for requirements deferred past the MVP (FBK-01) are written now and run when the feature ships.

If Reverb is introduced after the pilot, add tests for private-channel authorization, broadcasting only after commit, disconnect/reconnect recovery, and successful operation with the polling fallback when the WebSocket server is unavailable.

## MySQL/MariaDB integration tests

The default `php artisan test` suite uses SQLite for speed. For database-specific migrations, locking, and concurrency validation, create a separate empty test database and grant the test connection access only to it. Configure `DB_HOST`, `DB_PORT`, `DB_USERNAME`, and `DB_PASSWORD` in the local `.env`, then run:

```powershell
vendor/bin/phpunit --configuration phpunit.mariadb.xml
```

The MariaDB profile targets `university_queue_system_test` and the tests run `migrate:fresh`; this database must contain no data that needs to be preserved. The two concurrency tests are skipped on SQLite because SQLite does not implement the row-locking behavior the queue relies on.

## Unit tests to write first (`tests/Unit/`)

- `formatCode('RGS', 7)` returns `RGS-007`, and `formatCode('RGS', 1234)` still works.
- `canTransition(from, to)` accepts exactly the transitions in [03](03-architecture.md) and rejects all others.
- `estimateWait(ahead, recentServiceMinutes, fallback)` handles empty history.
- `queueDate(now)` returns the Asia/Manila date even when the server or browser is in another time zone, including just before and after midnight.

## Defect handling

File a GitHub issue with the bug template. Label by severity: `blocker` (cannot release), `major`, `minor`. A release has no open `blocker` items.

## Exit criteria for a version

- All test cases for that version's requirements pass.
- Unit tests pass.
- Manual checklist run on desktop and a phone.
- No open `blocker` issues.
