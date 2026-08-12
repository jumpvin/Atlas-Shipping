# ATLAS-M001 — Shipping Data Foundation

Status: active

Product: ATLAS Shipping Management

Current accepted product build: `0.1.5`

Target product build: `0.1.6`

Current accepted schema version: `0.1.2`

Release line: `0.1.x`

Authorized branch: `bootstrap/atlas-initialization`

Next owner: Builder

Next command: Implement Milestone

## Objective

Establish the permanent backend shipping domain model that future request submission, request management, shipper handoff, scheduling, delivery verification, and Excel transition features will use.

This milestone is backend/domain foundation only. It MUST NOT implement the New Shipping Request editor, request list UI, shipping workflow UI, notifications, or Excel export.

## Product Context

ATLAS Shipping replaces fragmented spreadsheet/email shipping coordination with a private frontend application. Project managers will eventually submit standardized shipping requests; shipping coordinators will send an immutable request snapshot to the third-party shipper; carrier/scheduling information will be managed afterward; and project managers will verify final delivery.

The data model created here must support those future workflows without forcing a later redesign.

## Compatibility and Scope Rule

Unless this milestone explicitly states otherwise:

- Preserve all accepted `0.1.5` functionality.
- Preserve passwordless identity and authentication behavior.
- Preserve application sessions and security guarantees.
- Preserve the frontend application shell, routing, responsive behavior, accessibility, and localization.
- Preserve existing migrations `001` through `004` and their identifiers.
- Preserve existing public shortcode and WordPress integration behavior.
- Preserve existing activity records and authentication activity behavior.
- Preserve the adopted WordPress Plugin Suite Profile authority.
- Do not remove, replace, or redesign existing architecture.
- Limit changes to this milestone.
- Do not perform unrelated refactoring or visual redesign.

## Required Architecture

Use a layered domain architecture:

`Future Frontend / REST -> Service Layer -> Repositories -> Database`

Repositories own persistence/query behavior and MUST NOT contain shipping workflow/business orchestration.

Services coordinate repositories, validation, transactions, activity, and domain operations.

Repositories should return model/domain objects (or typed collections of model/domain objects), not expose raw database rows as the public service contract.

The future UI and REST controllers must be able to use services without talking directly to repositories.

## Database Migration

Add the next ordered migration using the existing migration framework. Do not modify migrations `001` through `004`.

Target schema version for this milestone should advance consistently with the new shipping-domain schema.

The migration must be idempotent, upgrade-safe from accepted schema `0.1.2`, and verifiable through existing diagnostics/migration infrastructure.

## Shipping Requests

Create a permanent request table using the existing WordPress table-prefix conventions.

The request is the aggregate root for one shipping request.

Required concepts include at least:

- internal database ID
- non-guess-dependent public request identifier
- owner/requesting identity ID
- created-by identity ID
- updated-by identity ID
- current status
- project ID
- internal ID when applicable
- client
- project/job-site name
- requested ship date
- required delivery date
- delivery-date firmness/flexibility value or future-ready representation
- client-requested indicator where applicable
- project/request notes
- freight cost field reserved for later workflow use
- row/concurrency version or equivalent optimistic concurrency foundation
- created timestamp
- updated timestamp
- submitted timestamp nullable
- sent-to-shipper timestamp nullable
- completed timestamp nullable
- soft-delete/archive marker/timestamp

Do not attempt to place all future shipment information in this table. Stops, items, activity, and snapshots are separate concerns.

## Request Status Vocabulary

Establish canonical status constants/values sufficient for the approved product roadmap.

At minimum anticipate:

- draft
- submitted
- sent_to_shipper
- options_received
- scheduled
- in_transit
- carrier_reported_delivered
- delivery_issue
- delivery_verified
- complete
- cancelled

This milestone establishes vocabulary and persistence validation only.

Do NOT implement workflow transition rules or user-facing status actions yet.

## Public Request Identifier

Each request must receive a stable public identifier separate from its database primary key.

A human-readable format such as `AS-000001` is acceptable for the initial implementation, but generation must be centralized and collision-safe.

Do not require frontend consumers to expose sequential database IDs.

Do not make future format changes require rewriting relationship keys.

## Shipment Stops

Create an ordered stops table supporting normal and multi-stop shipments from the beginning.

The future form will default to one pickup and one delivery, but the schema MUST support additional stops without redesign.

Required concepts include at least:

- ID
- request ID
- stop sequence/order
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
- arrival/window start
- arrival/window end
- appointment-required indicator
- loading/unloading responsibility or handling responsibility representation
- dock availability
- forklift availability
- other equipment/handling notes
- stop-specific instructions
- created timestamp
- updated timestamp

Canonical stop types must support at least:

- pickup
- delivery
- intermediate

The service/repository design must preserve deterministic stop ordering.

## Shipment Items

Create a repeatable shipment-items table.

