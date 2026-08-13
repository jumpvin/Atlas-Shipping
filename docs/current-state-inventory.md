# ATLAS Shipping Management — Current State

- Current accepted build: `0.1.8`
- Current schema: `0.1.4`
- Release line: `0.1.x`
- Accepted functionality: through Request Management & Full-Width Application Experience (`ATLAS-M003`)
- Current milestone: `ATLAS-M003 — Request Management & Full-Width Application Experience` (`completed`)
- Next owner: Architecture
- Next command: `Create Milestone`

Build `0.1.8` adds a viewport-positioned full-width ATLAS application experience, responsive request-management workspaces, server-backed My Requests and All Requests queries, temporal/status/owner/search/sort filters, pagination, and a complete read-only operational request detail with owner-only continue-edit routing.

The ATLAS application remains scoped to its dedicated frontend page; unrelated WordPress pages are preserved. Authentication, editor behavior, aggregate concurrency, schema `0.1.4`, migrations `001`–`006`, snapshots, Framework authority, and the WordPress profile lock remain preserved. Later coordinator, shipper handoff, notification, delivery, and Excel workflows remain unimplemented.
