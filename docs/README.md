# Documentation index

These documents follow the SDLC phases. They live in the repo and change together with the code.

| # | Document | SDLC phase | Answers the question | Fill in when |
|---|----------|------------|----------------------|--------------|
| 00 | [Project overview](00-project-overview.md) | Planning | What are we building, for whom, and what is out of scope? | Before anything else |
| 01 | [Old system audit](01-old-system-audit.md) | Analysis | What went wrong last time? | Done (review it) |
| 02 | [Requirements](02-requirements.md) | Analysis | What must the system do? | Before design |
| 03 | [Architecture](03-architecture.md) | Design | How is the code organized? | Before coding |
| 04 | [Database schema](04-database-schema.md) | Design | What data do we store, and who may touch it? | Before coding |
| 05 | [UI/UX design](05-ui-ux-design.md) | Design | What do the screens look like and how do people move through them? | Before building each screen |
| 06 | [Test plan](06-test-plan.md) | Testing | How do we know it works? | Write cases before each version, run them at the end |
| 07 | [Deployment](07-deployment.md) | Deployment | How does it get online? | Before the first release |
| 08 | [Versioning and workflow](08-versioning-and-workflow.md) | All | How do we name versions, branches and commits? | Read once, then follow |
| 09 | [Roadmap](09-roadmap.md) | Planning | What ships in which version? | Update after every release |
| 10 | [Admission pilot next steps](10-next-steps.md) | Deployment | What remains before a limited Admission pilot? | Before preparing a pilot |
| - | [Decisions (ADRs)](decisions/) | Design | Why did we choose X over Y? | Whenever a big choice is made |

Current decisions: [backend](decisions/ADR-0001-backend-approach.md), [university sign-in direction](decisions/ADR-0002-university-sign-in.md), [accounts and identity adapters](decisions/ADR-0003-account-provisioning-and-identity.md), [polling versus Reverb](decisions/ADR-0004-live-updates.md), and [activity log](decisions/ADR-0005-activity-log.md).

## How the process works

Use a light, iterative SDLC. Do a short upfront pass, then repeat a small cycle for every version.

1. **Phase 0, once (v0.0.0):** fill in 00 and 02, review 01, confirm the open hosting questions in 00 and 07 ([ADR-0001](decisions/ADR-0001-backend-approach.md) is accepted), then draft 03, 04 and 05. Keep each document short and mark gaps as `TODO`.
2. **Each version (v0.1.0, v0.2.0, ...):** plan the scope from [the roadmap](09-roadmap.md), refine the design docs for that slice, build on feature branches, test against 06, release with a tag and a changelog entry, then update the roadmap.
3. **Rule:** if code and docs disagree, fix whichever is wrong in the same pull request.

## Document conventions

- Every document starts with a header table: version, status (Draft, Review, Approved), last updated.
- Requirements have stable IDs (for example `TKT-02`). Issues, pull requests, tests and commits refer to those IDs.
- Mark open questions as `TODO(you)` so they are easy to search for.
