# 02. Requirements

| Version | Status | Last updated |
|---------|--------|--------------|
| 0.4 | Draft | 2026-11-08 |

Priority uses MoSCoW: **M**ust, **S**hould, **C**ould. "Must" items define v1.0.0. Each requirement has a stable ID used in issues, tests and commits. The first release for real users is a smaller MVP (a one-department pilot), defined in [MVP scope](#mvp-scope-pilot).

## MVP scope (pilot)

The first release for real users is a pilot that starts with **Admission (AMS)**. Registrar and Accounting remain later expansion candidates. Only what is listed under "Included" is needed for the pilot.

**Included**

| # | MVP item | Requirements | Pilot notes |
|---|----------|--------------|-------------|
| 1 | Admin setup for one department, its services, student and staff accounts | ADM-01, ADM-02, ADM-03, ADM-06, ADM-07, AUTH-03, AUTH-04, AUTH-07 | One department at first; admins provision accounts and assign roles. |
| 2 | Student sign-in, ticket creation, ticket status viewing | AUTH-01, AUTH-02, AUTH-07, TKT-01, TKT-05, TKT-06 | Accounts are provisioned by an admin. University identity integration is added through a provider adapter when the institution's protocol is known. Status and people ahead refresh periodically. |
| 3 | Staff limited to their assigned department and service window; multiple windows serve simultaneously | AUTH-05, STF-01, STF-02, STF-03, STF-04, STF-05, STF-06 | Admin configures 1–20 windows per office and assigns staff. A window handles one ticket at a time; staff complete or skip it before taking a break. |
| 4 | Public display shows each window's serving ticket, readiness, or break state | DSP-01, DSP-02 | No student identity. Periodic refresh. |
| 5 | Correct numbering and protection against simultaneous requests | TKT-02, TKT-03, TKT-04, STF-02 | Daily numbers per department, one active ticket per student, no duplicate numbers, no ticket called twice. Covered by concurrency tests. |
| 6 | Admin activity log | ADM-08 | Successful account, catalog, and ticket-changing transactions are auditable without recording passwords, tokens, or unnecessary personal data. |

Also in the MVP: NFR-01 to NFR-09, NFR-11 to NFR-14.

**Deferred past the MVP:** guest tickets (AUTH-06), priority tickets (STF-07), feedback (FBK-01, ADM-04), statistics (ADM-05), multi-campus support, complex opening-hours rules (TKT-07), and WebSocket delivery using Reverb. Until then, screens use simple periodic refresh. University SSO integration depends on the identity protocol the institution provides; it is not assumed to be available for the first pilot. The pilot uses admin-provisioned local accounts with generated temporary passwords; local password sign-in must be explicitly enabled in the deployment environment, while seeded demo accounts remain blocked in production.

**Not listed either way** (treated as after the MVP unless you decide otherwise): ticket history (TKT-08), the estimated wait part of TKT-05, pause the entire department queue (STF-06; per-window break is included), next tickets on the display (DSP-03) and sound (DSP-04).

**Added to the MVP:** cancel a ticket (TKT-06) and skip with recall (STF-04). Enrollment takes a student through several offices, and TKT-04 allows only one active ticket, so a student must be able to leave a queue and staff must be able to move past a no-show.

## Roles

Student, Staff (one department), Admin, Public screen (no login). See [00](00-project-overview.md).

## Functional requirements

### Accounts and access (AUTH)

| ID | Requirement | Pri | Acceptance criteria |
|----|-------------|-----|---------------------|
| AUTH-01 | Students do not self-register. An admin provisions their account, or an approved university identity integration matches them to an existing account. | M | Student ID and email are unique; only active, provisioned students can sign in. |
| AUTH-02 | A user can log in and log out. After login they land on the page for their role. | M | A student never sees staff or admin pages. |
| AUTH-03 | Staff accounts are created by an admin and assigned to exactly one department and at most one service window. | M | Queue-operating staff must have a window assigned within their department; a window cannot be assigned to multiple staff members. |
| AUTH-04 | The first admin is created once by the owner (for example with a Laravel seeder or Artisan command), never from a public page. | M | No page or URL in the app can create an admin. |
| AUTH-05 | Every page and every action checks the user's role on the server. | M | Opening a staff URL while logged in as a student is refused or redirected away, and the same action sent directly (without the page) is refused too. |
| AUTH-06 | A guest can take a ticket without an account. | C | Deferred past the MVP. TODO(you): decide in 00. If yes, a guest ticket is tied to the device and cannot be listed by others. |
| AUTH-07 | Admins never choose or retrieve user passwords. Local accounts receive a generated, one-time temporary password that must be changed at first sign-in; reset issues a new one-time password. If an external provider is enabled, the application does not collect that provider's password. | M | Passwords are hashed at rest, shown only on the immediate create/reset response with no-store caching, never logged, and never available to retrieve later. |
| AUTH-08 | Authentication is isolated behind a provider adapter so a university identity provider can be integrated without changing queue roles or ticket ownership. | S | Each enabled adapter is tested against that institution's documented protocol and maps a stable external subject to one local user. No universal compatibility is promised. |

### Tickets (TKT)

| ID | Requirement | Pri | Acceptance criteria |
|----|-------------|-----|---------------------|
| TKT-01 | A student chooses a department, then a service, and receives a ticket. | M | Only active departments and services are listed. |
| TKT-02 | Ticket code is the department prefix plus a 3-digit number (`RGS-007`). The number restarts at 001 each day (Asia/Manila) per department. | M | First ticket after midnight is 001. |
| TKT-03 | Ticket numbers are unique even when two students submit at the same moment. | M | Two simultaneous requests never get the same number (see test T-TKT-03). |
| TKT-04 | A student can hold only one active ticket (waiting or serving). | M | A second request shows the existing ticket instead. |
| TKT-05 | A student sees their ticket status, people ahead, and an estimated wait, updating automatically. | M | Status refreshes by itself on a short interval (initially every 5 to 10 seconds, to be tuned in the pilot), without the student reloading the page. The MVP shows status and people ahead; the estimate follows later. |
| TKT-06 | A student can cancel a waiting ticket. | M | A serving or completed ticket cannot be cancelled. |
| TKT-07 | A department can be closed outside its hours, and tickets cannot be taken while closed. | S | The page shows opening hours instead of the service list. Deferred past the MVP. |
| TKT-08 | A student can see their past tickets. | S | Newest first, with date, department, service and status. |

### Staff console (STF)

| ID | Requirement | Pri | Acceptance criteria |
|----|-------------|-----|---------------------|
| STF-01 | Staff see only their own department's queue. | M | No control or data from another department is reachable. The queue list refreshes automatically every 5 to 10 seconds without reloading the staff page. |
| STF-02 | Staff can call the next waiting ticket in their department (oldest first) at their assigned window. | M | Each window can have one serving ticket; staff at distinct windows can serve tickets simultaneously and never receive the same ticket. |
| STF-03 | Staff can mark the serving ticket as completed. | M | Completion time is recorded. |
| STF-04 | Staff can skip a no-show and recall a skipped ticket. | S | A skipped ticket can be recalled until the day ends. |
| STF-05 | Admin configures 1–20 named service windows per department and assigns each staff member to one window. | M | The student's called ticket and public display show the window that owns the ticket. |
| STF-06 | Staff can put their assigned window on break and return it to service. | M | A break is rejected while a ticket is serving; complete or skip first. The break affects only that window, not other windows in the department. |
| STF-07 | Priority tickets (seniors, PWD, pregnant) are called before normal ones. | C | Deferred past the MVP. TODO(you): decide in 00. |

### Public display (DSP)

| ID | Requirement | Pri | Acceptance criteria |
|----|-------------|-----|---------------------|
| DSP-01 | A page with no login shows, per department, the ticket now serving and the counter. | M | Readable from 5 meters on a TV. Shows no student name, ID number or email. |
| DSP-02 | The display updates by itself. | M | A new call appears within one refresh interval (initially 5 to 10 seconds). Simple periodic refresh is enough; real-time infrastructure is deferred. |
| DSP-03 | The display shows the next few waiting tickets. | S | Up to 5 per department. |
| DSP-04 | A short sound plays when a ticket is called. | C | Off by default, one click to enable (browsers block autoplay). |

### Admin (ADM)

| ID | Requirement | Pri | Acceptance criteria |
|----|-------------|-----|---------------------|
| ADM-01 | Admin can add, edit and deactivate departments (name and prefix; opening hours come with TKT-07). | M | Prefix is unique, 2 to 4 capital letters. |
| ADM-02 | Admin can add, edit and deactivate services per department. | M | |
| ADM-03 | Admin can create staff accounts and assign or change their department. | M | |
| ADM-04 | Admin can read feedback, filtered by department. | S | Deferred past the MVP. |
| ADM-05 | Admin sees basic stats: tickets served today, average wait, average service time, average rating. | S | Numbers come from recorded timestamps. Deferred past the MVP. |
| ADM-06 | Removing a department, service or user means deactivating it, so history is kept. | M | Old tickets still show the original names. |
| ADM-07 | Admin provisions and manages student accounts; staff account management remains restricted to staff roles and department assignment. | M | Student ID and email are unique; role changes are validated server-side and audited. |
| ADM-08 | Admin can read a searchable activity log of successful account, catalog, and ticket-changing transactions. | M | Each entry identifies actor (or system), action, target, time, and department where applicable. Log entries cannot be edited or deleted through the app; passwords, tokens and sensitive field values are never recorded. |

### Feedback (FBK)

| ID | Requirement | Pri | Acceptance criteria |
|----|-------------|-----|---------------------|
| FBK-01 | After a ticket is completed, the student can rate 1 to 5 and add a comment, once per ticket. | S | A second rating for the same ticket is rejected. Deferred past the MVP. |

## Non-functional requirements

| ID | Requirement | Pri |
|----|-------------|-----|
| NFR-01 | Mobile first: usable at 360px wide, no horizontal scrolling. | M |
| NFR-02 | Accessibility: text contrast of at least 4.5:1, keyboard operable, visible focus, labels on every input, status changes announced to screen readers. | M |
| NFR-03 | Every screen that shows data handles loading, empty, error, offline and slow states. | M |
| NFR-04 | Laravel application on a relational SQL database (MySQL by default, MariaDB if that is what the university's host provides). Schema changes are made with Laravel migrations. | M |
| NFR-05 | Local passwords are stored only as hashes, transmitted only over HTTPS, and never written to logs or audit metadata. External-provider passwords are never collected by this app. | M |
| NFR-06 | User-supplied text is never inserted with `innerHTML` and never output unescaped in Blade views. | M |
| NFR-07 | Works on current Chrome, Edge, Firefox and Safari. | M |
| NFR-08 | Collect the minimum personal data (name, student ID, university email, course), and show staff only what the service needs. | M |
| NFR-09 | "Today" and the daily reset use the Asia/Manila time zone. | M |
| NFR-10 | The version number is visible in the page footer. | S |
| NFR-11 | All input is validated on the server. Browser-side checks are only a convenience. | M |
| NFR-12 | The application can be hosted on a server owned and run by the university. | M |
| NFR-13 | Audit data is append-only through the application and excludes passwords, authentication tokens, and unnecessary personal data. | M |
| NFR-14 | Queue screens remain usable with periodic refresh if real-time delivery is unavailable. | M |

## User stories (summary)

- As a student, I want to take a ticket from my phone so that I do not stand in line.
- As a student, I want to see how many people are ahead so that I know when to come.
- As a staff member, I want to call the next person with one button so that I can focus on the service.
- As the office, I want a public screen so that people know whose turn it is.
- As an admin, I want to manage departments and staff so that the system matches how the offices work.

## Traceability

Every requirement maps to a roadmap version in [09-roadmap.md](09-roadmap.md) and to test cases in [06-test-plan.md](06-test-plan.md).
