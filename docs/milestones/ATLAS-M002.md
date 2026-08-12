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

## Architecture Review Findings

### Resolved — R1 Meaningful stop/item first input

The Builder corrected initial draft creation so meaningful stop or item input can create and persist the first draft while blank default shells still create no database draft. Runtime validation passed.

### Resolved — R2 New Request localization

The Builder moved normal editor-visible strings to the PHP-provided localized configuration surface. Runtime/static validation of the translation map passed.

### Resolved in intent but still requires one atomicity correction — R3 Aggregate concurrency

The Builder now requires request `row_version` on stop/item mutations and stale child mutations correctly return conflict in runtime testing. Successful child operations also result in a newer aggregate version.

However, the current controller implementation performs these as two separate operations:

1. `touch()` calls `Service::update_request(..., array(), expected_row_version, ...)`, which increments the parent request `row_version` and `updated_at`.
2. The stop/item create/update/delete operation runs afterward.

This is not an atomic aggregate mutation. If child validation/persistence fails, the request version has already advanced even though the requested child mutation did not succeed. A process/database failure between the touch and child write has the same effect. The milestone contract requires successful child mutations to atomically validate the expected aggregate version, perform the child mutation, and advance the parent request version/timestamp.

#### R3A — Make child mutation + parent version advancement atomic

Correct stop/item create/update/delete/reorder editor operations so the following occur within one deliberate service/domain transaction boundary:

- validate the acting identity and editable request aggregate
- conditionally claim/check the expected request `row_version`
- validate and perform the requested child mutation
- advance the parent request `row_version` and `updated_at` only when the child mutation succeeds
- commit both together

If validation or child persistence fails, rollback so the parent version/timestamp does not advance.

If the expected version is stale, return the existing stable `atlas_request_conflict` semantics with no child or parent mutation.

The controller should call an aggregate-aware service operation rather than independently touching the request and then performing a child repository/service call.

This applies to editor stop/item create, update, remove, and reorder persistence.

Do not add unrelated authorization/workflow behavior.

## Review-Scope Rules

Address only R3A plus directly necessary runtime tests/report updates and small service/controller refactoring required to provide an atomic aggregate mutation boundary.

Preserve the accepted `0.1.6` foundation and all already-correct `0.1.7` editor behavior. Preserve migrations `001`–`005`. Migration `006` and schema `0.1.4` remain unless a genuine schema correction is required; no schema change is expected for R3A.

Do not add My Requests / All Requests lists, coordinator workflow, shipper handoff, snapshots on submission, notifications, Excel functionality, attachments, comments, freight-cost workflow, or delivery verification.

## Required Review Validation

Use the available local WordPress/MySQL runtime and validate at minimum:

- stale stop create/update/remove/reorder returns conflict and mutates neither child state nor parent version
- stale item create/update/remove/reorder returns conflict and mutates neither child state nor parent version
- valid stop child mutation succeeds and advances parent row_version exactly as the aggregate contract expects
- valid item child mutation succeeds and advances parent row_version exactly as the aggregate contract expects
- deliberately invalid stop/item child mutation does not advance parent row_version or updated_at
- simulated child persistence failure rolls back any parent version/timestamp change where practical to test
- child mutation response returns the current aggregate version and frontend retains it
- latest-draft ordering remains correct after successful child-only edits
- previously passing R1 first-input behavior remains intact
- previously passing R2 localization remains intact
- request-field optimistic concurrency remains intact
- submission remains deliberate and creates no snapshot
- submitted requests remain editable
- sent-to-shipper/later requests remain locked
- unauthenticated API remains rejected
- package boundary and product/schema versions remain correct
- PHP and JavaScript syntax pass

Record only tests actually executed.

## Original Milestone Contract

The original canonical milestone requirements remain in force, including:

- one scrolling card-based editor, expanded by default
- isolated New Request page module
- custom-session authenticated REST boundary with CSRF protection
- server-derived identity ownership
- editability for `draft` and `submitted`, lock at `sent_to_shipper` and later
- meaningful-input autosave with 800–1500 ms debounce
- latest-active-draft resume policy
- strict deliberate submission to `submitted`
- no snapshot on submission
- Project / Request Details card
- ordered multi-stop editor initialized with Pickup and Delivery
- repeatable structured Shipment Items
- Transportation / Handling Preferences
- shipment-level special instructions via the appropriate request notes field
- structured server-side submission validation
- safe activity attribution without autosave log flooding
- aggregate-wide `row_version` conflict handling
- accessibility, localization, loading/error/success states
- no later coordinator/workflow/list/notification/Excel features
- product target `0.1.7`
- schema target `0.1.4` with migration `006`
- directly installable WordPress development ZIP with repository-only files excluded

## Builder Completion Boundary

Address Review may transition this milestone only from `review_required` back to `implemented`.

At completion:

- Next owner: Architecture
- Next command: Review

Builder must commit and push review corrections to `origin/bootstrap/atlas-initialization` under the existing milestone authorization. Builder must not mark the milestone accepted/completed and must not begin the next milestone.
