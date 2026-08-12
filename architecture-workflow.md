# Architecture workflow

Architecture runs in a normal ChatGPT Project chat with connected GitHub access. It creates milestones, reviews completed candidates, and prepares update handoffs.

Architecture does not require PowerShell, a local checkout, Git notes, or low-level Git object APIs. For `Update Framework`, authenticate what the connected environment can inspect, record the intended target, and hand off `Update Framework` to Builder for local mutation and publication.

Reviews inspect all changed and affected surfaces. A blocker sets `review_required`, but inexpensive inspection continues so findings are consolidated.

## Create Milestone

Resolve identity at the start from a bounded read set: `workflow-state.json`; the current milestone contract only when a current milestone exists or predecessor closure is required; then `docs/roadmap.md` or the product's canonical milestone index only to establish naming/sequence and current-position context. Derive the current product release line from an explicit product development build when possible, and cross-check only current-state authorities such as `docs/current-state-inventory.md`, `docs/project-profile.md`, or the current product-owned AGENTS state field. Do not scan an entire roadmap for generic release labels or broadly search a coherent repository.

Before drafting, freeze the product release target and predecessor state. Derive the next milestone identifier from the product repository's established sequence/naming convention and derive the next development build from its current/latest completed product build convention. Framework version, build, and release identifiers are never product milestone, build, or release identities. For example, Evaluations state at product target `0.6.0` and build `0.6.0-dev.17` stays on that product release train; its next milestone name follows Evaluations' own milestone index, never Framework `0.3.9`.

When a current milestone exists, inspect that contract and workflow state directly. A reviewed predecessor may be closed while creating its successor when the command supplies the normal user approval; active, implemented, or `review_required` predecessors are not eligible. Otherwise stop with the precise state or authority conflict. Do not run historical audits or broad certification.

Once identity is frozen, use ordinary connected GitHub file operations. Create the successor contract first with the product's canonical status set to `active` when implementation begins immediately, then reread it to verify exact milestone/release/build/status identity. If closing a reviewed predecessor, update and verify that contract next, only when the product convention requires it. Update `workflow-state.json` LAST, after every contract it will reference is verified, with the successor milestone, `current_status = active`, successor product build, and `Builder / Implement Milestone`; then reread and verify all five handoff fields. A future milestone may remain `planned`, but it must not hand Builder `Implement Milestone`.

Failure safety is final-state consistency, not a single multi-file commit. If a later publication step fails, restore predecessor content and delete the new successor only when each file still exactly matches the bytes written by this command. Refuse rollback over an independently changed file and report the exact divergence. Never leave workflow state pointing to a missing or unverified milestone; successful bounded rollback returns final repository authority to its pre-command state even if Git history records the attempted create/delete. Target under one minute. After two minutes, stop broad discovery and report the exact missing or conflicting authority.
