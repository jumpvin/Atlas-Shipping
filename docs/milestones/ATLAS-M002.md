# ATLAS-M002 — Shipping Request Editor

Status: implemented
Product: ATLAS Shipping Management
Current accepted product build: `0.1.6`
Target product build: `0.1.7`
Current accepted schema version: `0.1.3`
Release line: `0.1.x`
Authorized branch: `bootstrap/atlas-initialization`
Next owner: Architecture
Next command: Review

## Objective

Replace the `New Request` placeholder with the first real ATLAS shipping workflow: a polished single-page request editor backed by the accepted Shipping Data Foundation.

Authenticated project managers must be able to enter a request without page reloads, autosave meaningful work as a draft, manage pickup/delivery and additional stops, manage shipment items, and deliberately submit the request. This milestone ends at `submitted`; it does not implement Shipping Coordinator or third-party shipper workflow.

## Experience Contract

The editor must feel simple but powerful, calm, professional, fast, forgiving, and clearly better than a spreadsheet.

- One scrolling page; never a wizard.
- Primary sections are expanded by default.
- Users may collapse cards manually without losing values.
- Use distinct cards, generous spacing, clear headings, concise helper text, and strong field grouping.
- Preserve the accepted shell, responsive behavior, accessibility, localization, and hash routing.
- Remain usable around 320px width without spreadsheet-style horizontal scrolling.

## Compatibility

Preserve all accepted `0.1.6` behavior and Shipping Data Foundation architecture. Preserve migrations `001`–`005` unchanged, authentication/session guarantees, public shortcode, activity architecture, Framework authority, and WordPress Plugin Suite Profile `0.2.0`.

Frontend/controller code must call services; it must not directly manipulate repositories or `$wpdb`.

## Page Module

This is the first substantial frontend page. Establish an isolated New Request page module responsible for route lifecycle, rendering, event binding, API calls, cleanup, and page-specific styling. Do not rewrite the whole router. Existing placeholder routes remain intact.

## Authenticated REST Boundary

Create only the REST/API surface required by this editor. It must support operations equivalent to:

- discover/resume the current identity's appropriate active draft
- create a draft
- load one editable request aggregate
- autosave/update request fields
- create/update/remove/reorder stops
- create/update/remove/reorder items
- submit a draft request

Use the existing ATLAS application session cookie/identity system, not WordPress-user authentication for application users. Derive actor identity server-side from the validated session; the browser must not be able to forge owner/actor IDs.

Add CSRF protection appropriate to the custom application-session model. Unauthenticated request reads/mutations must fail. Controllers call the shipping service layer and return stable JSON/error codes without SQL, stack traces, or internal implementation details.

Use the public request identifier for frontend identity/navigation where practical rather than requiring exposure of database IDs.

## Ownership and Edit Boundary

New requests belong to the current authenticated ATLAS identity.

For this milestone, the owner may edit requests in `draft` or `submitted` status. Server-side mutation must reject shipment-detail edits for `sent_to_shipper` and later/locked statuses even if a client manually calls the API.

Do not implement role/cross-user permission management yet.

## Autosave and Draft Policy

Opening `#/new-request` alone MUST NOT create a database draft.

Create a server draft only after meaningful business input exists, such as project ID, client/project/site name, a meaningful stop location, item description, or requested/required date. Default checkbox/select values do not count.

After draft creation:

- debounce autosave (roughly 800–1500 ms after meaningful changes)
- update the same draft rather than creating duplicates
- show subtle `Saving…`, `Saved`, and actionable failure states
- use accepted `row_version` optimistic concurrency
- never silently overwrite a newer server version
- retry transient failures conservatively

When the user returns to New Request, resume the appropriate active draft instead of automatically creating another. Simple policy for this milestone: if active drafts exist for the current identity, resume the most recently updated one. Do not build a Draft Manager or automatic draft cleanup yet.

Clearly indicate when an existing draft was resumed.

## Submission

`Submit Shipping Request` is a deliberate action and is not autosave.

