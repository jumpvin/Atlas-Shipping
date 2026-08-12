# Update Framework

Architecture and Builder share the command’s meaning, not its physical implementation.

Architecture inspects the connected repository and supplied Framework/extension identities, determines eligibility, and hands Builder the repository plus Framework and extension ZIP/SHA identities. Builder needs no snapshot request or external plan: it reads the clean synchronized checkout, authenticates packages, derives managed changes locally, preserves product-owned paths, validates inside the update, creates one normal Git commit, pushes, and verifies the remote and clean tree.

The authenticated installed baseline and current package define the ownership boundary. Every other path is product-owned. `Update Framework` validates and completes synchronously, then preserves or resumes the product's legitimate lifecycle; there is no mandatory second validation command. A legacy 0.3.8 product with no active milestone may normalize to `Architecture / Create Milestone` while retaining its completed product build.
