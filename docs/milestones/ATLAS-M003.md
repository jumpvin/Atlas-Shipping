# ATLAS-M003 — Request Management & Full-Width Application Experience

Status: implemented

Product: ATLAS Shipping Management

Current accepted product build: `0.1.7`

Target product build: `0.1.8`

Current accepted schema version: `0.1.4`

Release line: `0.1.x`

Authorized branch: `bootstrap/atlas-initialization`

Next owner: Architecture

Next command: Review

## Objective

Turn ATLAS from a functional application embedded inside a narrow WordPress content column into a convincing full-width frontend business application, and replace the `My Requests` and `All Requests` placeholders with the first real request-management experience.

This milestone must make the application practical to test and use on normal desktop screens while preserving responsive mobile behavior.

## User-Observed Layout Problem

Hands-on testing of accepted `0.1.7` showed that ATLAS is currently constrained by the active WordPress theme/content layout. The complete application shell is rendered inside a narrow centered page column, leaving large unused whitespace and compressing the sidebar, editor cards, labels, inputs, and repeated stop/item content.

This is not an editor-only problem. The ATLAS application itself must deliberately claim appropriate page real estate rather than inheriting a blog/page content width.

The goal is not merely to make individual inputs smaller or typography denser. The goal is to let ATLAS behave visually like the primary application on the page.

## Compatibility and Scope

Preserve all accepted `0.1.7` behavior, including:

- passwordless application authentication
- custom application sessions and CSRF protection
- shell routing and accessibility
- session expiration handling
- New Request editor
- meaningful autosave and draft resume
- ordered stops/items
- request transportation preferences
- deliberate submission
- aggregate-wide optimistic concurrency
- migrations `001`–`006`
- shipping repositories/services/snapshots
- Framework authority
- WordPress Plugin Suite Profile `0.2.0`

Do not redesign authentication or shipping-domain persistence unless directly required by request listing/query support.

## Full-Width Application Experience

When the ATLAS shortcode/application is rendered on its dedicated application page, the application must use substantially more of the available viewport.

Requirements:

- Do not allow the theme's normal narrow article/content max-width to determine the usable ATLAS application width.
- The application should expand across the main viewport with sensible outer gutters rather than touching the browser edges.
- Desktop should provide enough room for a stable sidebar and a generous main content region.
- The New Request editor should become comfortably readable and testable at common desktop widths.
- Avoid giant uncontrolled line lengths; individual content sections may retain intentional internal max-widths where useful, but the application shell itself should not be trapped in a blog column.
- Preserve responsive tablet/mobile behavior.
- Around 320px width, the sidebar/drawer and editor must remain usable without horizontal page scrolling.

Preferred desktop direction:

- application width approximately viewport-based with modest gutters
- sidebar roughly 220–280px where space allows
- main content consumes remaining width
- form cards use responsive columns naturally rather than being forced into an extremely narrow two-column grid

The exact CSS values are implementation details; optimize for a polished standalone-app feel.

## WordPress Theme Isolation

Solve the width problem in a robust way that does not depend on one specific theme.

Use a deliberately scoped ATLAS application-page treatment. Acceptable techniques may include application-root breakout/full-bleed layout rules, dedicated body/page classes, or another safe theme-independent mechanism.

Requirements:

- Scope overrides to the ATLAS application page/root.
- Do not globally alter unrelated WordPress pages/posts.
- Do not hide or damage WordPress admin screens.
- Do not require users to manually choose a special theme template if ATLAS can handle the application page itself.
- Preserve the existing shortcode architecture.

If the surrounding public theme header/footer remain visible for now, that is acceptable. The key requirement is that the ATLAS application body has appropriate real estate. A later milestone may decide whether the dedicated application page should become completely chrome-free.

## New Request Responsive Polish

Without redesigning the accepted editor workflow, adjust layout behavior so the form uses the newly available width well.

- Keep one scrolling page.
- Keep cards expanded by default.
- Preserve all fields and behavior.
- Use responsive field columns that remain readable.
- Prevent labels/inputs from becoming cramped.
- Repeated stop and item cards should use the available width intelligently.
- Transportation/handling controls should not look squeezed into a narrow column.
- Preserve autosave status and validation visibility.

This is layout polish, not a field/content redesign.

## Request Management Architecture

Replace `My Requests` and `All Requests` placeholders with real request-management pages backed by the accepted service/repository architecture.

Do not let frontend modules query `$wpdb` or repositories directly.

Add only the authenticated API/query surface required for these pages.

## My Requests

`My Requests` should default to requests owned by the current authenticated ATLAS identity.

The page should be useful immediately without configuration.

Default presentation:

- active/future work first
- closest relevant requested ship/delivery date first
- drafts and submitted requests clearly identifiable
- past/completed work separated or easily filterable

The user should be able to find their current work quickly.

