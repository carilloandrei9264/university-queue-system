# 08. Versioning and workflow

| Version | Status | Last updated |
|---------|--------|--------------|
| 0.2 | Draft | 2026-11-07 |

## Semantic Versioning

Format `MAJOR.MINOR.PATCH` (for example `0.4.1`).

| Part | Increase when | Example |
|------|---------------|---------|
| MAJOR | A breaking change for users or data that needs migration | `1.0.0` to `2.0.0` |
| MINOR | A new feature, backward compatible | `0.4.0` to `0.5.0` (public display added) |
| PATCH | A bug fix only | `0.4.0` to `0.4.1` |

While the major number is `0`, the project is in development. Each roadmap milestone is a MINOR version (`0.1.0`, `0.2.0`, ...). `1.0.0` is the first release that meets every **Must** requirement. Pre-release builds may use `-beta.1` (for example `0.9.0-beta.1`).

Three version numbers exist and are tracked separately:

| What | Where | Rule |
|------|-------|------|
| App version | `APP_VERSION` in the application config, shown in the footer, Git tag `vX.Y.Z` | Follows the rules above |
| Schema version | Header of `docs/04-database-schema.md` | Increase on every data model change, with a migration note and a new Laravel migration file |
| Doc version | Header of each doc | Increase on meaningful edits (0.1, 0.2, ... 1.0 when approved) |

## Branching: GitHub Flow

- `main` is always releasable and always matches what is deployed.
- Work happens on short-lived branches from `main`, named `type/short-description`:
  `feat/student-ticket-flow`, `fix/duplicate-ticket-number`, `docs/update-schema`, `chore/add-gitignore`.
- Open a pull request even when working alone. It gives you a checklist, a history and a place to link issues.
- Squash merge, so each pull request becomes one clear commit on `main`.
- Delete the branch after merging.

## Commit messages: Conventional Commits

Format: `type(scope): short description` in the present tense, under 72 characters.

| Type | Use for | Example |
|------|---------|---------|
| `feat` | New feature | `feat(tickets): create ticket with daily number` |
| `fix` | Bug fix | `fix(staff): scope call next to own department` |
| `docs` | Documentation only | `docs(schema): add dailyCounters` |
| `style` | Formatting, no logic change | `style(css): align button spacing` |
| `refactor` | Restructure, no behavior change | `refactor(ui): extract state box helper` |
| `test` | Tests | `test(domain): cover status transitions` |
| `chore` | Tooling, config, housekeeping | `chore: add editorconfig` |

A breaking change adds `!` (for example `feat(schema)!: rename tickets.status values`) and an explanation in the body.

Scopes to use: `auth`, `tickets`, `staff`, `display`, `admin`, `feedback`, `ui`, `data`, `domain`, `schema`, `docs`.

## Issues and milestones

- One GitHub **milestone** per version (`v0.3.0`).
- One **issue** per small piece of work, using the templates in `.github/ISSUE_TEMPLATE`. Put the requirement ID in the title or body.
- Labels: `feature`, `bug`, `docs`, `blocker`, `major`, `minor`.
- A pull request says `Closes #12` so the issue closes when it merges.

## The cycle for each version

```
Plan  ->  Design  ->  Build  ->  Test  ->  Release  ->  Review
 |          |           |         |          |            |
roadmap   refine      feature   test plan   tag +        what went
scope     docs 03-05  branches  + checklist changelog   well, update
                                                         09-roadmap
```

1. **Plan:** pick the scope from [09](09-roadmap.md), create the milestone and issues.
2. **Design:** update the design docs for this slice. Write the test cases first.
3. **Build:** small branches, small pull requests, update docs in the same pull request.
4. **Test:** run the unit tests and the manual checklist.
5. **Release:** follow the steps in [07](07-deployment.md).
6. **Review:** five lines in the roadmap: what shipped, what slipped, what to change.

## Definition of Done (a piece of work is done when)

- It meets the acceptance criteria of its requirement.
- It works at 360px and on desktop.
- Loading, empty, error, offline and slow states are handled.
- No user text goes through `innerHTML` or unescaped Blade output.
- Tests are written and pass, and the checklist is updated.
- Schema changes ship as a new migration, and migrations that have already run on a shared database are never edited.
- Docs and `CHANGELOG.md` are updated.
- The pull request is reviewed (self-review with the template counts when working alone).

## Hotfixes

For a bug in the released version: branch `fix/...` from `main`, fix, test, release as a PATCH (`0.4.1`). Do not mix new features into a hotfix.

## Changelog rules

- Write for users, not for developers: "Staff can now skip a no-show", not "added skip function".
- Group entries under `Added`, `Changed`, `Fixed`, `Removed`, `Security`.
- Add entries to **Unreleased** in each pull request. Move them under the version at release time.
