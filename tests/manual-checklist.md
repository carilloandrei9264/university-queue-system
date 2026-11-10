# Manual test checklist

Copy this list into the release pull request and tick it. Record the version, date, browser and device at the top.

Version: ____  Date: ____  Browser/device: ____

## Smoke path (every release)
- [ ] Landing page loads, no console errors
- [ ] Sign in as a seeded student, log out, sign in again
- [ ] Take a ticket and see its status and number of people ahead
- [ ] Finish or cancel it, then take a ticket at another office
- [ ] Staff calls the ticket, student screen updates on its own after the refresh interval, without reloading
- [ ] Assign staff to two windows, call two tickets, and confirm the student and display show the correct window for each
- [ ] Complete or skip the active ticket before starting a window break; confirm other windows remain available and the display shows the break
- [ ] Staff completes the ticket; the student can join another service queue
- [ ] Display board shows the call
- [ ] Admin can add a department and a service
- [ ] Admin downloads the student CSV template, previews a roster with valid/duplicate/invalid rows, imports only the ready records, and exports only approved roster fields

## Roles and access
- [ ] Student cannot open staff or admin pages
- [ ] Staff cannot see another department's tickets
- [ ] Non-admin cannot import or export a student roster
- [ ] Staff cannot call another ticket at their active window or manage another window's ticket
- [ ] Logged-out user is redirected from every protected page
- [ ] Display board works without logging in

## States (each data screen)
- [ ] Loading
- [ ] Empty
- [ ] Error with retry
- [ ] Offline banner
- [ ] Slow connection message

## Layout and accessibility
- [ ] 360px wide: no horizontal scroll
- [ ] Tab through every screen, focus is always visible
- [ ] Text contrast looks fine in light and dark mode (if supported)
- [ ] Status changes are announced (turn on a screen reader once per release)

## Concurrency (v0.3.0 onward)
- [ ] Two students take a ticket at the same moment: different numbers
- [ ] Two staff press Call next together: different tickets
