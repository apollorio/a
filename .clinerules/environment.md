# apollo::rio — Cline operating rules (2026-09-03)

Cline-specific rules for this workspace. **`CLAUDE.md` at the repo root is the
project contract** — read it (and `_inventory/registry/PLUGIN-DEPLOY-MAP.md`)
before editing any `apollo-*` plugin. These rules cover what Cline does
differently from a plain editor, and what changed in this environment.

## 1 · This folder mirrors to production
`D:\dev\_apollo.rio.br\plugins` → `apollo.rio.br/wp-content/plugins/` via
RealTimeSync (~30 s lag). **A saved file is a deployed file.**
- Never write a half-finished edit "to come back to it".
- Prefer `_sandbox/*-harness.html` / `.mjs` generation for any visual change.
- `editor.formatOnSave` is disabled in this workspace on purpose.

## 2 · PHP linting IS available now (env change, 2026-09-03)
CLAUDE.md's "no PHP binary" note is **stale** — `php` 8.3.31 (ZTS) is on PATH.
Run this on every PHP file before finishing a task; a green run is part of the gate:

```
php -l <file>
```

If a plugin has a real runner (`bin/*.php`, `_sandbox/*.mjs`), run its
dry-run/harness instead of only linting.

## 3 · Keep the structure
Do not add files inside an `apollo-*` plugin without reading its registry
chapter and the cardinal-sin rule (two cells declaring the same selector).
Agent/tooling config lives at the repo root only (this folder, `.cursor/`,
`CLAUDE.md`, `.vscode/`).

## 4 · Search hygiene
`debug-*.log` (once ~459 MB), `sync.ffs_*`, `elementor/`, `vendor/`,
`node_modules/` are excluded from search/watcher — do not `read`/`grep` those
without a reason. Use `search_codebase` for cross-plugin patterns; it respects
these excludes.

## 5 · Verification loop
1. Lint every changed PHP (`php -l`).
2. Run the relevant sandbox harness (e.g. `node apollo-events/_sandbox/build-portal-harness.mjs`).
3. Report green/red explicitly — never claim a gate passed without running it.