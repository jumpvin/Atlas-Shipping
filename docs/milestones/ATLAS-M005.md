# ATLAS-M005 — Shipping Coordinator Handoff

Status: active

Product: ATLAS Shipping Management

Current accepted product build: `0.1.9`

Target product build: `0.2.0`

Current accepted schema version: `0.1.4`

Authorized branch: `bootstrap/atlas-initialization`

Next owner: Builder

Next command: Implement Milestone

## Objective

Implement the first Shipping Coordinator workflow after a Project Manager submits a shipping request.

The milestone ends when an authorized coordinator can review a submitted request, make the final outbound handoff decision, create an immutable snapshot of exactly what is being sent, mark the request as sent to the third-party shipper, and leave the request ready for the later response/options workflow.

This milestone does not send real email to Kindle or implement quote/carrier response handling yet.

## Product Workflow

Current approved flow:

1. Project Manager creates and submits a request.
2. Submitted request remains editable by its owner until the Shipping Coordinator sends it to the shipper.
3. Shipping Coordinator reviews the submitted request.
4. Coordinator may determine final transportation/handling choices based on the PM's facts and optional preference/comment.
5. Coordinator deliberately performs `Send to Shipper`.
6. ATLAS creates an immutable snapshot of the exact outbound request.
7. Request transitions to `sent_to_shipper`.
8. Normal PM shipment-detail editing becomes locked.
9. Later milestones will record Kindle/shipper responses, options, scheduling, freight cost, transit, and delivery verification.

## Compatibility

Preserve all accepted `0.1.9` behavior, including authentication, full-width application layout, New Request editor, uninterrupted autosave, date handling, My Requests, All Requests, request detail, cross-user read, owner-only editing, aggregate concurrency, migrations `001`–`006`, snapshots foundation, activity, Framework authority, and WordPress Plugin Suite Profile `0.2.0`.

## Coordinator Authorization

Use the existing ATLAS identity roles. Do not create WordPress-user authorization.

For this milestone, coordinator workflow actions are authorized for ATLAS identities with role:

- `Shipping Coordinator`
- `Manager`
- `Administrator`

Project Manager identities may continue viewing requests according to accepted cross-coverage behavior but may not execute coordinator handoff actions.

Authorization MUST be enforced server-side. Hiding buttons is not sufficient.

Disabled identities must not retain usable application sessions under existing identity/session rules.

## Needs Attention

Replace the `Needs Attention` placeholder with the first coordinator work queue.

For authorized coordinator roles, the queue should prioritize submitted requests that have not yet been sent to the shipper.

Each queue item should expose useful summary context such as request ID, project, owner, requested ship date, required delivery date, pickup, delivery, and last updated time.

Provide an obvious action to open the coordinator handoff workspace.

For Project Manager identities, do not expose coordinator mutation controls. The navigation item may be hidden or may show an appropriate non-coordinator/empty state, but server authorization remains authoritative.

## Coordinator Handoff Workspace

Provide a focused workspace for one submitted request.

The coordinator must be able to inspect the complete operational request before sending it, including request/project information, ordered stops, items, contacts, windows, handling information, notes, and PM transportation preference/comment.

Do not force the coordinator to switch back and forth between the normal read-only detail view and a separate incomplete form.

The workspace should make the final outbound decision and the `Send to Shipper` action visually clear without becoming overwhelming.

## Final Transportation / Handling Decision

The PM's `preferred_equipment` and `transportation_comment` are advisory. The coordinator and third-party shipper normally make the final transportation decision.

Add coordinator-owned handoff fields sufficient for the initial outbound request, including at minimum:

- final/requested vehicle or equipment type
- coordinator transportation/handling notes

If useful and compatible with the current model, the coordinator may also explicitly confirm/override loading/unloading responsibility for the outbound handoff.

Do not overwrite the PM's original preference/comment. Preserve both the PM-provided input and coordinator outbound decision distinctly.

Vehicle/equipment should support a practical initial vocabulary while allowing an `other`/free-text path. Smaller shipments may prefer vans because they are cheaper, but do not encode automatic vehicle-selection business logic.

## Persistence

Add the smallest permanent persistence needed for coordinator-owned outbound handoff data.

Prefer a dedicated handoff/outbound-request record related to the shipping request rather than mixing mutable coordinator response data into PM-owned request fields.

The persistence design must be future-ready for later shipper responses and amendments without turning this milestone into the entire carrier-management system.

