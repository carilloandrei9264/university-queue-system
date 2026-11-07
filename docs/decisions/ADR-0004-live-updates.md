# ADR-0004: Polling first, Reverb when justified

| Status | Date |
|--------|------|
| **Accepted** | 2026-11-08 |

## Context

Student, staff, and public display screens need current queue information. The application currently refreshes read-only queue fragments every five seconds. Laravel Reverb could push changes over WebSockets, but the university's network, reverse proxy, firewall, TLS, and process-supervision support are unknown.

## Options

### A. Replace polling with Reverb now

- Pros: low-latency queue updates and fewer repeated reads when users are connected.
- Cons: adds a persistent service and operational dependencies before the pilot has shown a need; WebSockets may be blocked or difficult to operate on university hosting.

### B. Keep polling and evaluate Reverb after the pilot

- Pros: simple deployment, current implementation works without a worker or WebSocket service, and user impact can be measured first.
- Cons: updates are delayed by up to one refresh interval and cause periodic requests.

## Decision

**B.** Keep five-second polling as the MVP and fallback. Reverb is the preferred Laravel-native option if the pilot demonstrates that lower latency is needed and the host can reliably operate it.

If Reverb is introduced:

- Queue writes remain authoritative in the SQL database, and events publish only after a successful transaction commits.
- Student and staff channels are private and authorized by user/department. Public display events contain ticket codes and department only.
- Reconnects perform a fresh read; polling remains available as fallback. Queue actions must not depend on Reverb availability.

## Consequences

- No Reverb service, deployment, or package is required for the initial pilot.
- Ask the university IT team about WebSocket process support, TLS/proxy configuration, firewall rules, monitoring, and restart behavior before planning the integration.
- Measure refresh latency, request load, and user feedback during the pilot before adding real-time infrastructure.
