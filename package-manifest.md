# Package Manifest

- Package: Modular Development Framework
- Package build: `0.3.9-dev.18.9`
- Release target: `0.3.9`
- Type: complete simplified distribution
- Entry point: `START-HERE.md`
- Architecture: normal ChatGPT Project with connected GitHub access; prepares a Builder handoff
- Builder: local Codex checkout; authenticates the plan and packages, updates managed paths, validates, creates one normal commit, pushes, and verifies
- Consumer validator: `scripts/validate-installed-framework.ps1`
- Builder updater: `scripts/update-product-framework.ps1`
- Ownership authority for the 0.3.8 transition: `integrity/baseline-v0.3.8-manifest.json`

Package completeness is established by its authenticated member inventory and required-file closure, never by file count.
