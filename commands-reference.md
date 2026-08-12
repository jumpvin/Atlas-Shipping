# Commands

- `Initialize Framework`: load and verify repository authority.
- `Create Milestone`: resolve product identity immediately, publish and verify the successor as `active` when implementation begins now, then update workflow state last with `active / Builder / Implement Milestone`. Planned milestones cannot be handed to implementation. Close an eligible predecessor when deterministic and use guarded rollback on failure.
- `Implement Milestone`: Builder implements the one active milestone.
- `Review`: Architecture performs cold and affected-area review.
- `Address Review`: Builder applies the consolidated correction contract.
- `Update Framework`: Architecture hands off repository, Framework ZIP/SHA, and extension ZIP/SHA identities. Builder derives and applies the update locally, validates inside the transaction, creates one commit, pushes, and verifies.
- `Create Production Build`: package an accepted release candidate.
- `Promote Framework`: explicit user-authorized promotion transaction.