## All Requests

`All Requests` should display requests across approved ATLAS identities so project managers can cover for one another.

This milestone intentionally permits authenticated ATLAS users to view other project managers' requests, matching the approved product decision that cross-coverage is allowed.

However:

- ownership must remain visible
- viewing another user's request does not automatically grant edit ownership
- existing New Request editor ownership/edit rules remain unchanged
- do not introduce a complex role/capability system in this milestone

## Request List Data

Each request list row/card should expose useful summary information without becoming spreadsheet-like.

Include where available:

- public request ID
- project ID
- project/job-site name
- client
- request owner/submitted user
- status
- requested ship date
- required delivery date
- primary pickup summary
- primary delivery summary
- last updated time/date

Do not expose unnecessary internal database IDs.

## List Presentation

The interface should feel like an application workspace, not an Excel table pasted into WordPress.

Desktop may use a structured table/list hybrid if appropriate, but:

- maintain comfortable spacing
- use clear status indicators
- make rows easy to scan
- provide obvious request identity/project context
- mobile must transform gracefully into stacked cards or another usable representation
- do not require horizontal scrolling for the primary mobile experience

## Future vs Past

Provide a primary temporal view/filter consistent with the original product direction.

At minimum:

- `Upcoming / Active`
- `Past / Completed`

Upcoming/active should prioritize the nearest relevant shipping/delivery dates.

Past/completed should prioritize the most recent past work first.

Requests without usable dates should remain discoverable rather than disappearing.

## Status Filtering

Allow filtering by canonical request status.

Current vocabulary includes:

- draft
- submitted
- sent_to_shipper
- options_received
- scheduled
- in_transit
- carrier_reported_delivered
- delivery_issue
- delivery_verified
- complete
- cancelled

Do not implement status transition actions here.

## Owner Filtering

`All Requests` should support filtering by request owner/submitted user.

`My Requests` is already owner-scoped to the current identity and does not need a redundant owner filter unless implementation reuse makes it harmless.

The owner filter should use approved ATLAS identities rather than WordPress users.

## Sorting

Support useful sorting without overbuilding a generic data-grid system.

At minimum support:

- relevant shipment/delivery date
- submitted/created date
- last updated
- status
- owner where appropriate

Default sort for upcoming/active: nearest relevant date first.

Default sort for past/completed: most recent relevant date first.

## Search

Provide lightweight search across useful request summary fields such as:

- public request ID
- project ID
- project/job-site name
- client

Search should be server-backed if the list is paginated/server-filtered so results are not limited to the current page.

## Pagination

Implement server-backed pagination.

Choose a sensible default page size such as 20–30 requests.

Return total/page metadata sufficient for accessible Previous/Next or numbered pagination.

Do not load the entire future request history into the browser simply to filter it client-side.

## Request Detail / Opening Requests

A request in the list must have an obvious way to inspect it.

For this milestone, implement a practical read-only request detail experience using the existing client-side routing/application shell.

The detail view should show the accepted aggregate:

- request/project information
- status and owner
- stops in deterministic order
- shipment items
- transportation/handling preferences
- notes/special instructions
- important dates

If the request belongs to the current identity and is still editable (`draft` or `submitted`), provide an obvious path to continue editing it using the existing editor architecture rather than duplicating an editor.

Do not allow another identity to edit merely because they can view the request.

Do not implement coordinator workflow actions in the detail view yet.

## API Security

All request list/detail APIs must use the existing ATLAS application-session authentication.

Requirements:

- unauthenticated access rejected
- server derives current identity
- no client-forged identity authorization
- read-all behavior is deliberate and limited to authenticated approved ATLAS identities
- mutations continue to enforce existing owner/editability boundaries
- use public request IDs in browser-facing request navigation where practical
- return stable errors without SQL/stack traces

GET/read operations do not need mutation CSRF semantics unless required by the existing API architecture; mutations continue using the accepted protection.

## Query / Repository Support

Extend repository/service query capabilities cleanly for:

- owner-scoped request listing
- all-request listing
- status filtering
- temporal filtering
- owner filtering
- search
- sorting
- pagination
- total count

Use prepared queries and whitelisted sort fields/directions.

Avoid SQL injection through dynamic sort/filter parameters.

Avoid N+1 queries for request summaries. Primary pickup/delivery summaries should be loaded efficiently.

Do not build a generic ORM or generic admin data-grid framework.

## Identity Display

Request management needs owner display names.

Use the existing ATLAS identity system. Do not create a second user source.

The API/service may expose safe display identity information needed for lists/filters, such as identity ID and display name, but should not unnecessarily expose private authentication/session data.

## Accessibility

Request management must preserve the accepted accessibility standard.

Include:

- semantic headings
- labeled search/filter controls
- keyboard-operable sorting/filtering/pagination
- visible focus
- meaningful status text, not color alone
- accessible loading/error/empty states
- mobile reading order that remains logical

