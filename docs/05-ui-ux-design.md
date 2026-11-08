# 05. UI/UX design

| Version | Status | Last updated |
|---------|--------|--------------|
| 0.8 | In progress | 2026-11-08 |

## Design goals

1. **One obvious thing per screen.** A student wants a ticket. Staff want "Call next". The display wants one big number.
2. **Phone first.** Students use phones in a hallway, often on weak Wi-Fi.
3. **Calm under pressure.** Clear states, plain words, no `alert()` pop-ups.
4. **Readable from far away** on the public display.

## Current implementation

The shared responsive stylesheet is `public/css/app.css`. It now styles the landing, sign-in and password-change screens, student and staff queues, the admin area, and the public display chooser and board. The visual system uses NU blue and gold, responsive card layouts, labeled controls, visible keyboard focus, queue status badges, high-contrast large display codes, and reduced-motion handling.

The interface includes empty states for queues and admin lists, focused validation summaries, success confirmations, permission-denied and expired-session pages, and no-result messages for student and activity-log searches. Queue polling announces loading, slow, offline, retryable error, successful update, and expired-session outcomes while keeping the last rendered queue visible. The shared `public/js/ui-states.js` helper adds offline banners and form-submission/slow-response feedback. Submit feedback appears in the clicked button rather than creating a separate row that can disrupt header layouts; GET/search forms are not treated as mutations. It does not cache data offline; the displayed queue is only the last response already loaded in the page.

Loading feedback is intentionally limited to in-progress form submissions and queue refreshes; server-rendered page navigation does not show a skeleton because page content arrives with the initial response.

The admin overview now links directly to the existing office, service, staff, student, and activity-log pages. The admin header groups the signed-in user's name and role in an accessible profile menu with sign-out inside the menu. Activity filters keep the action selector beside the search field and its Filter/Clear controls, with a stacked layout on narrow screens. The sign-in page uses a responsive branded panel beside the form and the supplied campus photo at `public/images/campus-login.webp`. No unimplemented queue metrics or controls have been added.

Admin catalog and account pages use collapsed create forms, searchable lists, and compact expandable records instead of rendering a full edit form for every item. Lists are paginated (15 records per page) to keep long directories manageable. The admin header stays visible while scrolling. The centered content width is retained while gaps within the admin content are reduced.

## Current design review (old NU Queue-It)

The rebuild keeps the old visual identity and fixes how it works and how it is built. Screenshots are in [`assets/old-design/`](assets/old-design/). They were rendered with no backend and no internet, so web fonts and icon fonts fall back to defaults. The login, register and admin pages are PHP and could not be rendered here, so only their markup and CSS were reviewed.

### What to keep

| Keep | Why |
|------|-----|
| **NU blue (`#003594`) with gold (`#ffb81c`)** | It is the university's identity and is already recognizable. Gold on NU blue has a 6.3:1 contrast ratio, white on NU blue 10.8:1. |
| **Landing page: blue panel with the bulldog and "QUEUE IT", campus photo on the other half** | Strong first impression, instantly says "NU". |
| **Blue header with a gold stripe** | Consistent across every page. |
| **Card layout and the large queue number card** | The ticket number being the biggest thing on the student screen is right. |
| **Department prefixes in ticket codes (AMS, RGS, LIB...)** | Easy to read and say out loud. |

### What to improve

