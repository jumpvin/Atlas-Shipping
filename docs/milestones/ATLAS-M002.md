# ATLAS-M002 — Shipping Request Editor

Status: active

Product: ATLAS Shipping Management

Current accepted product build: `0.1.6`

Target product build: `0.1.7`

Current accepted schema version: `0.1.3`

Release line: `0.1.x`

Authorized branch: `bootstrap/atlas-initialization`

Next owner: Builder

Next command: Implement Milestone

## Objective

Replace the existing `New Request` placeholder with the first real ATLAS Shipping product workflow: a polished single-page shipping request editor backed by the accepted Shipping Data Foundation.

The experience must let an authenticated project manager enter a complete shipping request without page reloads, save meaningful work automatically as a draft, manage normal pickup/delivery information plus additional stops and shipment items, and deliberately submit the request when ready.

This milestone ends at `Submitted`. It MUST NOT implement the later Shipping Coordinator / third-party shipper workflow.

## Product Experience

The request editor is intended to demonstrate why ATLAS is better than spreadsheets and inconsistent email requests.

The interface should feel:

- simple but powerful
- calm and professional
- easy to scan
- fast
- forgiving
- not spreadsheet-like
- not overwhelming

The form MUST remain one scrolling page. Do not turn the sections into a wizard or multi-step sequence.

All primary sections are expanded by default. Users may collapse a section manually to focus, but they should not be forced through extra clicks to reveal normal fields.

Use distinct cards/sections, generous spacing, clear headings, concise helper text, and strong field grouping.

## Compatibility and Scope Rule

Unless explicitly changed here:

- Preserve all accepted `0.1.6` functionality.
- Preserve Shipping Data Foundation architecture and schema relationships.
- Preserve passwordless authentication and application sessions.
- Preserve application shell, routing, responsive behavior, accessibility, and localization.
- Preserve migrations `001` through `005` unchanged.
- Preserve public shortcode behavior.
- Preserve existing activity/authentication behavior.
- Preserve Framework and WordPress Plugin Suite Profile authority.
- Frontend code MUST use service/controller boundaries rather than directly querying repositories/database.
- Do not perform unrelated refactoring or redesign.

## Page Module Architecture

This is the first substantial application page. Establish the page-module pattern now rather than continuing to grow the generic placeholder renderer.

The exact file layout is flexible, but the New Request feature should have an isolated module responsible for its route lifecycle, rendering, event binding, data loading/saving, cleanup, and page-specific styles.

Do not rewrite the whole router. Preserve existing routes and shell behavior.

The architecture should make future `My Requests`, `All Requests`, and request-detail modules independently implementable.

## Authenticated API Boundary

Create the minimum authenticated REST API needed by this editor.

Use the existing ATLAS application session/identity system, not WordPress user authentication for application users.

At minimum support operations equivalent to:

- create a draft request
- load one editable request aggregate
- autosave/update request fields
- create/update/remove/reorder stops
- create/update/remove/reorder items
- submit a draft request
- discover/resume the current user's appropriate active draft for the New Request route

Controllers must call the shipping service layer. They MUST NOT bypass services to manipulate repositories directly.

REST responses should use public request identifiers for frontend navigation/identity where practical and must not require exposing internal database IDs as the public request identity.

Use appropriate HTTP methods, JSON responses, stable error codes, sanitization/validation, and no raw database errors.

## Application Session Security for REST

REST requests MUST authenticate through the existing ATLAS application session cookie and resolve the current ATLAS identity.

Add CSRF protection appropriate for the custom authenticated frontend session model. Do not assume WordPress login nonces alone protect non-WordPress application identities.

Do not permit unauthenticated access to request data or mutations.

For this milestone, an authenticated ATLAS identity may create and edit its own draft/submitted request as defined below. Do not implement the full cross-user permission system yet.

## Request Ownership

New requests default to the currently authenticated ATLAS identity as owner/requester and actor.

The frontend must not be able to forge another owner or actor simply by posting an arbitrary identity ID.

Server-side code derives actor identity from the validated session.

Do not add a submitter selector to the normal New Request form.

## Draft Creation and Autosave

Autosave is required, but it must avoid creating piles of empty/useless drafts.

### Before meaningful input

Opening `#/new-request` alone MUST NOT create a database draft.

The editor may maintain unsaved client state while the form is untouched or contains only trivial/empty values.

### Meaningful draft threshold

Create the server-side draft only after the user enters meaningful shipment information.

A reasonable threshold is the first meaningful non-empty business field such as project ID, client, project/site name, a pickup/delivery location, a shipment item description, or a requested/required date.

Do not create a draft merely because a default checkbox/select value exists.

### Autosave behavior

After a server draft exists:

- debounce changes rather than saving every keystroke
- save without reloading the page
- show subtle states such as `Saving…`, `Saved`, and actionable failure state
- preserve optimistic concurrency using the accepted `row_version` foundation
- do not silently overwrite a newer server version
- retry transient failures conservatively; do not create duplicate drafts

