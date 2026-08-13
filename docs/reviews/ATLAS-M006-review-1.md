# ATLAS-M006 — Architecture Review 1

Review result: `review_required`

Reviewed implementation commit: `19160bbf1f4cec7dba832e246e3bb25a7d170d47`

Product target remains: `0.2.1`

Schema target remains: `0.2.1`

## What passed

The implementation establishes migration `008`, dedicated actual-shipment/shipper-response persistence, coordinator-only response mutation, partial save without workflow advancement, row-version/transaction protection, scheduled transition/timestamp, freight-cost zero-vs-unknown representation, Needs Attention response routing, three-stage request detail, and package/framework validation.

The runtime harness confirms PM mutation denial, partial response persistence, distinct PM/handoff/actual concepts, stale finalization rejection, successful scheduled transition, duplicate finalization rejection, and queue removal after scheduling.

## R1 — Needs Attention must not lose unresolved work because its shipment date is now in the past

`Service::coordinator_queue()` currently queries both `submitted` and `sent_to_shipper` using `view => upcoming`.

Needs Attention is an operational work queue, not a temporal browsing view. A submitted request still requires outbound handoff even if its requested ship date has passed. A `sent_to_shipper` request still requires the shipper response/details to be recorded even if the requested ship/delivery date has passed.

Using the normal Upcoming temporal filter can therefore make unresolved coordinator work disappear from Needs Attention merely because time passed.

Correct the queue query so actionable workflow state is authoritative:

- all non-archived `submitted` requests needing outbound handoff remain discoverable until they leave `submitted`
- all non-archived `sent_to_shipper` requests needing response details remain discoverable until they leave `sent_to_shipper`
- past requested/delivery dates must not remove either state from Needs Attention
- scheduled requests remain absent from Needs Attention
- retain useful deterministic ordering, preferably oldest/most overdue attention first or another clearly deliberate operational order

Do not change My Requests / All Requests temporal browsing semantics to solve this. The correction belongs specifically to coordinator work-queue querying.

## R2 — Scheduled datetime handling must use a deliberate timezone contract

The response UI currently converts `datetime-local` values by string replacement only:

- browser local value -> database string by replacing `T` with a space
- database string -> browser value by replacing the space with `T`

This labels local wall-clock input as if it were the canonical stored value without an explicit timezone conversion. Elsewhere ATLAS established a canonical UTC behavior for stop datetime fields. Shipment scheduling needs the same deliberate contract so a coordinator's `2:00 PM` does not become ambiguous or wrong when WordPress/server/user timezone settings differ.

Correct and document the scheduled-window contract for:

- pickup_window_start
- pickup_window_end
- delivery_window_start
- delivery_window_end

Requirements:

- `datetime-local` values represent the coordinator/user's intended local wall-clock time
- conversion to persistence uses the application's deliberate timezone authority and canonical storage representation
- values round-trip back to the browser as the same intended local wall-clock time
- no silent timezone shift
- do not depend on PHP/MySQL/server timezone coincidence
- preserve null/blank values
- validate start <= end for pickup and delivery windows when both are supplied
- malformed/impossible datetime values are rejected with stable validation errors

If ATLAS currently has no explicit application timezone setting, use the WordPress site timezone as the initial authority and keep the conversion centralized so a future application-specific timezone can replace it without rewriting UI code.

## Required validation

Use the available WordPress/MySQL/browser runtime and add focused evidence for:

- submitted request with requested ship date in the past remains in Needs Attention
- sent-to-shipper request with requested/delivery date in the past remains in Needs Attention
- scheduled request is absent
- queue remains coordinator-authorized
- datetime-local pickup schedule round-trips through persistence without changing intended local wall-clock time
- datetime-local delivery schedule round-trips likewise
- test with WordPress timezone deliberately different from UTC if practical
- pickup end-before-start rejected
- delivery end-before-start rejected
- malformed scheduled datetime rejected
- blank optional window endpoints remain valid
- existing partial save, stale protection, scheduled transition, PM denial, freight cost behavior, request detail, migration, and package checks remain passing

## Scope

Address only R1 and R2 plus directly necessary query/service/API/UI/localization/test/report changes.

Preserve product `0.2.1`, schema `0.2.1`, migration `008` identity, accepted coordinator handoff architecture, and all prior functionality.

Do not begin transit, delivery verification, email, notifications, tracking, or Excel work.

At completion return lifecycle control to Architecture with status `implemented` and next command `Review`.
