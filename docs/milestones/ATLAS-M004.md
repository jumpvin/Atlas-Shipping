# ATLAS-M004 — Request Editor Interaction Stabilization

Status: implemented

Product: ATLAS Shipping Management

Current accepted product build: `0.1.8`

Target product build: `0.1.9`

Current accepted schema version: `0.1.4`

Release line: `0.1.x`

Authorized branch: `bootstrap/atlas-initialization`

Next owner: Architecture

Next command: Review

## Objective

Stabilize the accepted New Request editor based on hands-on browser testing before beginning Shipping Coordinator workflow development.

This is a tightly scoped corrective milestone. It must fix two user-observed functional defects:

1. valid shipping dates are being rejected with `A shipping date is invalid.`
2. successful autosave rebuilds the editor and removes focus from the field the user is actively typing in

It must also improve validation feedback enough that a user can identify the field responsible for a save/submission error, and prove that a realistic request can be completed and submitted end-to-end through the browser.

## User-Observed Evidence

Hands-on testing of accepted `0.1.8` showed a request containing ordinary future dates in the browser date controls, including a Requested Ship Date and a later Required Delivery Date, while the editor displayed `A shipping date is invalid.`

The same test showed that approximately one second after typing, autosave completes and the active input loses focus. The user must click back into the input to continue typing. This makes the autosave experience actively disruptive.

The current JavaScript confirms the focus-loss mechanism: normal successful `save()` ends by calling `render(false)`, which replaces the entire form DOM after each autosave.

## Compatibility and Scope

Preserve all accepted `0.1.8` functionality:

- full-width correctly positioned application shell
- passwordless authentication and application sessions
- custom-session REST security and CSRF behavior
- My Requests and All Requests
- request search/filter/sort/pagination
- read-only request detail
- cross-identity read / owner-only edit boundary
- New Request one-page card architecture
- meaningful autosave/draft resume
- multi-stop support
- structured shipment items
- transportation/handling preferences
- optimistic aggregate concurrency
- deliberate submission
- migrations `001`–`006`
- schema `0.1.4`
- shipping repositories/services/snapshots
- Framework and WordPress profile authority

Do not begin Shipping Coordinator workflow in this milestone.

## R1 — Diagnose and Correct Shipping-Date Validation

Trace the complete browser-to-database date path for:

- `requested_ship_date`
- `required_delivery_date`

Browser `input[type=date]` values are expected to use canonical HTML date values such as `YYYY-MM-DD`, regardless of localized browser display formatting.

Determine why valid future dates are currently reaching the user as `A shipping date is invalid.` and fix the root cause rather than suppressing validation.

Requirements:

- valid ISO calendar dates from browser date inputs save successfully
- both dates persist without timezone shifting
- saved dates reload into the same browser calendar date selected by the user
- dates survive autosave, page navigation, draft resume, continue-edit, and submission
- invalid calendar dates remain rejected
- do not reinterpret date-only values as UTC timestamps
- do not use JavaScript `Date` conversion for date-only request fields if it can shift the calendar day
- required delivery date may be validated relative to requested ship date only if such a rule is explicitly part of current product validation; do not invent a new business rule

The stop `datetime-local` canonical UTC behavior is a separate accepted concern and must not be broken while fixing request date-only fields.

## R2 — Autosave Must Not Interrupt Typing

Normal successful autosave must not rebuild the complete editor DOM.

Requirements:

- typing in a text input remains focused before, during, and after autosave
- typing in textarea remains focused
- cursor/caret position remains stable
- date controls do not lose focus solely because autosave completed
- select/checkbox interaction is not disrupted
- autosave status may transition `Saving…` -> `Saved` without replacing the editor
- the returned aggregate/request state and latest `row_version` must still update in memory
- server-normalized values may be reconciled deliberately without destroying active editing state

Do not solve this by disabling autosave.

Do not delay autosave so long that the issue is merely hidden.

## Structural Rerenders

Some operations may legitimately require structural UI updates, such as adding/removing/reordering persisted stops/items, loading a different request, or displaying the post-submission success screen.

Where a structural rerender is genuinely necessary:

- preserve focus intentionally when practical
- return focus to a logical nearby control after removal/reordering
- do not unexpectedly jump the user to the top of the page
- preserve scroll position unless the action intentionally navigates elsewhere

Routine field autosave must not require a structural rerender.

## R3 — Prevent Autosave Races / Lost Latest Input

Because autosave becomes non-rerendering, verify that edits made while a save is in flight are not silently lost.

The current `busy` guard must not cause a scheduled edit to disappear merely because another request is saving.

Requirements:

- if the user changes another field while an autosave is in flight, the newest state is queued/dirty and saved afterward
- rapid typing across multiple debounce windows eventually persists the latest values
- response from an older save must not overwrite newer unsaved browser values
- optimistic `row_version` remains correct
- do not create parallel mutation races that defeat the accepted aggregate concurrency model

A simple single-flight + dirty-follow-up-save strategy is acceptable.

## R4 — Field-Specific Validation Feedback

When request-level save or submission validation fails, the editor should identify the responsible field or section where practical.

For request date validation specifically:

- distinguish Requested Ship Date from Required Delivery Date
- associate the error with the relevant input visually and accessibly
- keep a useful summary message at the top if desired
- use `aria-invalid` and/or an associated error description where appropriate

More generally, preserve the existing submission error summary while improving field/section targeting for validation data the server can identify.

Do not build a large generic form framework.

## Server Error Contract

Use stable `WP_Error` codes/data for invalid request fields.