A debounce around 800–1500ms after meaningful changes is appropriate.

### Draft resume / duplicate prevention

When the current user returns to `New Request`, resume the user's appropriate existing active draft rather than automatically creating another one.

For this milestone, use a simple deterministic policy: if exactly one normal active draft exists for the current identity, load it. If multiple legacy/test drafts exist, choose the most recently updated active draft and do not create another until the user explicitly starts a new request in a future management flow.

Do not build a full Draft Manager in this milestone.

The editor should clearly indicate when an existing draft was resumed.

## Submission

Provide a deliberate `Submit Shipping Request` action.

Submission is NOT autosave.

Before submission, run full server-side validation and present field/section errors clearly.

On successful submission:

- status becomes `submitted`
- `submitted_at` is set
- activity records the submission and actor identity
- the editor displays a clear success state including the public request ID
- autosave continues to be possible for the owner while the request remains `submitted`, because the approved product rule allows project managers to edit through Submitted

Do not create a shipper snapshot on submission.

Do not mark `sent_to_shipper`.

The immutable shipper snapshot and PM edit lock occur only in the later coordinator handoff milestone.

## Form Sections

Implement the form as one page with cards. The exact wording may be refined for clarity, but the following functional groupings are required.

### 1. Project / Request Details

Include fields supported by the accepted domain model such as:

- Project ID
- Internal ID when applicable
- Client
- Project / Job Site Name
- Requested Ship Date
- Required Delivery Date
- Delivery-date firmness/flexibility
- Client-requested indicator where applicable
- General project/request notes

Do not expose freight cost to the project-manager request editor; cost belongs to the later shipping workflow.

### 2. Pickup / Stops

Normal requests should begin with one Pickup and one Delivery stop in the UI.

Each stop supports:

- stop type
- site/company name
- address line 1
- address line 2
- city
- state/region
- postal code
- country
- contact name
- contact phone
- contact email
- arrival/pickup/delivery window start
- window end
- appointment required
- who is responsible for loading/unloading or equivalent handling responsibility
- dock availability
- forklift availability
- equipment/handling notes
- stop-specific/special instructions

Use user-friendly local date/time controls in the browser. Convert deliberately to the backend's canonical storage representation. Do not ask normal users to type raw UTC database datetime strings.

### 3. Multi-stop support

Users must be able to add additional stops.

Support reorder where practical and always persist deterministic stop order.

Do not force users to understand database sequence numbers.

Removal of an unsaved stop is local only. Removal of a persisted stop must be confirmed if data would be lost.

### 4. Shipment Items

Provide repeatable item cards/rows with at least:

- quantity
- description
- length
- width
- height
- dimension unit
- weight
- weight basis where applicable
- weight unit
- packaging type
- stackable
- fork pockets
- weather sensitive
- special handling / notes

Users must be able to add and remove items and preserve deterministic order.

Avoid a dense spreadsheet-style grid on small screens.

### 5. Transportation / Handling Preferences

The project manager supplies useful context, but the Shipping Coordinator and third-party shipper ultimately decide the actual equipment/carrier plan.

Add a lightweight request-level preference area for:

- preferred vehicle/equipment type when the submitter has a preference
- optional transportation comment
- whether the shipping service is expected to load at pickup
- whether the shipping service is expected to unload at delivery
- whether separate third-party loading assistance may be needed
- whether separate third-party unloading assistance may be needed

This information is advisory/request data, not a final carrier assignment.

If the accepted `0.1.3` schema does not yet contain appropriate request-level fields for these concepts, add the smallest ordered migration needed. Do not overload unrelated columns simply to avoid a migration.

Anticipate values such as cargo van / sprinter van / box truck / flatbed / other / no preference without making the list impossible to extend later.

### 6. Special Instructions

Provide a clear place for shipment-level instructions that do not belong to a single stop or item.

Examples include a specific place on site where material should be delivered or coordination details.

Reuse the existing request notes field where semantically appropriate rather than creating duplicate concepts.

## Validation Strategy

Draft autosave uses permissive partial validation: incomplete drafts are allowed.

Submission uses strict validation.

At minimum, submission should require enough information to make the shipping request operationally understandable, including:

- Project ID or another approved project identifier
- meaningful project/client/site context
- at least one pickup stop
- at least one delivery stop
- required address/location information for required stops
- appropriate stop contact information where required by product rules
- valid pickup/delivery windows when supplied
- at least one shipment item
- positive item quantities
- meaningful item descriptions

Do not invent excessive mandatory fields. The purpose is consistent useful data, not bureaucratic friction.

Return structured validation errors that the frontend can associate with fields/cards.

## Editing Submitted Requests

The owner may continue editing a request in status `submitted` during this milestone.

Draft and submitted are the only statuses this editor should treat as editable for the project-manager flow.