If a new table/columns are required, add the next ordered migration and advance schema consistently. Do not modify migrations `001`–`006`.

## Send-to-Shipper Transaction

`Send to Shipper` is a deliberate, server-authorized state transition and MUST be atomic.

In one service-layer transaction, the operation must:

1. verify the acting identity is coordinator-authorized
2. verify the request exists and is currently `submitted`
3. verify the expected request `row_version` / concurrency claim
4. validate required outbound coordinator fields
5. persist the coordinator handoff/outbound record
6. create an immutable snapshot containing the exact outbound request data
7. transition request status to `sent_to_shipper`
8. set `sent_to_shipper_at`
9. advance request concurrency/version state
10. record safe activity events
11. commit everything together

Any failure must roll back the entire transition. ATLAS must never show `sent_to_shipper` without its corresponding immutable snapshot/outbound handoff data.

## Immutable Snapshot

Use and extend the accepted snapshot architecture rather than inventing a second history mechanism.

The handoff snapshot must preserve the exact outbound content, including at least:

- PM request fields
- ordered stops
- ordered items
- PM transportation preference/comment
- coordinator final vehicle/equipment decision
- coordinator outbound notes
- relevant loading/unloading decisions
- public request ID
- snapshot number/version
- snapshot type identifying shipper handoff
- creating coordinator identity
- timestamp
- deterministic content hash

After creation, snapshot content is immutable.

The snapshot must not merely point to mutable current request data.

## PM Lock Boundary

After successful transition to `sent_to_shipper`:

- the PM may still view the request
- the PM may not edit shipment details through New Request
- autosave/mutation APIs must reject attempts consistently
- Continue Editing should no longer appear
- the request should remain visible in My Requests / All Requests

This must be enforced by existing service/API editability rules, not only by frontend presentation.

## Concurrency

If the PM changes the submitted request while the coordinator has an older handoff workspace open, the coordinator's stale `Send to Shipper` attempt must fail safely rather than snapshotting stale information.

The coordinator should receive a clear conflict message and reload/review the latest request before sending.

Likewise, two coordinators must not be able to successfully send the same request twice.

## Outbound Preview

Before the irreversible handoff transition, provide a clear preview/confirmation of what will be sent.

The preview should emphasize operational information rather than raw JSON/database data.

At minimum show:

- request/project identity
- PM/request owner
- requested ship/delivery dates
- stops
- items
- handling/loading information
- PM transportation preference/comment
- coordinator final vehicle/equipment decision
- coordinator notes

The final action must be explicit, such as `Send to Shipper`.

Do not use a native browser `confirm()` as the primary final workflow. Use an in-application confirmation/preview pattern consistent with the ATLAS UI.

## Email Boundary

Do NOT send real email to Kindle/third-party shipper in this milestone.

However, structure the handoff service so a later email-delivery milestone can consume the immutable outbound snapshot rather than rebuilding shipment data from mutable tables.

The snapshot becomes the canonical payload for future outbound email/export generation.

## Activity

Record safe activity for at least:

- coordinator handoff draft/update if such mutable persistence exists
- request sent to shipper
- handoff snapshot created

Include acting identity and request aggregate identifiers where appropriate.

Do not duplicate sensitive shipment content into activity metadata.

## Request Lists and Detail

Update existing request-management presentation so `sent_to_shipper` is clear.

Request Detail should show coordinator outbound handoff information after the send action, while keeping the immutable snapshot/history concept understandable.

Do not expose raw snapshot JSON.

Coordinator users should have an obvious route from Needs Attention and appropriate submitted request detail into the handoff workspace.

## API

Add only the authenticated API surface required for:

- coordinator queue
- load handoff workspace
- save coordinator handoff fields if autosave/draft persistence is used
- preview outbound handoff
- execute Send to Shipper
- read safe outbound handoff information on request detail

All mutation endpoints require existing custom-session authentication, CSRF protection, coordinator role authorization, validation, and stable errors.

Never trust a client-supplied actor/role.

## UX

Keep the interface simple and calm.

The coordinator should feel like ATLAS is presenting the next operational decision, not a giant admin form.

Use the accepted full-width application shell, card hierarchy, status indicators, responsive behavior, and localization architecture.

Avoid unnecessary clicks, but preserve deliberate confirmation for the send action because it locks PM editing and creates the canonical outbound record.