| # | Finding in the old design | Improvement |
|---|---------------------------|-------------|
| U1 | **Not responsive.** There are 0 media queries. At 390px wide the student dashboard is 443px wide and the office dashboard is 414px, so both scroll sideways on a phone. Students are mainly phone users. | Mobile first layout, stacked cards on small screens (NFR-01). |
| U2 | **Empty and error states are placeholders.** Screens show `N/A` and `YYYY-MM-DD` instead of a real empty state, and a failed load is one line of plain text. | Explicit loading, empty, error, offline, slow, ready, no-result, permission, session, validation, and success states. |
| U3 | **Colors have sprawled.** About 25 colors, with 5 blues (`#003594`, `#004aad`, `#003da5`, `#007bff`, `#bbdefb`), 3 golds (`#ffb81c`, `#f2c200`, `#d9a500`), and a cyan card (`#e0f7fa`) that is not part of the brand. No CSS variables. | One token file with a small scale built from NU blue and gold. |
| U4 | **Some contrast fails.** White on the red Cancel button is 3.82:1 and the grey footnote text `#aaa` is 2.32:1 (4.5:1 needed). | Darker red and darker neutral text (see tokens). |
| U5 | **Fixed pixel sizes.** All 11 font sizes are in `px` (14 to 75), with no scale, so text ignores the user's browser font setting. | `rem` units and a type scale. |
| U6 | **Almost no focus styling.** 22 hover rules but only 2 focus rules. Keyboard users cannot see where they are. | A visible focus ring on every interactive element. |
| U7 | **Icons depend on a CDN font.** The menu icon is the Material Symbols font, which shows the word "menu" when it fails to load. | Inline SVG icons, no external font. |
| U8 | **The student screen shows personal data nobody needs.** Name, ID number and email are printed on the ticket card. | Show only ticket details: code, service, status, people ahead, estimated wait (NFR-08). |
| U9 | **Hard-coded wording.** "Cashier Window" appears for every department. | Counter label per department ("Window 2"). |
| U10 | **The staff screen has a single "Complete" button.** Calling the next person happens as a side effect of completing, and there is no skip. | Explicit **Call next**, **Complete** and **Skip** (STF-02 to STF-04). |
| U11 | **The login form mixes everything.** An "Admin Mode" checkbox and an account type menu (student, employee, guest) sit on one form. | One login form. The role comes from the account. Guest, if kept, is a separate "Continue as guest" link (AUTH-06). |
| U12 | **Layout details.** Headings sit tight against the cards below them, large empty areas fill the right column, and the campus photo is only 1024 x 461 pixels, so it looks soft on large screens. | Spacing scale, content-based card heights, a darker overlay on the photo, and a higher resolution photo if the university provides one. |
| U13 | **Department chooser is bare.** A title and a list built by script. | Department cards with prefix badge, open or closed state, and the current number of people waiting. |

### Modernizing without losing the identity

- Keep the NU blue and gold, but use blue mostly for structure (header, buttons, headings) and gold only for small accents and highlights, so the gold stays special.
- Use white and very light blue-grey surfaces with soft shadows and rounded corners (12px), instead of the current cyan cards.
- Make the ticket code the hero of the student screen: very large, centered, with a clear status badge.
- Offer a dark mode (the display board benefits most, a dark screen is easier on the eyes in a hallway).
- Add small, purposeful motion only (status change, state transitions) and honor `prefers-reduced-motion`.

TODO(you): The bulldog mascot and university building photo are university assets. Confirm you are allowed to use them, and ask the university for official brand colors or guidelines.

## Personas

| Persona | Context | Needs |
|---------|---------|-------|
| **Student (Mia, 19)** | Phone, hallway, slow campus Wi-Fi | Get a ticket in under 30 seconds, know when to go to the window |
| **Staff (Mr. Cruz)** | Desktop at the counter, many people waiting | One-click calling, never touches other departments |
| **Admin (IT office)** | Desktop, occasional | Set up departments and staff without developer help |
| **Waiting visitor** | Looks at the TV from across the room | Sees which number is being served and where |

## Sitemap

```
/ (landing)
 |- /login
 |- /student      (student)
 |- /staff        (staff)
 |- /admin        (admin)
 '- /display      (public, no login)
```

## Key user flows

**Student takes a ticket**
1. Log in. 2. Pick department. 3. Pick service. 4. Confirm. 5. Ticket screen with code, people ahead, estimated wait. 6. Status turns to "Now serving at Window 2". 7. After completion, a rating prompt appears (with feedback, after the MVP).

**Student enrolls across offices**
Enrollment takes a student through several offices, one after another. A student may hold only one active ticket (TKT-04), so they finish or cancel the ticket at one office before taking one at the next. TODO(you): confirm the usual order (for example Admission, then Registrar, then Accounting) and whether it differs for new and continuing students.

**Staff serves the queue**
1. Log in and land on the console for their department. 2. Set a counter label once. 3. Press **Call next**. 4. Serve. 5. Press **Complete** (or **Skip** if absent). 6. Repeat.

**Admin sets up**
1. Log in. 2. Add a department with its prefix (opening hours come later). 3. Add services. 4. Create student and staff accounts; assign each staff account to one department. 5. Review the activity log for recent successful changes.

## Screen inventory

