# ATLAS-M002 — Shipping Request Editor

Status: completed
Product: ATLAS Shipping Management
Accepted product build: `0.1.7`
Accepted schema version: `0.1.4`
Release line: `0.1.x`
Authorized branch: `bootstrap/atlas-initialization`
Next owner: Architecture
Next command: Create Milestone

## Objective

Replace the `New Request` placeholder with the first real ATLAS shipping workflow: a polished single-page request editor backed by the accepted Shipping Data Foundation.

Authenticated project managers can enter a request without page reloads, autosave meaningful work as a draft, manage pickup/delivery and additional stops, manage shipment items, and deliberately submit the request. This milestone ends at `submitted`; it does not implement Shipping Coordinator or third-party shipper workflow.

## Accepted Architecture Review

Architecture accepted implementation commit `d24a01dac417d9954a038e35c7e8174d726018fa` after review correction cycles.

Resolved findings:

- R1 — meaningful stop/item first input creates the first draft while blank default shells do not create a draft.
- R2 — normal New Request UI strings use the PHP-provided WordPress localization surface.
- R3 — aggregate-wide optimistic concurrency covers request, stop, and item editor mutations.
- R3A — child persistence and parent request row-version/timestamp advancement are committed or rolled back within one service-layer transaction boundary.

Live WordPress/MySQL validation passed for valid, stale, and invalid child mutation cases, including rollback behavior, first-input autosave, localization, submission, locking, and request-field concurrency.

## Accepted Scope

The completed milestone includes:

- one scrolling card-based New Request editor, expanded by default
- isolated New Request page module
- custom-session authenticated REST boundary with CSRF protection
- server-derived identity ownership
- editability for `draft` and `submitted`, lock at `sent_to_shipper` and later
- meaningful-input autosave with debounce
- latest-active-draft resume policy
- deliberate submission to `submitted`
- no snapshot on submission
- Project / Request Details
- ordered multi-stop editor initialized with Pickup and Delivery
- repeatable structured Shipment Items
- Transportation / Handling Preferences
- shipment-level special instructions via request notes
- structured server-side submission validation
- safe activity attribution without autosave log flooding
- aggregate-wide `row_version` conflict handling
- localized editor strings
- migration `006` and schema `0.1.4`
- WordPress-installable development ZIP

## Explicitly Not Implemented

This milestone does not include:

- My Requests / All Requests / Needs Attention real lists
- Shipping Coordinator workspace
- send-to-shipper action
- shipper snapshot action
- carrier quote/options or assignment
- final equipment assignment
- scheduling workflow
- freight-cost workflow
- delivery verification/issues
- notifications/email/configurable recipients
- Excel export/import
- attachments
- comments/discussion
- dashboards/metrics

## Acceptance Evidence

Accepted development artifact:

- product build: `0.1.7`
- schema: `0.1.4`
- artifact: `build/dev/atlas-shipping-0.1.7.zip`
- SHA-256: `8f6220f85b7360fe906d16bba0075086e654dea4ae4f34f155c57386127d1892`

Validation included PHP lint, JavaScript syntax, package boundary, WordPress 7.0.4 / MySQL 8.4 migration and runtime checks, authenticated and unauthenticated API behavior, autosave/resume, submission validation, valid submission, two-tab conflicts, locked-status boundary, no snapshot on submission, activity attribution, first-input draft behavior, localization, aggregate concurrency, and atomic child mutation rollback.

Visual 320px inspection and automated accessibility tooling were not run in the Builder validation environment; the accepted implementation remains subject to normal product-level visual testing as the UI evolves.

## Lifecycle

ATLAS-M002 is complete. Architecture may create the next product milestone. Builder must not begin later scope without a new active milestone.
