# 01. Old system audit (NU Queue-It, group3_FINALS)

| Version | Status | Last updated |
|---------|--------|--------------|
| 1.0 | Review | 2026-11-06 |

Reviewed: 40 files, about 2,300 lines of HTML, CSS and JS, plus PHP endpoints and a MySQL dump. Findings below are from reading the code, not from running it.

## 1. It is not only HTML, CSS and JS

The old system depends on **PHP and MySQL**: 3 of its 6 pages and all 20 backend scripts are PHP, and the data lives in a MySQL database (`queue-it.sql`). It cannot be hosted on GitHub Pages, and it needs a PHP server (XAMPP) to run. A queue is shared state between many people, so a pure front end needs somewhere to keep that state. This is the main design decision of the rebuild. See [ADR-0001](decisions/ADR-0001-backend-approach.md).

## 2. Correctness bugs

| # | Finding | Where |
|---|---------|-------|
| C1 | "Call next" is not scoped to a department. It promotes the oldest waiting ticket from any department, and marks every serving ticket in the whole system as complete. | `backend/api/update-queue-status.php` |
| C2 | Status values do not match. The database enum is `waiting`, `serving`, `completed`, but this script writes `Waiting`, `Serving`, `Complete`. `Complete` is not in the enum. | same file vs `queue-it.sql` |
| C3 | `complete-queue.php` uses `$pdo`, which is never defined anywhere. The connection file only creates `$connection` (mysqli). The script cannot run. | `backend/api/complete-queue.php`, `config/connection.php` |
| C4 | Role names disagree. The database role is `employee`, the check looks for `staff`. The login stores `$_SESSION['role']`, the check reads `$_SESSION['user_role']`. Staff would be denied. | `update-queue-status.php`, `login-form.php` |
| C5 | Queue numbers never reset. The number is `MAX(queue_number)+1` per department across all time. | `backend/api/generate-queue.php` |
| C6 | Race condition on numbering. The unique key is (department, service, number), but the number is computed per department, so two students in different services can still receive the same number. | `queue-it.sql`, `generate-queue.php` |
| C7 | Staff are not linked to a department. Any employee account can operate every queue. | `user_account_register` has no `department_id` |
| C8 | Nothing stops a student from holding several active tickets. The only rule is "one ticket per minute". | `generate-queue.php` |

## 3. Security problems

| # | Finding | Where |
|---|---------|-------|
| S1 | **No login check at all** on `delete-user.php`. Anyone who can reach the URL can delete any account by POSTing an id. | `backend/api/delete-user.php` |
| S2 | No login check on `complete-queue.php`, `data-fetcher.php` and `get-queue-list.php`. | `backend/api/` |
| S3 | `create-admin.php` has no access check and contains a placeholder password. Left on a server, it lets anyone create an admin. | `backend/create-admin.php` |
| S4 | Database connects as `root` with an empty password. | `backend/config/constants.php` |
| S5 | The SQL dump contains real-looking email addresses and password hashes. | `queue-it.sql` |
| S6 | Admin login is a checkbox on the normal login form. Guest login sets a session with no checks. | `backend/login-form.php` |
| S7 | Dashboards build HTML from data with `innerHTML` (15 uses across 3 files), which risks script injection if any text is not escaped. | `assets/js/*.js` |

## 4. Data model problems

| # | Finding |
|---|---------|
| D1 | `feedback.user_id` is `varchar`, while `user_account_register.id` and `queue.user_id` are `int`. |
| D2 | `feedback.queue_number` is `varchar` (sample rows use `CASH001`), while `queue.queue_number` is `int`. There is no cashier department. Feedback is not linked to a ticket id, so it cannot be tied to a department or a person reliably. |
| D3 | No foreign keys on `queue.user_id`, `queue.department_id`, `queue.service_id`, `feedback.*`. Only `services.department_id` has one. |
| D4 | The displayed code (`AMS001`) is not stored. It is rebuilt in code from a prefix and an int. |
| D5 | No timestamps for when a ticket was called or completed, so wait time and service time cannot be measured. |
| D6 | The users table is named `user_account_register`, mixes students, staff and admin, and uses camelCase and snake_case columns together. |

## 5. Front-end and UX problems

| # | Finding |
|---|---------|
| F1 | No live updates. The only `setInterval` is a clock in `office-dashboard.js`. Queue status loads once per page view. |
| F2 | Errors are shown with `alert()` (21 uses). There are no loading, empty or offline states. |
| F3 | The logout code is copy-pasted in three JS files. |
| F4 | Five CSS files with no shared design tokens (colors and spacing are repeated). |
| F5 | Screens are a mix of `.html` and `.php`, and access control depends on PHP sessions, so the HTML pages are reachable without logging in. |
| F6 | No public display board, no estimated wait, no skip or no-show handling, no counter or window labels. |
| F7 | Icons depend on Google's Material Symbols font loaded from a CDN. |

## 6. What was good (keep it)

- The core flow is right: choose department, choose service, get a ticket, staff call, complete, feedback.
- Prepared statements are used for SQL, passwords are hashed with `password_verify`, and there is a one-per-minute rate limit.
- Department prefixes (AMS, RGS, LIB, ACC, EXC) are a good ticket-code idea.
- A retry loop for duplicate-number errors shows the right instinct about concurrency.

## 7. How the rebuild answers each finding

| Finding | Answer | Document |
|---------|--------|----------|
| C1, C4, C7 | Staff belong to one department. Every staff action is checked against it. | 04 |
| C2, C3 | One status list defined once, with a transition table. | 03, 04 |
| C5, C6, C8 | Daily counter per department updated in one atomic step. One active ticket per student. | 04 |
| S1 to S3, S6 | Role checks on every operation, enforced by the data layer's rules, not only by hiding buttons. No public admin creation. | 04, 07 |
| S4, S5 | No default credentials. Only fake seed data in the repo. | 07 |
| S7 | Rule: never insert data with `innerHTML`. Use `textContent` or a safe template helper. | 03 |
| D1 to D6 | Redesigned schema with consistent types, foreign keys and timestamps. | 04 |
| F1 | Live listeners on the queue. | 03 |
| F2, F3, F4 | Shared UI helpers, design tokens, state components. | 03, 05 |
| F6 | Display board, estimated wait, skip, counter labels. | 02 |