| Screen | Role | Main components | Requirements |
|--------|------|-----------------|--------------|
| Landing | Anyone | Title, three buttons (Take a ticket, Staff login, View display) | |
| Login | Anyone | Sign-in using the configured local demo or university identity provider; inline errors | AUTH-01, AUTH-02, AUTH-07 |
| Student: choose | Student | Department cards, service list | TKT-01, TKT-07 |
| Student: ticket | Student | Big ticket code, status badge, people ahead, estimate, Cancel | TKT-04 to TKT-06 |
| Student: history | Student | List of past tickets | TKT-08 |
| Student: rate | Student | 5 stars, comment | FBK-01 |
| Staff console | Staff | Now serving panel, waiting list, Call next, Complete, Skip, counter label, pause | STF-01 to STF-06 |
| Display board | Public | One tile per department: code, counter, next few | DSP-01 to DSP-04 |
| Admin: catalog | Admin | Tables for departments and services with add and edit forms | ADM-01, ADM-02, ADM-06 |
| Admin: staff | Admin | Staff table, create form | ADM-03 |
| Admin: students | Admin | Searchable student accounts, create/edit/deactivate, issue a one-time generated temporary password on create/reset | ADM-07, AUTH-07 |
| Admin: activity | Admin | Filterable, read-only chronological business activity log | ADM-08 |
| Admin: feedback and stats | Admin | Feedback list, stat tiles | ADM-04, ADM-05 |

**MVP screens:** Landing, Login, Student: choose, Student: ticket, Staff console, Display board, Admin: catalog, Admin: students, Admin: staff, Admin: activity. After the MVP: Student: history, Student: rate, Admin: feedback and stats.

## Wireframes (low fidelity)

Student ticket screen (phone):

```
+---------------------------+
| NU Queue-It        [menu] |
+---------------------------+
|  Registrar - Enrollment   |
|                           |
|          RGS-007          |
|        [ WAITING ]        |
|                           |
|  People ahead:   4        |
|  Estimated wait: ~20 min  |
|                           |
|  [   Cancel ticket   ]    |
+---------------------------+
```

Staff console (desktop):

```
+--------------------------------------------------+
| Registrar            Window: [Window 2 v] [Pause] |
+------------------------+-------------------------+
| NOW SERVING            | WAITING (5)             |
|   RGS-004              |  RGS-005  Enrollment    |
|   Transcript Request   |  RGS-006  Enrollment    |
|                        |  RGS-007  Transcript    |
| [Complete] [Skip]      |  ...                    |
|                        |                         |
| [      Call next     ] |                         |
+------------------------+-------------------------+
```

Display board (TV):

```
+--------------------------------------------------+
| NU QUEUE-IT                              10:42 AM |
+------------------+------------------+------------+
| ADMISSION        | REGISTRAR        | ACCOUNTING |
|   AMS-012        |   RGS-004        |  ACC-009   |
|   Window 1       |   Window 2       |  Window 1  |
| Next: 013 014    | Next: 005 006    | Next: 010  |
+------------------+------------------+------------+
```

The wireframes show the full design. The MVP leaves out the Pause button, Skip, the counter label, and the "Next" tickets on the display board.

## Screen states (every data screen)

| State | What the user sees |
|-------|-------------------|
| Loading | An announced progress message for form submissions and queue refreshes |
| Empty | Short message and the next action ("No tickets waiting") |
| Error | Plain-language validation, access, or refresh error with a useful next step |
| Offline | Connection banner; the currently rendered queue stays visible and polling resumes after reconnect |
| Slow | After 4 seconds, a slow-network message while preserving the current page data |
| No search result | Explanation and a clear-filter/search action |
| Permission denied | Dedicated 403 page explaining the account lacks access |
| Session expired | Dedicated 419 page asks the user to sign in again |
| Form validation | Error summary is announced and focused; entered values are retained on account-creation forms |
| Success | Confirmation uses a status announcement after a completed action or successful refresh |
| Ready | The current content and next action |

Queue polling retries on its existing five-second interval. Offline behavior preserves the current rendered response rather than offering an offline cache or marking cached records as fresh.

## Design system

Keep design tokens and shared component rules together in `public/css/app.css` for this no-build-step MVP. Avoid hard-coded colors in page templates. Values are proposals built from the old palette; replace them with official brand values if the university has them.

