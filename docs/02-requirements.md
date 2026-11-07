# 02. Requirements

| Version | Status | Last updated |
|---------|--------|--------------|
| 0.3 | Draft | 2026-11-07 |

Priority uses MoSCoW: **M**ust, **S**hould, **C**ould. "Must" items define v1.0.0. Each requirement has a stable ID used in issues, tests and commits. The first release for real users is a smaller MVP (a one-department pilot), defined in [MVP scope](#mvp-scope-pilot).

## MVP scope (pilot)

The first release for real users is a pilot that starts with **one department**. The departments in scope for now are the Admission, Registrar and Accounting offices (the enrollment process). TODO(you): which one starts? Only what is listed under "Included" is needed for it.

**Included**

| # | MVP item | Requirements | Pilot notes |
|---|----------|--------------|-------------|
| 1 | Admin setup for one department, its services and staff | ADM-01 (name and prefix), ADM-02, ADM-03, ADM-06, AUTH-03, AUTH-04 | One department at first. |
| 2 | Student sign-in, ticket creation, ticket status viewing | AUTH-01, AUTH-02, TKT-01, TKT-05, TKT-06 | University account sign-in (seeded records until it is connected). Status and people ahead, refreshed periodically. A waiting ticket can be cancelled. |
| 3 | Staff limited to their assigned department, call next and complete | AUTH-05, STF-01, STF-02, STF-03, STF-04 | Department checks are enforced on the server. Staff can also skip a no-show and recall it. |
| 4 | Public display of the ticket being served | DSP-01, DSP-02 | No student identity. Periodic refresh. |
| 5 | Correct numbering and protection against simultaneous requests | TKT-02, TKT-03, TKT-04, STF-02 | Daily numbers per department, one active ticket per student, no duplicate numbers, no ticket called twice. Covered by concurrency tests. |

Also in the MVP: NFR-01 to NFR-09, NFR-11 and NFR-12.

**Deferred past the MVP:** guest tickets (AUTH-06), priority tickets (STF-07), feedback (FBK-01, ADM-04), statistics (ADM-05), multi-campus support, complex opening-hours rules (TKT-07), and real-time infrastructure. Until then, screens use simple periodic refresh.

**Not listed either way** (treated as after the MVP unless you decide otherwise): ticket history (TKT-08), the estimated wait part of TKT-05, counter label (STF-05), pause queue (STF-06), next tickets on the display (DSP-03) and sound (DSP-04).

**Added to the MVP:** cancel a ticket (TKT-06) and skip with recall (STF-04). Enrollment takes a student through several offices, and TKT-04 allows only one active ticket, so a student must be able to leave a queue and staff must be able to move past a no-show.

## Roles

Student, Staff (one department), Admin, Public screen (no login). See [00](00-project-overview.md).

## Functional requirements

### Accounts and access (AUTH)

| ID | Requirement | Pri | Acceptance criteria |
|----|-------------|-----|---------------------|
| AUTH-01 | Students sign in with their university account and do not register themselves. Student records (name, student ID, university email, course) come from the university. Seeded fake records are used until the university sign-in is connected ([ADR-0002](decisions/ADR-0002-university-sign-in.md)). | M | Student ID and email are unique, and a person who is not in the student records cannot sign in. |
| AUTH-02 | A user can log in and log out. After login they land on the page for their role. | M | A student never sees staff or admin pages. |
| AUTH-03 | Staff accounts are created by an admin and assigned to exactly one department. | M | A staff account without a department cannot be saved. |
| AUTH-04 | The first admin is created once by the owner (for example with a Laravel seeder or Artisan command), never from a public page. | M | No page or URL in the app can create an admin. |
| AUTH-05 | Every page and every action checks the user's role on the server. | M | Opening a staff URL while logged in as a student is refused or redirected away, and the same action sent directly (without the page) is refused too. |
| AUTH-06 | A guest can take a ticket without an account. | C | Deferred past the MVP. TODO(you): decide in 00. If yes, a guest ticket is tied to the device and cannot be listed by others. |

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
| STF-02 | Staff can call the next waiting ticket in their department (oldest first). | M | Two staff calling at once never receive the same ticket. |
| STF-03 | Staff can mark the serving ticket as completed. | M | Completion time is recorded. |
| STF-04 | Staff can skip a no-show and recall a skipped ticket. | S | A skipped ticket can be recalled until the day ends. |
| STF-05 | Staff set a counter label (for example "Window 2") shown to students and on the display. | S | The label appears next to the serving ticket. |
| STF-06 | Staff can pause the queue (for example, lunch break). | S | Students see "Queue paused". |
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
| NFR-05 | Passwords are stored only as hashes (Laravel's hashing) and are never logged or handled in plain text. | M |
| NFR-06 | User-supplied text is never inserted with `innerHTML` and never output unescaped in Blade views. | M |
| NFR-07 | Works on current Chrome, Edge, Firefox and Safari. | M |
| NFR-08 | Collect the minimum personal data (name, student ID, university email, course), and show staff only what the service needs. | M |
| NFR-09 | "Today" and the daily reset use the Asia/Manila time zone. | M |
| NFR-10 | The version number is visible in the page footer. | S |
| NFR-11 | All input is validated on the server. Browser-side checks are only a convenience. | M |
| NFR-12 | The application can be hosted on a server owned and run by the university. | M |

## User stories (summary)

- As a student, I want to take a ticket from my phone so that I do not stand in line.
- As a student, I want to see how many people are ahead so that I know when to come.
- As a staff member, I want to call the next person with one button so that I can focus on the service.
- As the office, I want a public screen so that people know whose turn it is.
- As an admin, I want to manage departments and staff so that the system matches how the offices work.

## Traceability

Every requirement maps to a roadmap version in [09-roadmap.md](09-roadmap.md) and to test cases in [06-test-plan.md](06-test-plan.md).