If a request is in `sent_to_shipper` or any later/locked status, the API MUST reject shipment-detail edits even if a client attempts to call the endpoint directly.

This establishes the approved lock boundary early even though the coordinator handoff action itself is not built yet.

Do not implement amendment workflow after `sent_to_shipper` yet.

## Activity

Use the existing activity infrastructure and actor attribution.

Record meaningful events, not every autosave keystroke.

At minimum:

- request draft created
- request submitted
- persisted stop/item add/remove events already produced by services as appropriate

Avoid flooding the activity table with one generic `request_updated` event for every debounced autosave. If necessary, add an autosave-aware service option or aggregate/suppress low-value autosave activity while preserving important audit events.

Do not log full shipment payloads into activity metadata.

## Concurrency

Use the existing request `row_version` optimistic concurrency mechanism.

The API must return a conflict response when the browser tries to save an outdated request version.

The frontend must not silently overwrite the server version. Show a clear message and provide a safe reload/recovery path.

## Accessibility

Preserve the accepted application-shell accessibility foundation.

The editor must include:

- proper labels
- fieldset/legend or equivalent semantic grouping where appropriate
- keyboard-operable add/remove/collapse/reorder controls
- visible focus states
- accessible validation messages
- `aria-expanded` for collapsible cards
- live-region treatment for autosave status where useful
- no keyboard traps

Collapsing a card must not discard its values.

## Responsive Behavior

The editor must remain usable around 320px width.

Desktop may use multi-column field groups where natural.

Mobile should stack fields and item/stop controls rather than forcing horizontal scrolling.

Do not make the form resemble an Excel table on mobile.

## Localization

All visible PHP and JavaScript interface text must remain translatable through the `atlas-shipping` text domain and the existing frontend localization pattern.

Do not introduce hardcoded production English strings in JavaScript where localized configuration should be used.

## Loading / Error States

Provide deliberate states for:

- initial draft lookup/loading
- resumed draft
- saving
- saved
- save failed
- validation failed
- concurrency conflict
- session/authentication failure
- successful submission

Do not expose raw PHP, SQL, stack traces, internal IDs, or database errors.

## Diagnostics

Extend protected diagnostics only where useful to confirm this milestone's infrastructure, such as:

- shipping REST/editor registration
- editor frontend module registration
- schema health if a new migration is added

Do not expose request contents through diagnostics.

## No Later Workflow

Do NOT implement:

- My Requests real list
- All Requests real list
- Needs Attention real list
- request management table/filtering/pagination
- Shipping Coordinator workspace
- send-to-Kindle / send-to-shipper action
- immutable shipper snapshot action
- PM edit lock transition UI
- carrier quote/options
- carrier assignment
- actual vehicle assignment
- scheduling workflow
- freight-cost entry/display workflow
- delivery verification
- delivery issues workflow
- notifications/email
- configurable notification recipients
- Excel export/import
- attachments
- comments/discussion system

These remain later milestones.

## Versioning and Migration

Advance product build from `0.1.6` to `0.1.7`.

If request-level transportation/handling preference fields require a schema addition, add a new ordered migration after `005` and advance schema version consistently.

Do not modify migrations `001`–`005`.

If no schema change is required, retain schema `0.1.3`.

Do not change Framework or WordPress profile versions.

## Packaging

Produce a directly installable development ZIP:

`build/dev/atlas-shipping-0.1.7.zip`

The ZIP must contain one top-level `atlas-shipping/` directory and exclude repository-only Framework files, reports, scripts, Git metadata, and build artifacts.

## Required Runtime Validation

The Builder has access to a local WordPress test site/runtime. Use it for this milestone where available.

Validate at minimum:

- plugin upgrade/activation from accepted `0.1.6`
- any new migration and migration rerun
- authenticated New Request route loads
- unauthenticated API calls fail
- authenticated API actor is derived from session
- opening New Request alone creates no draft
- meaningful input creates exactly one draft
- repeated autosaves update that draft rather than creating duplicates
- returning to New Request resumes the appropriate active draft
- autosave preserves request fields
- stop create/update/remove/order persistence
- item create/update/remove/order persistence
- local browser datetime converts to accepted backend canonical datetime
- invalid/reversed windows are rejected
- optimistic concurrency conflict is enforced
- submission strict validation rejects incomplete requests
- valid request submits successfully
- status becomes `submitted` and `submitted_at` is populated
- owner can still edit `submitted`
- simulated `sent_to_shipper` request rejects editor mutation
- no snapshot is created merely by submission
- activity attribution remains correct without excessive autosave log spam
- shell routing/back/forward behavior remains intact
- mobile/responsive editor is structurally usable
- PHP lint and relevant JS syntax checks pass
- package boundary passes

Clean up milestone test records after validation where practical.

If any browser-only visual/accessibility behavior cannot be automated, report it explicitly rather than