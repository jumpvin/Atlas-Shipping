# ATLAS-M003 — Architecture Review 1

Review result: `review_required`

Reviewed implementation commit: `7fad0faf49d8514d0766bc59d7211691f502fb07`

Product target remains: `0.1.8`

Schema target remains: `0.1.4`

## What passed

The application now claims substantially more horizontal real estate, and the overall target size is appropriate. Runtime evidence reports 1390px of application width in a 1422px viewport, no overflow at 900px or 320px, working My Requests / All Requests query runtime, cross-identity read with owner-only edit behavior, unauthenticated API rejection, PHP lint, JavaScript syntax, package boundaries, Framework validation, and unchanged historical migrations.

The service/repository query boundary, prepared query usage, whitelisted sort expressions, server-backed search/filter/pagination, owner-scoped My Requests behavior, cross-identity All Requests behavior, and continue-edit ownership boundary are acceptable.

Hands-on Architecture testing subsequently identified a positioning defect in the full-width treatment; see R4. The width itself should be preserved while its viewport positioning is corrected.

## R1 — Complete the required request-list summary

The current request row shows public ID, one project-context value, client, owner, status, requested ship date, pickup, delivery, and updated date.

The milestone explicitly requires each list row/card to include where available both:

- Project ID
- Project / Job Site Name
- Required Delivery Date

The current heading chooses `project_name || project_id`, so one disappears when both exist, and Required Delivery Date is not rendered.

Correct the list presentation so the user can see both project ID and project/job-site name when available, plus requested ship date and required delivery date, while preserving the clean non-spreadsheet presentation and mobile behavior.

Do not simply add a horizontally dense table. Use the available full-width workspace intelligently.

## R2 — Read-only Request Detail must represent the operational request, not only a summary

The current detail page receives the full aggregate but renders only abbreviated stop and item information.

Stops currently show essentially site/address. The operational read-only view must expose the request information needed to understand what was submitted, including where present:

- stop type and deterministic order
- site/company and full address
- contact name
- contact phone
- contact email
- arrival/window start and end
- appointment requirement
- handling/loading responsibility
- dock availability
- forklift availability
- equipment/handling notes
- stop-specific instructions

Shipment items must expose where present:

- quantity and description
- dimensions and dimension unit
- weight, weight basis, and weight unit
- packaging type
- stackable
- fork pockets
- weather sensitive
- special-handling / notes

Request-level detail must continue showing project/request context, status, owner, requested ship date, required delivery date, general notes/special instructions, and transportation/handling preferences.

The view should remain readable and card-based; do not reproduce the editor controls or make this an editable form.

Current owners of `draft` / `submitted` requests must retain the Continue Editing path. Other identities remain read-only.

## R3 — Finish request-management localization and accessible labels

The new module still contains normal rendered strings outside the established localization surface, including at least:

- the `request-detail` route title `Request Details` in `pages.js`
- the pagination navigation `aria-label="Pagination"`

Review all new ATLAS-M003 visible and accessibility text and route titles. Normal rendered text/accessible labels must originate from PHP translations using the `atlas-shipping` text domain and the localized configuration surface, consistent with the localization rule established in ATLAS-M002.

Defensive JavaScript fallback copy is acceptable, but the normal localized execution path must not depend on hard-coded English.

While touching the list controls, label the sort control accurately (for example, `Sort by`) rather than using `Last updated` as the field label while the selected sort may be Relevant Date, Created Date, Status, or Owner.

## R4 — Preserve the new application width but correct viewport positioning

Hands-on Architecture testing of the implemented full-width treatment confirms that the new application size is appropriate, but its positioning is not.

The current breakout rule uses a centered-width calculation combined with `margin-left: 50%` and `transform: translateX(-50%)` from an element that is itself inside the theme's narrow content column. In the tested Twenty Twenty-Five page, this causes the expanded ATLAS application to extend far off the left side of the viewport. The screenshot shows the left sidebar and approximately half of many form controls clipped outside the visible page while a large unused white area remains on the right.

This is a blocking usability defect even though the measured element width is correct.

Correct the dedicated ATLAS application-page layout so:

- the application remains approximately the same useful desktop width achieved in `0.1.8`
- the complete application is positioned within the viewport with sensible left and right gutters
- the sidebar is fully visible
- the main content is fully visible
- no application content is clipped beyond the left viewport edge
- no large accidental unused right-side region is created by an incorrect breakout origin
- the solution remains scoped to the ATLAS application page
- unrelated WordPress pages remain unaffected
- tablet/mobile behavior remains usable

Do not solve this by shrinking ATLAS back into the theme content column. Width is accepted; positioning is what must change.

Prefer a robust viewport-relative/full-bleed technique that accounts for the fact that the shortcode root may begin inside a centered theme content container. Do not assume the shortcode element's own 50% point is the viewport center.

## Review-scope rules

Address only R1–R4 plus directly necessary styles, localization/config additions, runtime validation, and report updates.

Preserve:

- the accepted wider application size while correcting its position
- 320px/tablet responsive behavior
- accepted New Request editor
- My Requests owner scoping
- All Requests cross-identity read behavior
- owner-only mutation behavior
- search/filter/sort/pagination architecture
- request query prepared/whitelisted SQL behavior
- schema `0.1.4`
- product target `0.1.8`
- migrations `001`–`006`

Do not implement coordinator workflow, shipper handoff, Needs Attention, delivery verification, notifications, Excel, attachments, comments, or cross-user editing.

## Required validation

Use the available WordPress/MySQL/browser runtime where possible and verify:

- desktop application remains approximately the accepted wide size
- desktop ATLAS shell is wholly visible within the viewport with balanced/sensible gutters
- sidebar and main content are not clipped on the left or right
- no large accidental blank region results from breakout positioning
- the Twenty Twenty-Five test page or equivalent narrow theme container correctly centers/positions the viewport-wide application
- unrelated WordPress pages remain unaffected
- 900px and approximately 320px layouts remain free of primary horizontal overflow
- list rows show Project ID and Project/Job Site Name independently when both exist
- list rows show Requested Ship Date and Required Delivery Date when both exist
- request detail renders full stop operational fields when populated
- request detail renders full item operational fields when populated
- request-level transportation/handling preferences and notes remain visible
- deterministic stop/item ordering is preserved
- current owner continue-edit remains available for draft/submitted
- other-user detail remains read-only
- My Requests / All Requests filters, search, sorting, pagination remain functional
- all normal ATLAS-M003 UI/accessibility strings use the localized configuration path
- unauthenticated read APIs remain rejected
- PHP lint and JavaScript syntax pass
- package boundary passes
- product remains `0.1.8`
- schema remains `0.1.4`

At completion return lifecycle control to Architecture with status `implemented` and next command `Review`.
