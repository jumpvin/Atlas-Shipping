# ATLAS-M003 — Architecture Review 1

Review result: `review_required`

Reviewed implementation commit: `7fad0faf49d8514d0766bc59d7211691f502fb07`

Product target remains: `0.1.8`

Schema target remains: `0.1.4`

## What passed

The full-width application treatment is successful and correctly scoped to the ATLAS application page. Runtime evidence reports 1390px of application width in a 1422px viewport, no overflow at 900px or 320px, working My Requests / All Requests query runtime, cross-identity read with owner-only edit behavior, unauthenticated API rejection, PHP lint, JavaScript syntax, package boundaries, Framework validation, and unchanged historical migrations.

The service/repository query boundary, prepared query usage, whitelisted sort expressions, server-backed search/filter/pagination, owner-scoped My Requests behavior, cross-identity All Requests behavior, and continue-edit ownership boundary are acceptable.

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

## Review-scope rules

Address only R1–R3 plus directly necessary styles, localization/config additions, runtime validation, and report updates.

Preserve:

- full-width scoped application behavior
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

- desktop full-width behavior remains intact
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