### Color tokens

| Token | Light | Use | Contrast check |
|-------|-------|-----|----------------|
| `--blue-900` | `#00205f` | Dark header, display board background | white on it: 15.3:1 |
| `--blue-700` | `#003594` | **NU blue**, header, primary buttons, headings | white on it: 10.8:1 |
| `--blue-500` | `#2f6bd6` | Links on white, focus ring | on white: 5.0:1 |
| `--blue-100` | `#e6edfa` | Tinted surfaces, replaces the cyan cards | |
| `--gold-500` | `#ffb81c` | **NU gold**, accents, highlights, active tab line | on NU blue: 6.3:1 |
| `--gold-700` | `#805400` | Gold used as text on light backgrounds | on white: 6.59:1 |
| `--ink-900` | `#14202e` | Body text | on white: 16.5:1 |
| `--ink-600` | `#4b5a6b` | Secondary text (replaces `#aaa` and `#888`) | on white: 7.1:1 |
| `--surface` | `#ffffff` | Cards | |
| `--bg` | `#f4f6fa` | Page background | |
| `--line` | `#d8dfea` | Borders and dividers | |
| `--danger-700` | `#a61b1b` | Cancel, errors (replaces `#e74c3c`) | white on it: 7.52:1 |
| `--success-700` | `#176b36` | Serving, success | white on it: 6.58:1 |

Dark mode swaps surfaces and text (for example `--bg` to `#0c1424`, `--surface` to `#14213a`) and keeps blue and gold as accents. Verify every pair with a contrast checker before locking the tokens.

### Status colors

Every badge has text as well as color.

| Status | Color | Label |
|--------|-------|-------|
| waiting | gold tint with dark gold text | Waiting |
| serving | green | Now serving |
| completed | grey | Completed |
| cancelled | grey | Cancelled |
| skipped | red tint | Skipped |

### Typography

| Item | Proposal |
|------|----------|
| Family | One modern sans for everything, with system fallbacks. The old display font (Konkhmer Sleokchher) is heavy and hard to read in small UI text, so keep it, if at all, for the logo and landing page only. TODO(you): pick the family. |
| Ticket codes | Very large, bold, tabular numbers (`font-variant-numeric: tabular-nums`) |
| Scale (rem) | 0.875, 1, 1.125, 1.25, 1.5, 2, 3, then a display scale for the board (4 to 8) |
| Body | 1rem (16px) minimum, line height 1.5 |

### Spacing, shape, motion

| Item | Value |
|------|-------|
| Spacing scale | 4, 8, 12, 16, 24, 32, 48, 64 px (as rem) |
| Radius | 8px (controls), 12px (cards), 999px (badges) |
| Shadow | One soft shadow for cards, one stronger for menus and dialogs |
| Motion | 150 to 250ms, only for state changes. None when `prefers-reduced-motion` is on |
| Breakpoints | 360 (base), 640, 960, 1280 |
| Icons | Inline SVG set, drawn on a 24px grid, `currentColor` |

### Components to build (tokens and base styles in v0.1.0, the rest as the screens need them)

Buttons (primary, secondary, danger, icon), inputs with labels and inline errors, cards, status badge, ticket card (the hero), department card, list row, state boxes (loading, empty, error, offline, slow), toast, connection banner, dialog, header with menu, display tile.

## Accessibility checklist

- Contrast of 4.5:1 or more for text, 3:1 for large text and UI borders.
- Every input has a visible label. Errors are linked with `aria-describedby`.
- Status changes (ticket called) are announced with `aria-live="polite"`.
- Keyboard: every action reachable, focus visible, no focus traps.
- Touch targets at least 44px.
- Respect `prefers-reduced-motion` and `prefers-color-scheme`.
- The display board is also readable by screen readers, not only visual.

## Content and tone

Short, plain English. Say what happened and what to do next. Example: "Your ticket is RGS-007. 4 people are ahead of you." Not: "Queue entry generated successfully."

## Design assets to produce

- [ ] Logo and favicon (reuse the bulldog if permitted)
- [ ] A higher resolution campus photo, or a cropped and overlaid version of the current one
- [ ] Mockups of the 3 key screens above in Figma (optional but useful for a portfolio)
- [ ] Final token values in `tokens.css`, with every color pair checked for contrast