## Accessibility / Localization

All new visible and accessibility text must use the `atlas-shipping` localization surface.

The confirmation/preview must be keyboard operable, focus managed, and understandable without relying on color alone.

## Database / Migration

A schema change is expected if coordinator handoff persistence is added.

Use the next ordered migration, preserve `001`–`006` byte-for-byte, and advance schema consistently from `0.1.4`.

Migration must be fresh-install safe, upgrade safe from `0.1.4`, and idempotent under the existing migration architecture.

## Versioning

This milestone begins the next feature line:

- accepted build: `0.1.9`
- target build: `0.2.0`

Advance schema only as required by the handoff persistence migration.

Do not change Framework or extension versions.

## Explicitly Out of Scope

Do NOT implement:

- actual email delivery to Kindle/shipper
- configurable email recipients
- shipper reply ingestion
- quote/options comparison
- carrier assignment
- pickup/delivery scheduling confirmation
- freight cost workflow beyond preserving existing reserved field
- in-transit tracking
- carrier-reported delivery
- PM delivery verification
- delivery issue resolution
- Excel export/import
- attachments/documents
- comments/discussion system
- automated vehicle selection

## Required Runtime Validation

Use the available local WordPress/MySQL/browser environment.

At minimum verify:

- upgrade from accepted `0.1.9`
- fresh migration and rerun/idempotency for any new migration
- coordinator role can access Needs Attention handoff queue
- Manager/Administrator authorization behaves as specified
- Project Manager cannot execute coordinator APIs even if calling them directly
- submitted requests appear in coordinator queue
- draft and already-sent requests do not appear as actionable submitted queue items
- coordinator workspace renders full request data
- PM preference remains distinct from coordinator decision
- coordinator handoff fields persist correctly
- outbound preview matches the current request aggregate and coordinator decision
- successful Send to Shipper creates exactly one immutable snapshot
- snapshot content matches the outbound preview/canonical handoff payload
- request status becomes `sent_to_shipper`
- `sent_to_shipper_at` is populated
- PM editing becomes server-rejected after send
- Continue Editing disappears after send
- request remains viewable in My Requests / All Requests / Request Detail
- stale coordinator workspace fails on row-version conflict
- duplicate/concurrent send cannot create two successful handoffs
- simulated failure during handoff rolls back status, snapshot, and handoff persistence together where testable
- activity records contain acting identity
- unauthenticated coordinator API rejected
- CSRF failure rejected on mutations
- existing New Request autosave/date/focus behavior remains intact for editable requests
- existing request management remains intact
- PHP lint
- JavaScript syntax
- package boundary
- product `0.2.0`
- schema/version consistency

## Acceptance Criteria

ATLAS-M005 is implementation-complete when:

1. Needs Attention is a real coordinator queue for submitted requests.
2. Authorized coordinators can open a complete handoff workspace.
3. PM transportation preference remains advisory and distinct from coordinator final decision.
4. Coordinator outbound decision is persisted deliberately.
5. Send to Shipper is server-authorized and atomic.
6. A successful send creates exactly one immutable canonical outbound snapshot.
7. Snapshot content represents exactly what is being handed to the shipper.
8. Request transitions from `submitted` to `sent_to_shipper` with timestamp/version/activity.
9. PM shipment-detail editing is locked after handoff while viewing remains available.
10. Stale/duplicate/concurrent sends fail safely.
11. Request Detail exposes the handoff state without raw snapshot internals.
12. Existing `0.1.9` behavior remains intact.
13. No actual shipper email or response/options workflow is introduced.
14. Product build is `0.2.0`, schema is advanced consistently if required, and a valid WordPress-installable development ZIP is produced.

## Packaging

Produce `atlas-shipping-0.2.0.zip` with one top-level `atlas-shipping/` plugin directory and no repository-only Framework files, reports, Git metadata, or test harnesses.

## Git Authorization

Builder is authorized to work only on `bootstrap/atlas-initialization`, modify files required by this milestone, commit the implementation, and push to `origin/bootstrap/atlas-initialization`.

Builder is NOT authorized to merge to `master`, force-push, rebase published history, delete branches, modify unrelated repositories, or begin the next milestone.

## Builder Completion Boundary

Builder may transition this milestone only from `active` to `implemented`.

At completion:

- Next owner: Architecture
- Next command: Review

Builder must not mark the milestone accepted/completed and must not begin shipper response/options work.
