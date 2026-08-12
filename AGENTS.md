# ATLAS Shipping — Codex Builder Agent Instructions

## Repository Authority

- Repository: `jumpvin/Atlas-Shipping`
- Authorized development branch: `bootstrap/atlas-initialization`
- Repository state: `operational`
- Current product build: `0.1.5`
- Current product schema: `0.1.2`
- Current milestone: `ATLAS-M001`
- Current milestone status: `active`
- Next owner: `Builder`
- Next command: `Implement Milestone`

The repository bootstrap milestone `ATLAS-INIT-001` has passed Architecture review. The active product-development milestone is `ATLAS-M001 — Shipping Data Foundation`.

## Role

Implement only the canonical current repository milestone. Architecture owns requirements, lifecycle governance, review, acceptance, and completion.

## Authority

Consumer-repository authority order is: current milestone; Framework and adopted extension locks; product profiles/inventory/roadmap; these instructions; then source/tests. Stop on conflicts.

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

## Repository states

Detect `empty`, `bootstrap`, or `operational`. This repository is operational and requires the full operations manifest and validation surface.

## Commands

- `Initialize Builder`: preflight and identity/readiness report; read-only.
- `Implement Milestone`: require current status `active`; implement immutable scope; validate/package/report; transition only to `implemented`.
- `Address Review`: require `review_required`; apply canonical compliance findings only; retain identity; return to `implemented`.
- `Review`: use cold artifact, invariant, then targeted-evidence passes. A first blocker sets `review_required` but does not normally stop remaining bounded static inspection; aggregate discoverable blockers, stop unrelated expensive execution, and use `blocked/incomplete audit` only when authority or artifact failure makes further inspection impossible.
- `Validate Repository`: execute `repository.operations.json` and emit classified results.
- `Prepare Chat Handoff` / `Rotate Chat`: verify durable lifecycle/planning state and reject unsafe Builder worktrees before chat rotation.
- `Create Production Build` / `Create Prod`: package only an exact completed-milestone commit, exclude open work, independently validate, and record provenance.
- Packaging/release/update/adoption/reconciliation: always preflight, then follow explicit repository authority.
- `Update Framework`: use the supplied Framework and extension ZIP/SHA identities, derive managed changes from the synchronized local checkout, preserve product-owned work, validate within the update, publish one commit, and verify remote equality and a clean tree.

## Git and safety

Use the authorized release/milestone/hotfix branch. Do not create, switch, merge, rebase, force-push, delete, stash, or discard work without explicit authority. Commit/push only when the command and milestone authorize them. Preserve unrelated changes and report every omission or failure.

For `ATLAS-M001`, the milestone explicitly authorizes commits and pushes to `bootstrap/atlas-initialization` only. It does not authorize merging to `master`.

## Completion

Report identity/status, synchronization evidence, changed paths/reasons, checks with result/gate classifications, reports/artifacts, risks, Git identities, and final tree state. Builder never claims architectural acceptance.
