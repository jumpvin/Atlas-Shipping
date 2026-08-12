# ATLAS Shipping Management — Current State

- Current implemented build: `0.1.7`
- Current schema: `0.1.4`
- Release line: `0.1.x`
- Accepted functionality: through Shipping Data Foundation (`ATLAS-M001`)
- Current repository milestone: `ATLAS-M002 — Shipping Request Editor` (`implemented`)
- Next owner: Architecture
- Next command: `Review`

Build `0.1.7` adds the isolated New Request single-page editor, session-authenticated and CSRF-protected REST boundary, meaningful autosave and latest-draft resume, ordered stop/item editing, request transportation preferences through migration `006`, optimistic concurrency, structured submission validation, and deliberate transition through `submitted`.

The accepted `0.1.6` authentication, shell, diagnostics, migrations `001`–`005`, shipping repositories/services/snapshots, Framework authority, and WordPress profile lock remain preserved. Shipping Coordinator, shipper handoff/snapshot, lists, notification, scheduling, freight-cost, delivery-verification, attachments, and Excel workflows remain unimplemented.