Run strict server-side validation before submission and return structured field/section errors.

On success:

- status becomes `submitted`
- `submitted_at` is populated
- activity records submission with validated `actor_identity_id`
- show a clear success state with public request ID
- owner remains allowed to edit while status is `submitted`

Do NOT create a shipper snapshot on submission. Do NOT set `sent_to_shipper`. Snapshot creation and PM edit lock happen in the later coordinator handoff milestone.

## Form Cards

### Project / Request Details

Include domain-supported fields:

- Project ID
- Internal ID where applicable
- Client
- Project / Job Site Name
- Requested Ship Date
- Required Delivery Date
- delivery-date firmness/flexibility
- client-requested indicator where applicable
- general request/project notes

Do not expose freight cost in this PM editor.

### Stops

Initialize the UI with one Pickup and one Delivery stop. Support additional stops and deterministic ordering.

Each stop supports:

- stop type
- site/company name
- address lines
- city
- state/region
- postal code
- country
- contact name
- contact phone
- contact email
- pickup/delivery/arrival window start and end
- appointment required
- loading/unloading/handling responsibility
- dock availability
- forklift availability
- equipment/handling notes
- stop-specific instructions

Use friendly local browser date/time controls and deliberately convert to the backend canonical datetime representation. Never ask normal users to type raw UTC database strings.

Unsaved stop removal is local only. Persisted stop removal must require a clear confirmation when data will be lost. Reorder controls must be keyboard operable where implemented.

### Shipment Items

Repeatable item UI supports:

- quantity
- description
- length / width / height
- dimension unit
- weight
- weight basis where applicable
- weight unit
- packaging type
- stackable
- fork pockets
- weather sensitive
- special handling / notes

Users can add/remove items and deterministic order is preserved. Mobile presentation must use cards/stacking rather than a dense grid.

### Transportation / Handling Preferences

Project managers provide context; Shipping Coordinator and the third-party shipper make the final equipment/carrier decision.

Provide request-level fields for:

- preferred vehicle/equipment type or no preference
- optional transportation comment
- shipping service expected to load at pickup
- shipping service expected to unload at delivery
- separate third-party loading assistance may be needed
- separate third-party unloading assistance may be needed

Anticipate extensible choices such as cargo/sprinter van, box truck, flatbed, other, and no preference.

If schema `0.1.3` lacks appropriate request-level fields, add the smallest new ordered migration after `005` and advance schema consistently. Do not overload unrelated columns merely to avoid a migration.

### Special Instructions

Provide a clear shipment-level instructions area for details that do not belong to one stop/item, including specific on-site drop location or coordination details. Reuse existing request notes where semantically correct rather than duplicating concepts.

## Validation

Draft autosave permits incomplete data. Submission is stricter but should not create unnecessary bureaucracy.

Submission must require enough information to make the request operationally understandable, including:

- project identifier/context
- at least one pickup
- at least one delivery
- required location/address information for those stops
- valid stop contacts where required by the form rules
- valid windows when supplied
- at least one shipment item
- positive quantity
- meaningful item description

Return structured validation errors that map to fields/cards.

## Activity

Preserve validated actor attribution. Record meaningful events such as draft creation and submission, plus existing stop/item lifecycle events.

Do not flood activity with a generic request-updated event for every debounced autosave. Add an autosave-aware service option or equivalent suppression/aggregation if needed. Never log full request payloads.

## Concurrency

Use `row_version`. Outdated autosave/update requests return a conflict. The frontend must show a clear conflict message and safe reload/recovery path rather than silently overwriting server state.

## Accessibility / Localization

Preserve accepted shell accessibility. Require labels, semantic grouping, keyboard-operable controls, visible focus, accessible validation messages, `aria-expanded` on collapsible cards, useful live-region treatment for autosave status, and no keyboard traps.

All visible PHP/JS text must be translatable through `atlas-shipping` and the existing localization pattern.

## Loading and Error States

Deliberately handle:

