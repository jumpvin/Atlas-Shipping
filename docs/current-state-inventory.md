# ATLAS Shipping Management — Current State

- Current accepted build: `0.1.6`
- Current schema: `0.1.3`
- Release line: `0.1.x`
- Accepted functionality: through Shipping Data Foundation
- Previous milestone: `ATLAS-M001` (Architecture accepted after review corrections and WordPress/MySQL runtime validation)
- Current repository milestone: `ATLAS-M002 — Shipping Request Editor` (`active`)
- Next owner: Builder
- Next command: `Implement Milestone`
- Target build: `0.1.7`

Accepted baseline includes passwordless authentication, private application sessions, application shell/router/accessibility, diagnostics, migrations `001` through `005`, shipping request/stops/items/snapshot persistence, repository/service/model boundaries, optimistic concurrency, actor-attributed activity, and shortcode `atlas_shipping_app`.

ATLAS-M002 is authorized to replace only the New Request placeholder with the single-page card-based editor, authenticated API, meaningful autosave/draft resume, stops/items editing, transportation/handling preferences, and deliberate submission through `submitted`. Later coordinator, shipper handoff, request-list management, notification, delivery-verification, and Excel workflows remain unimplemented.
