# ATLAS-INIT-001 — Repository Bootstrap and Accepted Baseline Import

Status: active

Product: ATLAS Shipping Management

Current accepted product build: `0.1.5`

Current accepted schema version: `0.1.2`

Release line: `0.1.x`

Authorized branch: `bootstrap/atlas-initialization`

Next owner: Builder

Next command: Implement Milestone

## Objective

Convert `jumpvin/Atlas-Shipping` from Framework bootstrap state to an operational repository by importing the exact accepted ATLAS Shipping `0.1.5` product baseline, materializing the installed Modular Development Framework authority surface, and recording the already-approved WordPress Plugin Suite Profile adoption.

This milestone is repository/framework initialization only. It MUST NOT change ATLAS Shipping product behavior, product version, or database schema.

## Immutable Source Authorities

### Accepted ATLAS product package

- Package: `atlas-shipping-0.1.5.zip`
- SHA-256: `ae192f65eebf5a0de0ffb6e4200cd9b9e4670f8937139c08429f820c1ba1389f`
- Product version: `0.1.5`
- Schema version: `0.1.2`

### Modular Development Framework

- Package: `modular-development-framework-v0.3.9-dev.18.9.zip`
- Package build: `0.3.9-dev.18.9`
- Governing release line: `0.3.9`
- SHA-256: `2bfd43b13dc82ce3e0de8bb6ee02b317031abe5172641e18aeaff72f3a0c8a53`

### Adopted extension

- Extension: WordPress Plugin Suite Profile
- Version: `0.2.0`
- Type: `platform-profile`
- Package SHA-256: `3496fcbe66100c31ea4e3a70893ba311f2e395fcf2d4db7c999505532de98cb4`
- Adoption status: approved by Architecture before this milestone

## Required Work

### 1. Import accepted ATLAS `0.1.5`

Import the exact contents of the accepted `atlas-shipping-0.1.5.zip` as the product baseline.

Preserve the plugin's internal source structure and behavior.

The repository should contain source suitable for rebuilding the WordPress-installable plugin package. Do not nest the plugin under an extra version directory.

Do not silently rewrite product files merely to normalize style.

### 2. Materialize Framework-managed authority

Using the supplied Framework package and existing bootstrap authority, install the Framework-managed repository files required for an operational consumer repository.

This includes, where required by the Framework package and installed-framework validation surface:

- `framework.json`
- finalized `framework.lock`
- `workflow-state.json`
- `repository.operations.json`
- Framework-managed validation schemas/scripts required by the installed package
- any required Framework-managed documentation or state files

Preserve root `AGENTS.md` while updating its bootstrap-specific state only as required to reflect the resulting operational repository.

Do not invent Framework-managed formats when the supplied package provides canonical templates or schemas.

### 3. Materialize approved WordPress extension adoption

Create the repository lock/authority required by the approved WordPress Plugin Suite Profile `0.2.0` adoption.

Expected canonical target:

`docs/extensions/wordpress-plugin-suite-profile.lock`

Use the extension package's own manifest, integration guidance, adoption contract, and validation instructions to determine exact managed content.

The extension adoption must not itself advance the ATLAS product version or schema.

### 4. Record current product state

Create/update the product-owned current-state authority required for future Architecture and Builder operations.

It must unambiguously record at least:

- Product: ATLAS Shipping Management
- Current accepted build: `0.1.5`
- Current schema: `0.1.2`
- Accepted functionality through Application Shell Hardening
- No active product-development milestone beyond this initialization transaction
- Planned next product scope: Shipping Data Foundation

Do not represent Shipping Data Foundation as implemented.

### 5. Transition repository to operational state

At the end of successful implementation, the repository must no longer be in bootstrap state.

The final Framework/workflow authority should represent this initialization milestone as `implemented` and hand control back to Architecture for Review.

Expected end-of-implementation lifecycle:

- current milestone: `ATLAS-INIT-001`
- current status: `implemented`
- current build: `0.1.5`
- next owner: `Architecture`
- next command: `Review`
- bootstrap status: `operational`

