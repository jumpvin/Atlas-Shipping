# ATLAS-M001 — Shipping Data Foundation

Status: review_required

Product: ATLAS Shipping Management

Current accepted product build: `0.1.5`

Target product build: `0.1.6`

Current accepted schema version: `0.1.2`

Release line: `0.1.x`

Authorized branch: `bootstrap/atlas-initialization`

Next owner: Builder

Next command: Address Review

## Objective

Establish the permanent backend shipping domain model that future request submission, request management, shipper handoff, scheduling, delivery verification, and Excel transition features will use.

This milestone is backend/domain foundation only. It MUST NOT implement the New Shipping Request editor, request list UI, shipping workflow UI, notifications, or Excel export.

## Architecture Review Findings

Architecture review of implementation commit `9177137fcea6cd3bfd8fcad3287a399bb9faf20f` found five compliance issues that MUST be addressed without expanding milestone scope.

### R1 — Prevent orphan stop/item relationships

Independent stop/item service operations currently accept arbitrary request IDs. Before creating a stop or item, the service MUST verify the parent request exists and is not archived unless an explicitly internal operation permits archived access. Return a stable `WP_Error` on invalid parent request and never create orphan records.

Update/remove operations MUST also preserve aggregate integrity and must not mutate a stop/item that is not attached to a valid request aggregate.

### R2 — Validate stop datetime windows

`window_start` and `window_end` are currently sanitized as text but not validated as canonical database datetimes.

The service MUST validate accepted datetime values deliberately, store one canonical representation, and enforce `window_start <= window_end` when both are present. Invalid datetime/window combinations must return stable `WP_Error` values before persistence.

### R3 — Make snapshot numbering concurrency-safe

Snapshot numbering currently derives `snapshot_no` from `count(existing snapshots) + 1`, which is race-prone.

Implement deliberate concurrency-safe allocation using transaction/locking, atomic allocation, bounded retry after a unique-key collision, or another safe mechanism. The unique DB constraint may remain as a final guard but MUST NOT be the only concurrency strategy.

### R4 — Validate actor identity consistently

Operations that accept `actor_id` MUST verify the actor is a real ATLAS application identity before attributing a domain mutation to it.

This applies to request update/archive, stop create/update/remove, item create/update/remove, and snapshot creation. This is identity-integrity validation only; do NOT introduce role/capability authorization rules in this milestone.

### R5 — Record actor attribution consistently in shipping activity

Shipping activity events for request, stop, item, and snapshot mutations MUST include `actor_identity_id` in safe metadata when an actor is available and validated.

Do not change authentication activity architecture. Do not store secrets or authentication tokens.

## Review-Scope Rules

Address only R1–R5 plus directly necessary tests/validation/reporting. Preserve all accepted `0.1.5` functionality and all already-correct `0.1.6` Shipping Data Foundation architecture.

Do not add UI, REST shipping endpoints, workflow transitions, notifications, Excel functionality, attachments, carrier workflow, or delivery verification.

Product target remains `0.1.6`; schema remains `0.1.3` unless a schema change is genuinely required to satisfy R1–R5. Prefer no schema change.

## Original Milestone Contract

The full canonical milestone requirements remain those established when ATLAS-M001 was activated, including the layered service/repository/model architecture, ordered migration `005`, requests/stops/items/snapshots persistence, public IDs, soft deletion, transactions, diagnostics, packaging, and regression preservation.

## Required Review Validation

After corrections, Builder must validate as strongly as available:

- orphan stop creation rejected
- orphan item creation rejected
- mutations against invalid parent aggregates rejected
- valid canonical stop datetime values accepted
- invalid datetimes rejected
- reversed stop windows rejected
- concurrent or simulated-collision snapshot numbering resolves safely without duplicate numbers
- invalid actor identities rejected across all mutation entry points
- shipping activity includes validated `actor_identity_id`
- existing request CRUD/aggregate behavior preserved
- existing migrations `001`–`004` unchanged
- migration `005` remains upgrade-safe and idempotent
- package boundary remains valid
- product remains `0.1.6`
- schema remains consistent

Use the provided local WordPress test site/runtime if available to obtain real database/runtime evidence. Record what was actually executed; do not fabricate evidence.

## Builder Completion Boundary

Address Review may transition this milestone only from `review_required` back to `implemented`.

At completion:

- Next owner: Architecture
- Next command: Review

Builder must commit and push the review corrections to `origin/bootstrap/atlas-initialization` under the existing milestone authorization. Builder must not mark the milestone accepted/completed and must not begin the next milestone.
