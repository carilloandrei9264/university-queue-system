# ADR-0003: Admin-provisioned accounts and university identity adapters

| Status | Date |
|--------|------|
| **Accepted** | 2026-11-08 |

## Context

The project cannot yet obtain the university's sign-in or student-data interface details. The pilot still needs a controlled way to create student and office accounts. Later institutions may use different identity providers and data interfaces.

"API" describes an interface, not a single authentication protocol. EDI (Electronic Data Interchange) is generally a business-data exchange format/process, not a user sign-in protocol. Treating either as a universal login method would promise compatibility the project cannot guarantee.

## Options

### A. Require one specific university sign-in provider before development

- Pros: avoids a local password system.
- Cons: blocks development and depends on an IT decision that is not available yet.

### B. Admin-provision local users and hard-code a university-specific login later

- Pros: accounts can be prepared for the pilot.
- Cons: tightly couples authentication to one institution and makes later integrations risky.

### C. Admin-provision local users and isolate authentication behind an adapter

- Pros: the queue uses stable local user IDs and roles; one institution's provider can be integrated without changing queue rules.
- Cons: each provider still needs its own configuration, security review, and integration tests. Local credential issuance needs a separate safe process if no provider is available.

## Decision

**C.**

- An admin provisions and maintains student and staff account records. Student records include only the required fields; staff receive one department assignment. Users do not self-register.
- Authentication is an adapter boundary. An adapter may support the institution's documented protocol, such as OpenID Connect/OAuth 2.0, SAML 2.0, or LDAP, only after requirements and infrastructure are confirmed. These are examples, not a promise to support every provider automatically.
- Student-information imports/synchronization (for example, an API or approved file export) are separate from sign-in. EDI is not treated as an authentication protocol.
- External identity uses a stable provider subject mapped to one local user. Email alone is not used as the durable identity key; local role and department remain controlled by the app's admin.
- Local accounts use an app-generated temporary password shown once after creation or reset; the user must change it at first sign-in. Admins cannot choose or retrieve passwords, and the activity log never records credentials. Share the temporary password only through a secure channel. The deployment must explicitly enable local-password sign-in; seeded demo accounts are marked and cannot sign in in production.
- The first pilot department is Admission (AMS). Confirm its service list and account-provisioning procedures with the office before onboarding real users.

## Consequences

- Add student account administration and an authentication adapter requirement to the pilot scope.
- Keep seeded demo accounts for local development and staging only.
- Confirm with university IT the identity provider, protocol, stable user identifier, student-data source, account lifecycle, and hosting requirements before choosing a package or implementation.
- An unfamiliar proprietary method may require custom work or may not be supportable; the deployment must be evaluated rather than assumed compatible.
