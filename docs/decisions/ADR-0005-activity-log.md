# ADR-0005: Append-only business activity log

| Status | Date |
|--------|------|
| **Accepted** | 2026-11-08 |

## Context

Administrators need to understand who changed accounts, office configuration, and queue tickets, and when. A log is useful for operational accountability, but indiscriminate request logging could retain passwords, identity tokens, or unnecessary student data.

## Options

### A. No activity log

- Pros: no additional storage or privacy controls.
- Cons: administrators cannot investigate ordinary business changes.

### B. Log every request and database query

- Pros: broad diagnostic detail.
- Cons: excessive volume and a high risk of capturing credentials, personal data, and irrelevant polling reads.

### C. Log successful business mutations with allow-listed details

- Pros: records useful accountability events while limiting sensitive data.
- Cons: each business action needs a deliberate event name and metadata policy; a retention period must be agreed.

## Decision

**C.** Keep an admin-visible, append-only business activity log for successful account, department, service, and ticket-changing actions. Write each event in the same SQL transaction as the successful change.

Each record contains the actor (or system), action, target, timestamp, and department where applicable. Metadata is allow-listed and limited to non-sensitive values or changed field names. Never log passwords, tokens, cookies, full request bodies, or unnecessary personal data. Polling reads are not activity events. Authentication failures belong in separately protected security monitoring, not this business activity view.

## Consequences

- Add an `audit_logs` table and a read-only admin screen.
- Application routes expose no update/delete operation for audit records; database administration and backups remain governed by university operations.
- Admin access to the log is authorized server-side.
- Confirm retention, archival, and access policy with the university before real student data is used.
