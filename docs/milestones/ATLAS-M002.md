# ATLAS-M002 — Shipping Request Editor

Status: review_required
Product: ATLAS Shipping Management
Current accepted product build: `0.1.6`
Target product build: `0.1.7`
Current accepted schema version: `0.1.3`
Release line: `0.1.x`
Authorized branch: `bootstrap/atlas-initialization`
Next owner: Builder
Next command: Address Review

## Objective

Replace the `New Request` placeholder with the first real ATLAS shipping workflow: a polished single-page request editor backed by the accepted Shipping Data Foundation.

Authenticated project managers must be able to enter a request without page reloads, autosave meaningful work as a draft, manage pickup/delivery and additional stops, manage shipment items, and deliberately submit the request. This milestone ends at `submitted`; it does not implement Shipping Coordinator or third-party shipper workflow.

## Architecture Review Findings

Architecture review of implementation commit `7bb0277b71dfa0cd3cfb382c3e3e7e98902775cc` found three compliance issues that MUST be corrected without expanding milestone scope.

### R1 — Meaningful stop/item input must be able to create the first draft

The browser-side `meaningful()` policy correctly treats a meaningful stop location or item description as sufficient to begin autosave. However, the `POST /draft` controller currently evaluates only request-level fields (`project_id`, `client`, `project_name`, dates, notes), and the browser sends only `x.request` to that endpoint. Therefore a user who begins by entering a pickup/delivery location or item description can receive `atlas_draft_not_meaningful` instead of having that meaningful work persisted.

Correct the initial-draft contract so the same meaningful-input policy is enforced coherently on client and server. A meaningful stop location or meaningful item description MUST be sufficient to create the first draft. Do not let default blank pickup/delivery/item shells create a draft. The server remains authoritative for deciding whether initial input is meaningful.

The solution may create the request aggregate and initial children in one service operation or use another atomic/coherent approach, but it MUST NOT create orphan children or partial first-save state.

### R2 — All visible New Request editor strings must use the localization authority

`assets/js/new-request.js` contains many hard-coded user-visible English strings, including field labels, card headings, button labels, checkbox labels, accessible move labels, removal confirmation, submission success copy, and validation heading.

The milestone explicitly requires all visible PHP/JS text to be translatable through the `atlas-shipping` text domain and existing localization pattern.

Move the normal UI strings into the PHP-provided localized string/configuration surface and consume them from JavaScript. Defensive fallback strings may remain where appropriate, but normal rendered output must originate from translatable WordPress strings. This includes accessibility labels and the persisted-record removal confirmation.

### R3 — Optimistic concurrency must protect aggregate child mutations, not only request-row PATCH

The request row uses `row_version` for request-level PATCH and submission, but stop/item create/update/delete/reorder endpoints currently mutate the aggregate without requiring/advancing the request concurrency version. This allows two open editor tabs to change the same stop/item aggregate without the stale tab receiving the required conflict response. It also means child-only edits do not necessarily advance the request's `updated_at`/`row_version`, weakening the latest-draft/resume ordering contract.

Extend the accepted optimistic-concurrency boundary across editor aggregate mutations. Stop/item create, update, remove, and reorder persistence initiated by the editor MUST validate the expected request `row_version`, fail with the same stable conflict semantics when stale, and advance/touch the aggregate request version after a successful child mutation. The frontend must carry forward the returned current version and surface a safe conflict/reload state rather than silently overwriting newer aggregate state.

Do not add role/cross-user permission behavior or unrelated workflow logic.

## Review-Scope Rules

Address only R1–R3 plus directly necessary tests, localization/configuration additions, validation/report updates, and small refactoring required to keep the implementation coherent.

Preserve the accepted `0.1.6` foundation and all already-correct `0.1.7` editor behavior. Preserve migrations `001`–`005`. Migration `006` and schema `0.1.4` may remain if no schema correction is required.

Do not add My Requests / All Requests lists, coordinator workflow, shipper handoff, snapshots on submission, notifications, Excel functionality, attachments, comments, freight-cost workflow, or delivery verification.

## Required Review Validation

Use the available local WordPress/MySQL runtime and validate at minimum:

- opening New Request alone creates no draft
- project/request meaningful input creates one draft
- meaningful stop location as the first user-entered business data creates one draft and persists the stop
- meaningful item description as the first user-entered business data creates one draft and persists the item
- default blank pickup/delivery/item shells alone do not create a draft
- repeated autosaves update the same draft
- resume returns the most recently updated active draft, including after child-only changes
- all normal New Request editor visible strings originate from localized PHP configuration / `atlas-shipping` translations
- two-tab stale request-field update returns conflict
- two-tab stale stop create/update/remove/reorder returns conflict
- two-tab stale item create/update/remove/reorder returns conflict
- successful child mutation advances the aggregate request row version and updated timestamp as appropriate
- frontend retains the newest returned row version after every aggregate mutation
- submission remains deliberate and does not create a snapshot
- submitted requests remain editable
- sent-to-shipper/later requests remain locked
- unauthenticated API remains rejected
- package boundary and plugin/schema versions remain correct
- PHP and JavaScript syntax pass

Record only tests actually executed.

## Original Milestone Contract

The original canonical milestone requirements remain in force except where the review findings above clarify their implementation. They include:

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
- `row_version` conflict handling
- accessibility, localization, loading/error/success states
- no later coordinator/workflow/list/notification/Excel features
- product target `0.1.7`
- schema target `0.1.4` when migration `006` is retained
- directly installable WordPress development ZIP with repository-only files excluded

## Builder Completion Boundary

Address Review may transition this milestone only from `review_required` back to `implemented`.

At completion:

- Next owner: Architecture
- Next command: Review

Builder must commit and push review corrections to `origin/bootstrap/atlas-initialization` under the existing milestone authorization. Builder must not mark the milestone accepted/completed and must not begin the next milestone.