Required concepts include at least:

- ID
- request ID
- item sequence/order
- quantity
- description
- length
- width
- height
- dimension unit
- weight
- weight basis where needed
- weight unit
- packaging type
- stackable indicator
- fork-pockets indicator
- weather-sensitive indicator
- special-handling/notes
- created timestamp
- updated timestamp

Use numeric/decimal database types appropriate for dimensions and weight. Do not store formatted dimension text as the only dimensional representation.

## Snapshot Foundation

The approved product requires an immutable record of exactly what was sent to the third-party shipper once the coordinator performs `Sent to Shipper` in a later milestone.

Establish the persistence/service foundation necessary for immutable request snapshots now.

The snapshot architecture must be capable of preserving at least:

- request data at snapshot time
- ordered stop data at snapshot time
- ordered item data at snapshot time
- snapshot number/version
- snapshot type
- creating identity
- created timestamp
- amendment/parent relationship where appropriate
- reason/comment where appropriate
- deterministic content fingerprint/hash

A snapshot must contain actual immutable data, not merely a pointer to the current mutable request.

Do NOT implement the frontend `Mark as Sent to Shipper` action yet.

Do NOT implement shipper email generation yet.

## Activity Integration

Use the existing ATLAS activity infrastructure.

Shipping-domain service operations should record safe events such as:

- request_created
- request_updated
- request_deleted or request_archived
- stop_created
- stop_updated
- stop_removed
- item_created
- item_updated
- item_removed
- snapshot_created where snapshot service behavior is implemented/tested

Activity records should identify the request aggregate where applicable and the acting identity when available.

Do not store sensitive credentials, raw authentication tokens, or unnecessary personal technical metadata.

## Repository Layer

Create repositories for the shipping aggregate and its relationships, including at least:

- Request repository
- Stop repository
- Item repository
- Snapshot repository if snapshot persistence is created in this milestone

Responsibilities include:

- persistence CRUD
- lookup by internal ID where internal code requires it
- lookup by public request identifier
- ordered relationship queries
- soft-delete filtering
- explicit inclusion of archived/deleted records when authorized by service behavior

Repositories MUST use prepared queries and WordPress database conventions.

Repositories MUST NOT decide user permissions or workflow transitions.

## Domain Models

Create lightweight domain/model objects for requests, stops, items, and snapshots where applicable.

They should provide a stable typed boundary between repositories and services.

Avoid forcing future REST/UI code to depend on `$wpdb` row shapes.

Do not overbuild a generic ORM.

## Service Layer

Create shipping-domain services sufficient to prove and support the architecture.

At minimum support backend operations for:

- create a draft request
- load a complete request aggregate
- update request foundation fields
- soft-delete/archive a request
- create/update/remove ordered stops
- create/update/remove ordered items
- create/load immutable snapshots if snapshot persistence is included

Services own validation and multi-repository coordination.

No frontend UI should call these services yet.

## Identity Ownership

Use the existing ATLAS application identity system for request ownership and actor attribution.

Do not create a second user system.

The architecture must support the approved future behavior where:

- project managers default to their own requests
- authorized users may see all requests for coverage
- project managers can edit while a request is still Submitted
- shipment details lock for project managers after Sent to Shipper
- project managers later retain limited delivery-verification actions

Do not implement those permission rules yet; ensure the model can support them.

## Validation

Implement backend/domain validation appropriate to this foundation.

Examples:

- canonical status values
- valid identity references where required
- positive item quantity
- nonnegative/valid dimensional values
- valid stop type
- deterministic positive stop/item sequence
- valid email values when supplied
- valid date/time storage values

Return stable internal errors using existing WordPress conventions such as `WP_Error` where appropriate.

Do not expose raw database errors.

## Transactions and Atomicity

Multi-table aggregate operations must not leave partial shipping records.

Use database transactions where supported/appropriate for operations such as creating a request with relationships or replacing/reordering related records.

If transaction support is unavailable, fail conservatively and document the fallback behavior.

Public request identifier generation must be concurrency-safe enough to prevent duplicate identifiers.

## Soft Deletion / Data Retention

Requests must use soft deletion/archive behavior rather than destructive deletion during normal application operations.

Normal request deletion must not permanently remove:

- stops
- items
- snapshots
- activity history
- future documents/attachments

Repositories should exclude soft-deleted requests by default while allowing explicit service-level inclusion when needed.

Do not alter the plugin's conservative uninstall/data-preservation policy.

## Database Indexes

Add useful indexes for expected future queries, including where appropriate:

- request public identifier
- request owner/requesting identity
- request status
- requested ship date
- required delivery date
- created/updated timestamps
- request soft-delete/archive state
- stop request ID + sequence
- item request ID + sequence
- snapshot request ID + snapshot/version

Avoid speculative indexes with no foreseeable use.

## Diagnostics

Extend protected diagnostics to report the shipping-domain foundation safely.

