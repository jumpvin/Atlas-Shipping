# ATLAS Shipping Management — Current State

- Current implemented build: `0.1.9`
- Current schema: `0.1.4`
- Release line: `0.1.x`
- Accepted functionality: through Request Management & Full-Width Application Experience (`ATLAS-M003`)
- Current milestone: `ATLAS-M004 — Request Editor Interaction Stabilization` (`implemented`)
- Next owner: Architecture
- Next command: `Review`

Build `0.1.9` corrects strict date-only validation and SQL `NULL` handling, provides structured field-specific date errors, and changes routine autosave to a non-rerendering single-flight/dirty-follow-up strategy that preserves focus, caret position, and latest input.

The accepted full-width request-management application, owner-only editing boundary, schema `0.1.4`, migrations `001`–`006`, Framework authority, and WordPress profile lock remain preserved. Shipping Coordinator and later workflow remain unimplemented.
