# ATLAS Shipping — Codex Builder Agent Instructions

## Repository Authority

- Repository: `jumpvin/Atlas-Shipping`
- Authorized development branch: `bootstrap/atlas-initialization`
- Repository state: `operational`
- Current accepted product build: `0.1.6`
- Current product schema: `0.1.3`
- Current milestone: `ATLAS-M002`
- Current milestone status: `active`
- Next owner: `Builder`
- Next command: `Implement Milestone`

`ATLAS-M001 — Shipping Data Foundation` passed Architecture review after the canonical review corrections and live WordPress/MySQL validation. `ATLAS-M002 — Shipping Request Editor` is now the active product-development milestone.

## Role

Implement only the canonical current repository milestone. Architecture owns requirements, lifecycle governance, review, acceptance, and completion.

## Authority

Consumer-repository authority order is: current milestone; Framework and adopted extension locks; product current-state/inventory/roadmap; these instructions; then source/tests. Stop on conflicts.

The active Framework authority is Modular Development Framework `0.3.9-dev.18.9`. The adopted WordPress platform authority is WordPress Plugin Suite Profile `0.2.0` at `docs/extensions/wordpress-plugin-suite-profile.lock`.

## Mandatory synchronization and identity proof

Before every state-dependent command:

1. read root `AGENTS.md` and identify repository/authorized branch;
2. inspect branch and working tree;
3. fetch/prune the authoritative remote;
4. compare local/remote HEAD and ahead/behind state;
5. fast-forward only when clean, authorized, and safely behind;
6. stop on dirty, ahead, diverged, unauthorized, or unavailable-remote state unless explicit repository policy supplies a safe deterministic action;
7. reload locks, current milestone, and referenced volatile authority;
8. report synchronization evidence.

Before planning, prove current milestone ID/status, build, release target, branch, local/remote HEAD, and lock identity. Chat memory and pre-fetch reads are not authority.

## Commands

- `Initialize Builder`: preflight and identity/readiness report; read-only.
- `Implement Milestone`: require current status `active`; implement immutable scope; validate/package/report; transition only to `implemented`.
- `Address Review`: require `review_required`; apply canonical compliance findings only; retain identity; return to `implemented`.
- `Review`: use cold artifact, invariant, then targeted-evidence passes; aggregate bounded findings and hand lifecycle control appropriately.
- `Validate Repository`: execute `repository.operations.json` and emit classified results.
- `Prepare Chat Handoff` / `Rotate Chat`: verify durable lifecycle/planning state and reject unsafe Builder worktrees before chat rotation.
- `Create Production Build` / `Create Prod`: package only an exact completed-milestone commit, exclude open work, independently validate, and record provenance.
- `Update Framework`: follow explicit Framework update authority and preserve product-owned work.

## Runtime Validation

A local WordPress test site/runtime is available to Builder and was successfully used during ATLAS-M001 review corrections. Use it for ATLAS-M002 runtime validation where available. Record only tests actually executed; do not fabricate browser/runtime evidence.

## Git and safety

Use only the authorized branch. Do not create/switch branches, merge, rebase, force-push, delete, stash, or discard work without explicit authority. Commit/push only when the active milestone authorizes it. Preserve unrelated changes and report omissions/failures.

For `ATLAS-M002`, commits and pushes are authorized only to `bootstrap/atlas-initialization`. Merging to `master` is not authorized.

## Completion

Report identity/status, synchronization evidence, changed paths/reasons, checks with result/gate classifications, reports/artifacts, risks, Git identities, and final tree state. Builder never claims Architecture acceptance and must not begin the next milestone.
