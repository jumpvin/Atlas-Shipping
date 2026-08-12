---
contract-type: extension-adoption
extension-name: "WordPress Plugin Suite Profile"
extension-slug: "wordpress-plugin-suite-profile"
extension-version: "0.2.0"
extension-type: "platform-profile"
status: approved
created-by: architect
created-at: "2026-08-12"
---

# WordPress Plugin Suite Profile Adoption

## Purpose

Apply the approved WordPress implementation, lifecycle, security, packaging, testing, and uninstall authority to ATLAS Shipping without changing product runtime behavior.

## Framework Compatibility

Framework `0.3.9-dev.18.9` satisfies the profile minimum `0.3.5`.

## Current Project State

Accepted product build `0.1.5`, schema `0.1.2`, through Application Shell Hardening.

## Repository Changes

- Added the canonical extension lock.
- Added stage-separated build paths and packaging/validation operations.
- Added required build ZIP ignore rules.

## Approved Exceptions

None.

## Validation Requirements

Package identity, single plugin root, version/schema identity, PHP syntax, Framework state, and repository-metadata exclusion are implementation-blocking.

## Prohibited Changes

- No product functionality changes.
- No Framework lifecycle changes outside `ATLAS-INIT-001`.
- No Shipping Data Foundation implementation.
