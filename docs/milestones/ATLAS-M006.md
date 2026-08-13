# ATLAS-M006 — Shipper Response & Shipment Details

Status: implemented

Product: ATLAS Shipping Management

Current accepted product build: `0.2.0`

Target product build: `0.2.1`

Current accepted schema version: `0.2.0`

Release line: `0.2.x`

Authorized branch: `bootstrap/atlas-initialization`

Next owner: Architecture

Next command: Review

## Objective

Implement the next coordinator workflow after ATLAS has sent the canonical request to the third-party shipper.

This milestone lets the Shipping Coordinator record what the shipper reports back, capture the actual shipment/carrier details that were set, record freight cost, and move the request from `sent_to_shipper` into a clearly defined scheduled/details-set state.

The workflow remains manually entered by the coordinator. ATLAS does not parse shipper email or send/receive real email in this milestone.

## Product Context

Current accepted flow:

1. PM submits request.
2. Coordinator reviews and sends the immutable canonical request to the shipper.
3. Request becomes `sent_to_shipper`; PM shipment-detail editing locks.
4. Third-party representative responds with the shipment they found/set up.
5. Coordinator records those returned details in ATLAS.
6. Once actual shipment details are confirmed, the request becomes scheduled/details set.
7. Later milestones handle transit, carrier-reported delivery, PM delivery verification, notifications, and Excel transition/export.

The third-party representative is not an ATLAS user in this milestone.

## Compatibility

Preserve all accepted `0.2.0` behavior, including coordinator authorization, Needs Attention handoff queue, immutable shipper-handoff snapshots, PM edit locking after handoff, full request-management experience, New Request editor, authentication/session security, migrations `001`–`007`, schema `0.2.0`, Framework authority, and WordPress Plugin Suite Profile `0.2.0`.

## Coordinator Authorization

Use the same server-authorized coordinator roles accepted in ATLAS-M005:

- Shipping Coordinator
- Manager
- Administrator

Project Managers may view the returned shipment information but may not mutate it.

Never trust client-supplied role or actor identity.

## Needs Attention Evolution

Expand Needs Attention beyond only submitted handoffs.

For coordinators, clearly distinguish at least:

- Submitted — needs outbound handoff
- Sent to Shipper — awaiting/needs shipper response details

The queue should help the coordinator understand the next action immediately.

Do not create an overly complex workflow dashboard yet.

## Shipper Response Workspace

For a request in `sent_to_shipper`, provide a coordinator workspace to record the response/details returned by the third-party shipper.

The workspace should preserve context from the immutable outbound handoff and make it easy to compare what was requested with what was actually arranged.

Display the canonical outbound handoff summary without exposing raw snapshot JSON.

## Actual Shipment Details

Persist coordinator-owned actual shipment details separately from PM-requested and outbound-requested values.

At minimum support:

- carrier/provider name
- actual vehicle/equipment type
- other equipment description when applicable
- carrier/driver contact name where provided
- carrier/driver phone where provided
- carrier/driver email where provided
- pickup scheduled date/time or window
- delivery scheduled date/time or window
- freight cost
- currency (initial default USD is acceptable)
- reference / confirmation / load number where provided
- coordinator notes about the shipper response

Do not overwrite the PM preference or coordinator outbound handoff decision.

The data model must make these three concepts distinguishable:

1. PM requested/preferred information
2. Coordinator outbound handoff request
3. Actual shipment details returned/confirmed by the shipper

## Multi-Stop Scheduling

The existing request model supports multi-stop shipments. Do not design actual scheduling in a way that assumes there can only ever be one pickup and one delivery.

For this milestone, a shipment-level pickup and delivery schedule summary may be used for the common case, but persistence must be future-ready for stop-level scheduled windows without destructive redesign.

If practical within bounded scope, allow actual scheduled window overrides per stop. If not, document the deliberate future extension point.

## Persistence

Add the smallest dedicated persistence model for shipper response / actual shipment details.

Prefer a separate shipment-details/shipper-response record related one-to-one with the request for the initial workflow, with clear future extension for amendments or stop-level schedules.

Do not store actual shipment values only inside activity metadata or snapshot JSON.