Include at least:

- shipping request table health
- stops table health
- items table health
- snapshot table/architecture health where applicable
- expected vs installed schema version
- shipping repository/service registration or equivalent health indicator

Do not expose customer/shipment data through diagnostics.

## WordPress Profile Requirements

This milestone inherits WordPress Plugin Suite Profile `0.2.0`.

In particular:

- use the existing ordered migration system
- use WordPress database prefixes and prepared queries
- sanitize/validate stored values
- escape only at output boundaries
- preserve activation/upgrade safety
- preserve uninstall/data-retention behavior
- preserve plugin packaging boundaries
- do not load unnecessary frontend/admin assets

## No User Interface

Do NOT implement or replace placeholder UI for:

- New Request
- My Requests
- All Requests
- Needs Attention
- request detail

Do NOT implement:

- request editor
- autosave
- draft-resume UI
- request filters/sorting/pagination
- REST shipping endpoints
- shipper handoff UI
- status transition UI
- carrier/quote/scheduling UI
- delivery verification UI
- notifications
- email delivery
- Excel export/import
- attachments/files
- dashboards/metrics

The milestone is backend/domain only.

## Versioning

Advance the ATLAS product build from `0.1.5` to `0.1.6` consistently where product version metadata requires it.

Advance the schema version consistently with the new migration.

Do not change Framework or extension versions.

Use product version `0.1.6` for the resulting development package.

## Packaging

The resulting development ZIP must remain directly installable through WordPress and contain one top-level plugin directory:

`atlas-shipping/`

Repository-only Framework files, reports, scripts, Git metadata, and build artifacts MUST NOT leak into the plugin ZIP.

## Required Validation

Run the repository operations required by `repository.operations.json` plus milestone-specific validation.

At minimum verify:

- fresh schema migration path through the new shipping migration
- upgrade path from accepted schema `0.1.2`
- migration rerun/idempotency
- expected shipping tables and indexes
- request repository CRUD
- public-ID lookup and uniqueness behavior
- stop ordering and relationship persistence
- item ordering and relationship persistence
- complete aggregate loading
- soft-delete default exclusion and explicit inclusion
- activity recording
- snapshot immutability/persistence behavior where implemented
- transaction rollback on simulated multi-step failure where testable
- PHP syntax/lint
- package boundary
- plugin version `0.1.6`
- schema version consistency

If no live WordPress runtime/database is available, perform the strongest bounded static/isolated validation available and explicitly classify runtime-only checks as not run. Do not fabricate runtime evidence.

## Regression Checklist

Verify and report:

- Existing plugin activation architecture remains intact.
- Existing migrations `001` through `004` remain unchanged.
- Existing passwordless authentication remains intact.
- Existing atomic magic-link behavior remains intact.
- Existing application sessions remain intact.
- Existing identity management remains intact.
- Existing frontend application shell remains intact.
- Existing routing/back-forward behavior remains intact.
- Existing accessibility/session-expiration behavior remains intact.
- Existing diagnostics remain functional.
- Existing shortcode remains functional.
- Existing activity authentication events remain compatible.
- WordPress Plugin Suite Profile lock remains unchanged.
- Framework authority remains valid.
- No request editor or shipping workflow UI was introduced.

## Acceptance Criteria

This milestone is implementation-complete when:

1. The permanent shipping request persistence model exists.
2. Ordered multi-stop persistence exists.
3. Repeatable structured shipment-item persistence exists.
4. Snapshot persistence can support future immutable shipper handoffs without redesign.
5. Repositories provide a clean persistence boundary.
6. Services provide a clean domain/business-operation boundary.
7. Public request identifiers are stable and safely generated.
8. Soft deletion preserves shipment history.
9. Multi-table operations have deliberate atomicity behavior.
10. Shipping activity integrates with the existing activity system.
11. Protected diagnostics can identify shipping-domain schema health.
12. Upgrade from accepted `0.1.5` / schema `0.1.2` is supported.
13. Product version is `0.1.6` and schema version is advanced consistently.
14. Existing accepted functionality remains intact.
15. No shipping request UI or workflow functionality has been introduced.
16. A valid WordPress-installable development ZIP is produced and validated.

## Git Authorization

This milestone authorizes Builder to:

- work only on `bootstrap/atlas-initialization`
- modify product files and repository validation/report files required by this milestone
- commit the complete milestone implementation
- push commits to `origin/bootstrap/atlas-initialization`

This milestone does NOT authorize:

- merging to `master`
- force-pushing
- rebasing published history
- deleting branches
- modifying unrelated repositories
- implementing the next milestone

## Builder Completion Boundary

Builder may transition this milestone only from `active` to `implemented`.

At implementation completion, repository authority must hand control to:

- Next owner: Architecture
- Next command: Review

Builder must not mark the milestone accepted/completed and must not begin the Shipping Request Editor milestone.
