# Troubleshooting

- Dirty or diverged checkout: stop and reconcile user work; never reset it silently.
- Package digest mismatch: reject the package and obtain the authenticated bytes.
- Product-owned path collision: stop rather than overwrite it.
- Targeted test failure: isolate and rerun only that behavior.
- Architecture lacks local tooling: return a Builder `Update Framework` handoff; do not move Architecture into Codex.
- Push failure after the update commit: rerun the same authenticated `Update Framework`; the exact one-commit state retries push only. Any other divergence fails closed.
- Report output: omit it normally or write outside the product repository; success must leave the product tree clean.
