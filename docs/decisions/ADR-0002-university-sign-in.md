# ADR-0002: University sign-in and seeded student records

| Status | Date |
|--------|------|
| **Accepted** (university integration remains an option; interim account provisioning is superseded by [ADR-0003](ADR-0003-account-provisioning-and-identity.md)). | 2026-11-07 |

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

**B remains the target direction; admin-provisioned accounts replace seeded-only records as the interim.** The detailed provisioning and adapter decision is [ADR-0003](ADR-0003-account-provisioning-and-identity.md).

- Students do not self-register. An admin creates or imports an account record, and sign-in is performed by the configured authentication method.
- Seeded fake records and demo logins remain for local development and staging only.
- Staff and admin accounts remain locally managed for role and department assignment; production authentication must use a reviewed credential method.
- Roles, department assignment, policies and tickets are stored and enforced by the application, so they do not depend on how sign-in is done. The sign-in step is kept isolated so the university's method can be added without changing them.

## Consequences

- There is no self-registration page. An active admin-provisioned account is required for sign-in.
- The repository holds fake data only. If the pilot uses real student records, the university loads them onto the server and they are never committed to git.
- Seeded demo passwords (hashed) exist only outside production.
- Privacy: keep to name, student ID, university email and course (NFR-08), and show staff only what the service needs.
- Still unknown, to confirm with the university: which sign-in method it provides, how student records are supplied and kept up to date, and what local credential issuance policy is acceptable if SSO is unavailable.
- If the university's method needs an extra Laravel package or server setup, make that a separate decision when the method is known. Nothing is assumed now.
- The interim seeded-record approach is replaced by admin-provisioned student and staff accounts. See [ADR-0003](ADR-0003-account-provisioning-and-identity.md) for identity adapters, local credential safeguards, and the distinction between authentication and student-data imports.
