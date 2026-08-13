# ATLAS-M005 — Architecture Review 1

Review result: `review_required`

Reviewed implementation commit: `905a41a03c62551fce2b087598651adbbd80957d`

Product target remains: `0.2.0`

Schema target remains: `0.2.0`

## What passed

The coordinator backend foundation is substantially correct. Runtime evidence reports passing migration upgrade/idempotency, coordinator role authorization and Project Manager denial, submitted queue/workspace behavior, atomic send/snapshot/status/lock behavior, stale/duplicate send boundaries, unauthenticated rejection, PHP lint, JavaScript syntax, package boundaries, Framework validation, and preservation of migrations `001`–`006`.

The service uses a transaction and request-row lock for final handoff, verifies submitted state and expected row version, persists coordinator handoff data, creates a `shipper_handoff` immutable snapshot, links the snapshot, transitions the request to `sent_to_shipper`, timestamps/version-advances the request, and records activity after commit. The accepted PM editability rule therefore locks normal editing after successful handoff.

## R1 — Coordinator workspace must show the complete operational request

The current coordinator UI abbreviates the request too aggressively. The milestone requires the coordinator to inspect the complete operational request before sending.

Current stop rendering combines only a subset of fields into one paragraph and omits operational fields including, where present:

- contact email
- window start/end
- appointment requirement
- handling responsibility
- dock availability
- forklift availability
- equipment notes

Current item rendering is essentially quantity, description, and weight and omits, where present:

- dimensions and dimension unit
- weight basis
- packaging type
- stackable
- fork pockets
- weather sensitive
- special-handling notes

Request-level context should also clearly expose the relevant project/client/request fields and PM handling/loading preferences, not only project/date/notes and PM vehicle preference.

Reuse/pattern-match the accepted Request Detail cards where practical so the coordinator can genuinely review what is about to become the canonical outbound snapshot.

Do not make the coordinator switch to another route to find omitted operational data.

## R2 — Implement a real in-application outbound preview/confirmation

The current UI places a static card under the coordinator form saying that the action creates an immutable snapshot, with the `Send to Shipper` button immediately inside it. This is not the required preview of the exact outbound payload.

Before final send, the user must be able to review an in-application preview that includes at least:

- request/project identity
- PM/request owner
- requested ship/delivery dates
- complete ordered stops
- complete ordered items
- handling/loading information
- PM transportation preference/comment
- coordinator final vehicle/equipment decision, including `other` text when applicable
- coordinator notes
- coordinator loading/unloading decisions

The preview must reflect the current coordinator form values, not merely previously persisted data.

Use an explicit application confirmation step/panel/modal/drawer or equivalent. Do not use native browser `confirm()`.

The final `Send to Shipper` control should only execute from this deliberate confirmation state. Provide a clear way to return and edit the coordinator decision before sending.

Focus management and keyboard operation must be deliberate for the confirmation experience.

The canonical snapshot payload produced by the server must continue to match what the preview represents.

## R3 — Complete coordinator localization/accessibility text

`assets/js/coordinator.js` currently hard-codes most normal rendered English, including queue headings, descriptions, statuses, action labels, workspace headings, field labels, vehicle choices, confirmation copy, success/error fallback copy, and other coordinator text.

This violates the milestone requirement that all new visible/accessibility text use the existing `atlas-shipping` localization surface.

Move normal coordinator UI strings through PHP `__()` / localized configuration, consistent with the accepted M002/M003 localization architecture. Defensive JavaScript fallback strings are acceptable, but the normal execution path must use localized values.

Review accessibility labels/focus behavior at the same time. The final confirmation should expose an appropriate dialog/region structure if that interaction pattern is used, with a meaningful accessible name and deliberate focus entry/return.

## R4 — Request Detail must expose the complete outbound handoff decision after send

The current Request Detail handoff section only renders:

- final equipment
- coordinator notes
- sent timestamp

The persisted handoff contains additional operational decisions. After send, Request Detail must also show where applicable:

- final vehicle/equipment including `other_equipment` when `final_equipment=other`
- coordinator loading-at-pickup decision
- coordinator unloading-at-delivery decision
- coordinator notes
- sent timestamp
- a user-understandable indication that an immutable shipper-handoff snapshot was created / this represents the outbound handoff record

Do not expose raw snapshot JSON, database IDs, or content hashes as normal user-facing detail.

The goal is for PMs and covering users to understand what ATLAS recorded as handed to the shipper.

## Review-scope rules

Address only R1–R4 plus directly necessary styles, localization/config additions, browser/runtime validation, and report updates.

Preserve:

- coordinator/Manager/Administrator server authorization
- Project Manager denial
- submitted-only handoff state
- existing migration `007`
- schema `0.2.0`
- atomic transaction/row lock
- immutable `shipper_handoff` snapshot architecture
- stale/duplicate send protection
- PM edit lock after `sent_to_shipper`
- product target `0.2.0`
- accepted `0.1.9` editor/request-management behavior

Do not add actual email delivery, shipper response/options, scheduling, freight workflow, tracking, delivery verification, Excel, attachments, comments, or cross-user editing.

## Required validation

Use the available WordPress/MySQL/browser runtime and verify:

- coordinator workspace visibly includes the complete populated stop operational fields
- coordinator workspace visibly includes the complete populated item operational fields
- request-level PM handling/loading preferences are visible to coordinator
- preview/confirmation visibly represents current request + current coordinator decision
- changing coordinator decision before preview changes the preview
- cancelling/backing out of confirmation does not send or lock the request
- final send from confirmation creates exactly one immutable snapshot
- snapshot payload matches the confirmed outbound representation
- stale row-version conflict still blocks send
- duplicate send still fails safely
- PM editing still locks after send
- Request Detail shows the complete coordinator outbound decision after send
- normal coordinator UI/accessibility strings use the localized configuration path
- confirmation is keyboard operable with deliberate focus entry/return
- Project Manager cannot execute coordinator API directly
- unauthenticated and CSRF failures remain rejected
- existing request editor autosave/date/focus behavior remains intact
- PHP lint, JavaScript syntax, package boundary, Framework validation pass
- product remains `0.2.0`
- schema remains `0.2.0`

At completion return lifecycle control to Architecture with status `implemented` and next command `Review`.
