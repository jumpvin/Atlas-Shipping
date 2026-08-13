# ATLAS-M004 Builder Implementation Report

Result: `implemented`; handoff: `Architecture / Review`.

## Delivered

- Strict `YYYY-MM-DD` calendar validation without timestamp/timezone conversion.
- Stable field-specific REST validation data and accessible inline date errors.
- Non-rerendering autosave with a single-flight dirty follow-up queue and child-ID reconciliation.
- Compatible SQL `NULL` persistence for blank optional dates.
- Product `0.1.9`; schema remains `0.1.4`; migrations `001`–`006` unchanged.

## Runtime evidence

- Date matrix: leap date and exact round-trip pass; blank optional date passes; malformed, impossible, and non-leap dates rejected with field keys.
- Browser focus remained on Project ID through two autosave cycles and on Project Name, Notes, stop Contact Name, and item Description after autosave.
- An edit made during the first save persisted through the queued follow-up (`M004-E2E-ALPHA-OMEGA`) with Saved status.
- Dates `2026-09-15` and `2026-09-20` reloaded unchanged.
- Realistic request `AS-000012` submitted successfully, appeared in My Requests, and rendered complete dates/stops/item in Request Detail.

Builder does not claim Architecture acceptance.