## Localization

All new visible UI strings must use the existing `atlas-shipping` localization authority.

Do not repeat the hard-coded-JavaScript-string problem corrected during ATLAS-M002 review.

## Loading / Empty / Error States

Deliberately handle:

- initial loading
- filter/search loading
- no requests yet
- no matching filtered requests
- API failure
- authentication/session expiration
- pagination boundary

My Requests with no data should encourage the user toward New Request.

## Diagnostics

Extend protected diagnostics only as useful for request-management API/module registration and query support.

Do not expose shipment contents in diagnostics.

## Explicitly Out of Scope

Do NOT implement:

- Needs Attention real workflow
- Shipping Coordinator workspace
- send-to-Kindle / send-to-shipper action
- immutable snapshot action from UI
- carrier quote/options management
- final vehicle/carrier assignment
- scheduling workflow
- freight-cost editing workflow
- delivery verification/issues
- notifications/email/configurable recipients
- Excel export/import
- attachments
- comments/discussion
- dashboard metrics
- cross-user edit permissions
- complex role/capability management

## Database Changes

Prefer no schema change. The accepted schema `0.1.4` already contains the request/stops/items data required for list/detail views.

If a genuinely necessary index is missing for server-backed list/query performance, add the smallest ordered migration and advance schema consistently. Do not change schema merely for convenience.

## Versioning

Advance product build from `0.1.7` to `0.1.8`.

Preserve schema `0.1.4` unless an approved necessary migration is added.

Do not change Framework or extension versions.

## Packaging

Produce a directly installable development ZIP:

`atlas-shipping-0.1.8.zip`

It must contain one top-level `atlas-shipping/` plugin directory. Repository Framework files, reports, Git metadata, test harnesses, and unrelated build artifacts must not leak into the plugin ZIP.

## Required Runtime Validation

Use the available local WordPress/MySQL test environment.

At minimum validate:

- upgrade from accepted `0.1.7`
- plugin activation
- authentication still works
- application page uses substantially more desktop viewport width than the narrow theme content column
- theme-width override is scoped to the ATLAS application page
- unrelated WordPress pages remain unaffected
- New Request remains functional at the wider layout
- New Request remains usable on mobile
- My Requests returns only current identity ownership
- All Requests returns cross-identity requests for authenticated ATLAS identities
- other-user request can be viewed but cannot be mutated through existing owner-protected editor API
- owner filter
- status filter
- upcoming/active temporal filtering and default sort
- past/completed temporal filtering and default sort
- search
- pagination and total counts
- allowed sort fields/directions
- malicious/unsupported sort input cannot become raw SQL
- read-only request detail
- current owner's editable draft/submitted request has a valid continue-edit path
- unauthenticated list/detail API rejected
- existing draft autosave/resume remains functional
- existing submission remains functional
- aggregate concurrency remains functional
- sent-to-shipper/later edit lock remains functional
- PHP lint
- JavaScript syntax
- package boundary
- product/schema version consistency

If automated browser tooling is available, perform viewport checks around 320px, tablet, and normal desktop widths. If unavailable, record that limitation honestly and perform the strongest available source/runtime validation.

## Acceptance Criteria

ATLAS-M003 is implementation-complete when:

1. ATLAS is no longer visually trapped in the theme's narrow article column on its dedicated application page.
2. Desktop application real estate is substantially improved while responsive/mobile behavior remains intact.
3. The accepted New Request editor remains functional and materially easier to test/use in the wider shell.
4. My Requests is a real authenticated owner-scoped request list.
5. All Requests is a real authenticated cross-user request list.
6. Future/active vs past/completed views behave as designed.
7. Status, owner, search, sort, and pagination work server-side.
8. Request summaries are readable and non-spreadsheet-like.
9. Requests can be opened into a useful read-only detail view.
10. Current owners can continue editing editable requests without granting cross-user edit rights.
11. Query implementation is prepared/whitelisted and avoids obvious N+1 behavior.
12. New UI is accessible and localized.
13. Existing authentication/editor/domain behavior remains intact.
14. No coordinator/shipper/delivery/notification/Excel workflow has been introduced.
15. A valid WordPress-installable `0.1.8` development ZIP is produced and validated.

## Git Authorization

Builder is authorized to work only on `bootstrap/atlas-initialization`, modify files required by this milestone, commit the complete implementation, and push to `origin/bootstrap/atlas-initialization`.

Builder is NOT authorized to merge to `master`, force-push, rebase published history, delete branches, modify unrelated repositories, or begin the next milestone.

## Builder Completion Boundary

Builder may transition this milestone only from `active` to `implemented`.

At completion:

- Next owner: Architecture
- Next command: Review

Builder must not mark the milestone accepted/completed and must not begin the next milestone.