Where practical, validation errors should expose safe structured field information such as:

- field key
- localized message

Do not expose SQL, stack traces, internal paths, or sensitive data.

Normal autosave errors must not erase the user's unsaved browser input.

## Submission End-to-End

Use a realistic request in the local browser/runtime and prove the complete user path:

- enter project/request information
- select valid Requested Ship Date
- select valid Required Delivery Date
- enter complete pickup information
- enter complete delivery information
- add at least one shipment item
- optionally set transportation/handling preferences
- allow autosave to run during normal typing
- confirm focus is not stolen by autosave
- navigate away and resume/continue editing if useful to test persistence
- submit the request
- confirm status transitions to `submitted`
- confirm success UI appears
- confirm request appears in My Requests
- confirm request detail shows the persisted values

Do not create a shipper snapshot on submission; the accepted boundary remains that snapshot creation occurs later when the coordinator sends the request to the shipper.

## Date Validation Test Matrix

At minimum test:

- valid leap/non-leap calendar handling where practical
- valid current/future date
- valid Requested Ship Date + later Required Delivery Date
- blank optional date if currently allowed
- malformed date string sent directly to API rejected
- impossible date such as `2026-02-30` rejected
- browser-selected date persists exactly without one-day timezone drift

Do not require a date to be future-only unless that is already an established product requirement.

## Focus / Autosave Browser Test Matrix

Use actual browser automation if available; otherwise perform the strongest browser-level manual/runtime test available and report limitations.

At minimum verify:

- type continuously in Project ID across at least two autosave cycles
- type continuously in Project / Job Site Name across autosave
- type in General Request / Project Notes across autosave
- type in a stop address/contact field across autosave
- type in an item description across autosave
- active element remains the same control after save
- caret remains usable without another click
- latest typed value persists after reload
- editing while a previous save is in flight eventually persists the newest value

## UI/UX Boundaries

This milestone may make small directly related presentation changes for:

- inline validation errors
- error highlighting
- autosave status behavior
- focus indicators

Do not redesign the New Request form or application shell.

The overall current presentation is accepted for this corrective milestone.

## Accessibility and Localization

All new validation/status text must use the `atlas-shipping` localization surface.

Validation must not rely on color alone.

Focus behavior must remain keyboard-friendly.

Error associations should be understandable to assistive technology where practical.

## Database Changes

No schema change is expected or authorized.

Product schema remains `0.1.4`.

Do not add a migration unless Architecture review discovers a genuine persistence defect that cannot be corrected compatibly without one.

## Versioning

Advance product build from `0.1.8` to `0.1.9`.

Preserve schema `0.1.4`.

Do not change Framework or extension versions.

## Explicitly Out of Scope

Do NOT implement:

- Shipping Coordinator workspace
- Needs Attention workflow
- send-to-shipper action
- shipper email generation
- immutable shipper snapshot UI/action
- quote/carrier options
- scheduling workflow
- freight cost workflow
- delivery verification
- notifications/email delivery
- Excel export/import
- attachments
- comments
- new role/capability architecture
- cross-user editing

## Required Regression Validation

Use the available local WordPress/MySQL/browser runtime and verify:

- upgrade from accepted `0.1.8`
- plugin activation
- login/session behavior
- full-width application positioning remains correct
- My Requests / All Requests remain functional
- request detail remains functional
- cross-user view / owner-only edit remains enforced
- meaningful-input draft creation remains intact
- blank shell still creates no draft
- autosave/resume remains intact
- aggregate optimistic concurrency remains intact
- stop/item atomic mutation remains intact
- submitted requests remain editable by owner
- sent-to-shipper/later requests remain locked
- unauthenticated API rejected
- PHP lint
- JavaScript syntax
- package boundary
- product `0.1.9`
- schema `0.1.4`

## Acceptance Criteria

ATLAS-M004 is implementation-complete when:

1. Valid browser-selected request dates no longer produce the invalid-shipping-date error.
2. Request date-only values persist and reload as the exact selected calendar dates.
3. Invalid calendar dates are still rejected with field-specific feedback.
4. Routine autosave does not rerender the editor or steal focus.
5. Caret/typing remains uninterrupted through repeated autosave cycles.
6. Edits made during an in-flight save are not lost and are subsequently persisted.
7. Autosave status updates without disrupting the editor.
8. Validation identifies the responsible date field and improves field/section targeting where practical.
9. A realistic complete request can be entered and successfully submitted end-to-end in the browser.
10. The submitted request appears correctly in My Requests and Request Detail.
11. Existing `0.1.8` request-management and security behavior remains intact.
12. No coordinator/later workflow has been introduced.
13. Product build is `0.1.9`, schema remains `0.1.4`, and a valid WordPress-installable development ZIP is produced.

## Packaging

Produce:

`atlas-shipping-0.1.9.zip`

The ZIP must contain one top-level `atlas-shipping/` directory and exclude repository-only Framework files, reports, test harnesses, Git metadata, and unrelated build artifacts.

## Git Authorization

Builder is authorized to work only on `bootstrap/atlas-initialization`, modify files required by this milestone, commit the complete implementation, and push to `origin/bootstrap/atlas-initialization`.

Builder is NOT authorized to merge to `master`, force-push, rebase published history, delete branches, modify unrelated repositories, or begin the next milestone.

## Builder Completion Boundary

Builder may transition this milestone only from `active` to `implemented`.

At completion:

- Next owner: Architecture
- Next command: Review

Builder must not mark the milestone accepted/completed and must not begin Shipping Coordinator workflow.
