# ADR-0002: University sign-in and seeded student records

| Status | Date |
|--------|------|
| **Accepted** (direction). The integration method is still open. | 2026-11-07 |

## Context

Students already have university accounts. The system should not create a second set of passwords for them. The connection to the university's account system will be made on the university server, and what the university provides is not known yet. Until then the system needs student records to work with: name, student ID, university email and course.

## Options

### A. Self-registration with its own passwords

The old system's approach, and how `AUTH-01` was first written.

- Pros: no dependency on the university.
- Cons: a second password for every student, anyone can register with any student ID, and the data is duplicated.

### B. Sign in with the university account

Student records come from the university.

- Pros: one account for the student, trusted identity, and the university controls who can sign in.
- Cons: depends on what the university provides and on its timeline.

### C. Seeded records and demo logins only

- Pros: works immediately for development and demos.
- Cons: not suitable for real use.

## Decision

**B, with C as the interim.**

- Students sign in with their university account and do not register themselves.
- Until the connection exists, seeded fake records (name, student ID, university email, course) and seeded demo logins are used on local and staging only.
- Staff and admin also sign in this way. An admin assigns their role and department.
- Roles, department assignment, policies and tickets are stored and enforced by the application, so they do not depend on how sign-in is done. The sign-in step is kept isolated so the university's method can be added without changing them.

## Consequences

- There is no registration page, and `AUTH-01` is rewritten accordingly.
- A person who is not in the student records cannot sign in.
- The repository holds fake data only. If the pilot uses real student records, the university loads them onto the server and they are never committed to git.
- Seeded demo passwords (hashed) exist only outside production.
- Privacy: keep to name, student ID, university email and course (NFR-08), and show staff only what the service needs.
- Still unknown, to confirm with the university: which sign-in method it provides, how pilot users sign in before it is connected, and how student records are supplied and kept up to date.
- If the university's method needs an extra Laravel package or server setup, make that a separate decision when the method is known. Nothing is assumed now.