Use the next ordered migration if required and advance schema consistently. Preserve migrations `001`–`007` byte-for-byte.

## Draft Saving

Coordinator should be able to begin entering shipper response information and save/resume it before marking details final.

Autosave or explicit Save is acceptable; prioritize reliability and low friction.

A partially entered response must NOT advance request status to scheduled.

Project Managers must not be able to mutate this record.

## Mark Details Set / Scheduled

Provide a deliberate coordinator action after the actual shipment information is sufficiently complete.

Use user-facing wording such as `Confirm Shipment Details` or `Mark Scheduled`; do not expose an awkward internal status name.

On successful confirmation:

- validate coordinator authorization
- verify request status is `sent_to_shipper`
- verify expected concurrency/version
- validate required actual shipment fields
- persist final actual shipment details
- transition request to canonical `scheduled`
- record a scheduled/details-confirmed timestamp
- advance request row version
- record activity
- commit atomically

If validation or persistence fails, the request must remain `sent_to_shipper`.

## Required Actual Fields

Do not over-require information that Kindle may not always provide.

For final confirmation, require at minimum:

- carrier/provider name OR an explicit `not yet provided` representation if the workflow genuinely permits it
- actual vehicle/equipment type
- enough pickup/delivery schedule information to understand the arranged shipment

Freight cost should be supported and normally expected, but if real operations sometimes receive cost later, permit a deliberate `pending/not provided` state rather than forcing fake values.

Do not silently treat zero cost as `unknown`.

## Freight Cost

Freight cost is visible to all authenticated ATLAS users with request visibility, matching the approved product decision.

Store money using an appropriate decimal representation, not floating-point binary arithmetic.

Display currency clearly.

Do not implement invoicing/accounting functionality.

## Request Detail

After shipper response information exists, Request Detail should show a clearly separated `Actual Shipment Details` section.

Include where available:

- carrier/provider
- actual vehicle/equipment
- carrier/driver contact
- scheduled pickup
- scheduled delivery
- freight cost + currency
- confirmation/reference number
- coordinator response notes
- details confirmed/scheduled timestamp

The page should make it easy to understand the progression:

PM Request -> Shipper Handoff -> Actual Shipment Details

Do not expose raw persistence records or snapshot JSON.

## PM Experience

Project Managers can view actual shipment details as soon as they are saved, including freight cost.

They remain unable to edit the original shipment details after `sent_to_shipper`.

They do not confirm or choose the carrier in this milestone.

Do not implement PM delivery verification yet.

## Status / Lists

Existing request lists must clearly render `sent_to_shipper` and `scheduled`.

Scheduled requests should remain in Upcoming / Active until later delivery/completion states move them into past/completed behavior.

Needs Attention should stop treating a request as awaiting shipper response once it is scheduled.

## Concurrency

Coordinator response editing and final confirmation must respect request concurrency.

A stale coordinator screen must not overwrite newer response data or confirm an outdated shipment state.

Two coordinators must not be able to independently finalize conflicting shipment details.

Use deliberate row-version/transaction behavior consistent with prior milestones.

## Activity

Record safe activity for at least:

- shipper response/details saved
- shipment details confirmed / scheduled

Include acting identity and request aggregate identifiers.

Do not duplicate sensitive contact/shipment content unnecessarily into activity metadata.

## API Security

Add only the authenticated API required for response loading/saving/finalization.

Mutations require:

- custom ATLAS session authentication
- CSRF protection
- coordinator role authorization
- server-derived actor identity
- validation
- concurrency protection

Unauthenticated and Project Manager direct API attempts must be rejected.

## UX

Keep the workspace operational and calm.

The coordinator should immediately understand:

- what ATLAS sent
- what the shipper returned
- what information is still missing
- whether the shipment is ready to mark scheduled

Use cards and hierarchy rather than spreadsheet density.

Do not require re-entering data that can safely be prefilled from the coordinator handoff. Prefill may be offered, but actual returned values must remain explicitly editable and separately persisted.

## Accessibility / Localization

All visible and accessibility strings must use the `atlas-shipping` localization surface.

Forms require labels, visible focus, accessible errors, and keyboard-operable final confirmation.

