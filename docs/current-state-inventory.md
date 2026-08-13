# ATLAS Shipping Management — Current State

- Current accepted build: `0.1.9`
- Current schema: `0.1.4`
- Release line: `0.1.x`
- Accepted functionality: through Request Editor Interaction Stabilization (`ATLAS-M004`)
- Current milestone: `ATLAS-M004 — Request Editor Interaction Stabilization` (`completed`)
- Next owner: Architecture
- Next command: `Create Milestone`

Build `0.1.9` corrects request date-only validation and SQL `NULL` handling, provides structured field-specific date errors, and changes routine autosave to a non-rerendering single-flight/dirty-follow-up strategy that preserves focus, caret position, and latest input. Browser/runtime validation confirms exact date round-tripping, uninterrupted autosave across multiple field types, in-flight follow-up persistence, realistic successful submission, and visibility in My Requests and Request Detail.

The accepted full-width request-management application, owner-only editing boundary, schema `0.1.4`, migrations `001`–`006`, Framework authority, and WordPress profile lock remain preserved. Shipping Coordinator and later workflow remain unimplemented.