- initial draft lookup/loading
- resumed draft
- saving
- saved
- save failed
- validation failed
- concurrency conflict
- authentication/session failure
- successful submission

## Diagnostics

Extend protected diagnostics only as useful for editor/API/module registration and any new schema migration. Never expose shipment contents.

## Explicitly Out of Scope

Do NOT implement:

- My Requests / All Requests / Needs Attention real lists
- request management filtering/sorting/pagination
- Shipping Coordinator workspace
- send-to-Kindle / send-to-shipper action
- shipper snapshot action
- carrier quote/options or assignment
- final vehicle assignment
- scheduling workflow
- freight-cost workflow
- delivery verification/issues
- notifications/email/configurable recipients
- Excel export/import
- attachments
- comments/discussion
- dashboards/metrics

## Versioning

Advance product build to `0.1.7`.

If transportation/handling preference fields require schema changes, create a new ordered migration after `005` and advance schema version consistently. Otherwise retain schema `0.1.3`. Never modify migrations `001`–`005`. Framework/profile versions remain unchanged.

## Packaging

Produce and validate `build/dev/atlas-shipping-0.1.7.zip` with exactly one top-level `atlas-shipping/` directory and no repository-only Framework files, reports, scripts, Git metadata, or build artifacts.

## Required Runtime Validation

Use the available local WordPress test site/runtime where possible. Validate at minimum:

- upgrade/activation from accepted `0.1.6`
- any new migration plus rerun/idempotency
- authenticated New Request route loads
- unauthenticated API access fails
- API actor/owner derives from session
- opening New Request creates no draft
- meaningful input creates exactly one draft
- repeated autosaves update that draft
- returning resumes the appropriate draft
- request fields persist
- stop create/update/remove/order persists
- item create/update/remove/order persists
- local datetime converts to canonical backend value
- invalid/reversed windows fail
- optimistic concurrency conflict is enforced
- incomplete submission fails with structured errors
- valid submission succeeds and sets `submitted_at`
- owner can edit `submitted`
- simulated `sent_to_shipper` rejects editor mutation
- submission creates no snapshot
- activity attribution is correct without autosave spam
- shell routing/back/forward remains intact
- PHP lint and JS syntax checks pass
- package boundary passes

Clean milestone test data where practical. Browser-only visual/accessibility checks that cannot be automated must be reported as not run rather than fabricated.

## Regression Checklist

Verify existing migrations `001`–`005`, passwordless authentication, atomic magic links, sessions, identity management, shell routing/accessibility/session expiration, diagnostics, shortcode, Shipping Data Foundation repository/service behavior, snapshot integrity, Framework authority, and WordPress profile lock remain intact.

## Acceptance Criteria

Implementation is complete when:

1. New Request is a real single-page card-based editor.
2. Opening it does not create an empty draft.
3. Meaningful input creates and autosaves one draft.
4. Active draft resume works without automatic duplicate creation.
5. Pickup/delivery plus multi-stop editing works.
6. Structured repeatable item editing works.
7. Transportation/handling preference data is persisted cleanly.
8. Authenticated API uses ATLAS session identity and CSRF protection.
9. Optimistic concurrency prevents silent overwrite.
10. Strict submission validation works.
11. Successful submission sets status/timestamp and audit attribution.
12. Submitted remains editable by owner.
13. Sent-to-shipper/later status rejects PM shipment-detail mutation.
14. Submission does not create a shipper snapshot.
15. Responsive/accessibility/localization foundations are preserved.
16. No later coordinator/list/notification/Excel workflow is introduced.
17. `0.1.7` package is installable and validated.

## Git Authorization and Completion Boundary

Builder may work, commit, and push only on `bootstrap/atlas-initialization` for this milestone. No merge, force-push, rebase of published history, branch deletion, unrelated repository changes, or next-milestone work is authorized.

Builder may transition only `active -> implemented`, then hand off:

- Next owner: Architecture
- Next command: Review

Builder must not claim Architecture acceptance.