Do not repeat prior hard-coded coordinator-JavaScript localization regressions.

## Email Boundary

Do NOT implement:

- actual email to shipper
- inbound email parsing
- automatic extraction from Kindle's reply
- mailbox integration

The coordinator manually records returned information.

## Database / Migration

A new migration is expected for actual shipment details.

Use migration `008` if no intervening authorized migration exists.

Preserve `001`–`007` unchanged.

Migration must be fresh-install safe, upgrade safe from schema `0.2.0`, and idempotent under the existing migration architecture.

Advance schema consistently.

## Versioning

Advance product build:

`0.2.0` -> `0.2.1`

Advance schema only as required by the new persistence migration.

Do not change Framework or extension versions.

## Explicitly Out of Scope

Do NOT implement:

- real shipper email delivery
- inbound email parsing
- multiple quote comparison workflow
- automated carrier selection
- live tracking integration
- in-transit event ingestion
- carrier-reported delivery
- PM delivery verification
- delivery issue resolution
- notification preferences
- configurable email recipients
- Excel export/import
- attachments/documents
- accounting/invoicing

## Required Runtime Validation

Use the available local WordPress/MySQL/browser environment.

At minimum verify:

- upgrade from accepted `0.2.0`
- migration `008` fresh/upgrade/rerun behavior if added
- coordinator can see sent-to-shipper requests requiring response
- Project Manager cannot access response mutation APIs
- coordinator can load outbound handoff context
- partial response can be saved without changing request status
- actual shipment data remains distinct from PM and handoff data
- carrier/provider persistence
- actual equipment + other equipment persistence
- contact persistence
- scheduled pickup/delivery persistence
- freight cost decimal/currency persistence, including zero vs unknown behavior
- reference number and notes persistence
- PM can view saved actual shipment details
- PM still cannot edit original shipment request
- final confirmation transitions exactly `sent_to_shipper` -> `scheduled`
- scheduled timestamp populated
- stale finalization rejected
- duplicate finalization rejected
- simulated failure rolls back status/details finalization atomically where testable
- Needs Attention queue removes scheduled request from response-needed state
- request lists render scheduled appropriately
- request detail renders PM Request / Shipper Handoff / Actual Shipment Details distinctly
- unauthenticated response API rejected
- CSRF failure rejected
- existing coordinator handoff remains functional
- existing New Request autosave/date/focus behavior remains intact
- PHP lint
- JavaScript syntax
- package boundary
- product `0.2.1`
- schema/version consistency

## Acceptance Criteria

ATLAS-M006 is implementation-complete when:

1. Needs Attention distinguishes submitted handoff work from sent-to-shipper response work.
2. Coordinators can record and resume shipper-returned actual shipment details.
3. PM requested data, outbound handoff data, and actual shipment data remain distinct.
4. Carrier/equipment/contact/schedule/freight/reference/notes can be persisted.
5. PMs can view actual shipment details but cannot mutate them.
6. Partial response saves do not advance workflow status.
7. Final confirmation atomically transitions the request from `sent_to_shipper` to `scheduled`.
8. Stale/duplicate coordinator finalization fails safely.
9. Request Detail clearly presents the three-stage information progression.
10. Existing accepted handoff/request/editor/security behavior remains intact.
11. No email, tracking, delivery verification, or Excel workflow is introduced.
12. Product build is `0.2.1`, schema advances consistently if required, and a valid WordPress-installable development ZIP is produced.

## Packaging

Produce `atlas-shipping-0.2.1.zip` with one top-level `atlas-shipping/` directory and no repository-only Framework files, reports, test harnesses, or Git metadata.

## Git Authorization

Builder is authorized to work only on `bootstrap/atlas-initialization`, modify files required by this milestone, commit the implementation, and push to `origin/bootstrap/atlas-initialization`.

Builder is NOT authorized to merge to `master`, force-push, rebase published history, delete branches, modify unrelated repositories, or begin the next milestone.

## Builder Completion Boundary

Builder may transition this milestone only from `active` to `implemented`.

At completion:

- Next owner: Architecture
- Next command: Review

Builder must not mark the milestone accepted/completed and must not begin transit/delivery workflow.