Do not mark this milestone accepted or completed. Builder may transition only to `implemented`.

## Product Preservation Rule

Unless explicitly required to materialize repository/framework metadata outside the product package:

- Preserve all accepted `0.1.5` product functionality.
- Preserve database schema `0.1.2`.
- Preserve existing authentication behavior.
- Preserve frontend application shell behavior.
- Preserve migration history and identifiers.
- Preserve shortcode behavior.
- Preserve diagnostics behavior unless Framework-managed metadata requires additive diagnostics only.
- Preserve WordPress plugin installability.
- Do not add Shipping Data Foundation functionality.
- Do not modify product version from `0.1.5`.

Any divergence from the accepted product package must be explicitly justified as required Framework/adoption metadata and must not alter runtime product behavior.

## Packaging Validation

Rebuild an installable ATLAS Shipping package from repository source and verify that it remains a valid WordPress plugin ZIP.

The produced plugin package must retain a single top-level plugin directory:

`atlas-shipping/`

No nested version folder, duplicate plugin copy, Framework repository metadata, Git metadata, local build artifacts, or unrelated repository files may leak into the distributable plugin ZIP.

The rebuilt plugin must still report product version `0.1.5` and schema `0.1.2`.

## Framework / Repository Validation

Run the bounded Framework validation appropriate to an installed consumer repository and the WordPress extension adoption.

At minimum verify:

- root `AGENTS.md` exists and resolves the correct repository/branch/state
- Framework package identity matches the locked SHA-256
- Framework lock validates against the supplied package schema
- repository operations authority exists and is valid
- workflow state is valid and coherent
- authorized branch is `bootstrap/atlas-initialization`
- extension lock identity matches WordPress Plugin Suite Profile `0.2.0`
- repository is classified operational after implementation
- product build remains `0.1.5`
- product schema remains `0.1.2`
- package rebuild excludes Framework/repository-only files

## Regression Checklist

Verify and report:

- Existing plugin activates correctly.
- Existing `0.1.5` authentication behavior remains intact.
- Existing magic-link/session architecture remains intact.
- Existing application shell and routing remain intact.
- Existing responsive/accessibility behavior remains intact.
- Existing diagnostics remain functional.
- Existing migration history remains intact.
- Existing application shortcode remains functional.
- No Shipping Data Foundation functionality has been introduced.
- Product version remains `0.1.5`.
- Schema version remains `0.1.2`.

## Git Authorization

This milestone explicitly authorizes Builder to:

- work only on `bootstrap/atlas-initialization`
- create files required by this milestone
- modify bootstrap authority files only as required by this milestone
- commit the complete initialization transaction
- push commits to `origin/bootstrap/atlas-initialization`

This milestone does NOT authorize:

- merging to `master`
- force-pushing
- rebasing published history
- deleting branches
- modifying unrelated repositories
- implementing the next product milestone

## Prohibited Changes

Do not:

- implement Shipping Data Foundation
- add request/stops/items business tables unless they already exist in accepted `0.1.5`
- change product runtime behavior
- change the product version
- change the schema version
- redesign the application shell
- replace authentication architecture
- remove accepted source files
- perform unrelated refactoring
- claim Architecture acceptance

## Deliverables

Builder must leave on `bootstrap/atlas-initialization`:

1. Imported accepted ATLAS Shipping `0.1.5` source.
2. Operational Framework authority surface.
3. WordPress Plugin Suite Profile `0.2.0` adoption lock.
4. Current-state product authority.
5. Valid operational workflow state with this milestone `implemented` and `Architecture / Review` handoff.
6. Validation evidence/report required by Framework operations.
7. A rebuilt installable WordPress plugin ZIP or reproducible packaging output proving `0.1.5` remains packageable.
8. A concise implementation report including changed paths, validation results, package identity, Git commit SHA, pushed remote SHA, and final clean-tree status.

## Acceptance Boundary

Builder completion means `implemented`, not accepted.

Architecture will review the cold repository/artifact after Builder implementation. Only Architecture plus user acceptance may close this initialization milestone and proceed to the Shipping Data Foundation milestone.
