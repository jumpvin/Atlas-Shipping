# Builder workflow

Builder runs in Codex with the authorized local checkout.

For `Update Framework`, supply the Framework ZIP and authenticated SHA-256 plus any extension ZIP/SHA pairs. Builder reads the local checkout, derives the managed update internally, validates, creates one commit, pushes, and verifies a clean synchronized result. No snapshot request or hand-written plan is required.

For milestone work, verify branch, clean tree, remote synchronization, current milestone, and package identity. Run only the bounded checks selected by the milestone.

Do not run historical exhaustive matrices unless a milestone explicitly requires one.
