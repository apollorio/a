# superseeded/ — retired documentation, kept not deleted

Moved here 2026-09-03. **Nothing in this folder is loaded, executed or read by
any Apollo plugin.** Verified before the move: a grep across every `apollo-*`
PHP file (excluding `vendor/`, `node_modules/`) found **zero** references to any
`.md` path. These files were documentation-only and remain so.

This folder contains **no PHP and no plugin header**, so WordPress does not treat
it as a plugin and will not load it. It is also not named `apollo-*`, so the
Apollo evidence tooling (`verify-structure.js`, `scan-plugins.js`) does not count
it as a plugin candidate.

Original paths are preserved as `superseeded/<plugin>/<filename>`, so every file
can be restored to exactly where it came from.

---

## What was moved, and why

### 1. `correct.md` × 23 — broken tooling output

One per plugin, all ~1 KB, all generated **12/02/2026**. These are not
documentation and never were: they are the **dumped source of a PowerShell PHPCS
report script that failed to interpolate its own variables**. Representative
content:

```
❌ **** → Erros:  | Avisos: ""
 = .Value.warnings
 = .Name
if (Test-Path C:\Users\rafae\Local Sites\apollo\...\FINAL_VIPGO_20260212_100831.json)
```

Every `$variable` was stripped, leaving bare `=` assignments and empty
placeholders. They also embed a **different machine's absolute paths**
(`C:\Users\rafae\Local Sites\...`), so they were never valid on this checkout.

Plugins affected: `adverts, chat, coauthor, comment, core, dashboard, djs,
email, events, fav, groups, loc, login, membership, mod, notif, seo, sheets,
social, statistics, templates, users, wow`.

### 2. Finished reports and audits × 6

| file | size | why retired |
|---|---|---|
| `apollo-telegram/FLOW_ANALYSIS.md` | 51 KB | point-in-time analysis report |
| `apollo-telegram/MISSING_COMPONENTS_REPORT.md` | 10 KB | explicitly a report; gap list, not a contract |
| `apollo-templates/REPORT_INVESTIGATION.md` | 19 KB | explicitly a report |
| `apollo-templates/NAVBAR_INTEGRATION_COMPLETE.md` | 9.6 KB | "COMPLETE" — record of finished work |
| `apollo-login/AUDIT-AJAX-USERSWP-VALIDATION.md` | 17 KB | audit report |
| `apollo-login/AUDIT-credentials-fix-20260206.md` | 2 KB | dated audit, historical |

---

## What deliberately stayed in place

Living documentation was **not** moved:

- every `README.md` (`admin`, `email`, `maps`, `remind`, `statistics`, `users`,
  `telegram`, plus nested ones under `apollo-templates/` and `apollo-events/`)
- `apollo-telegram/`: `ARCHITECTURE.md`, `INDEX.md`, `CHANGELOG.md`,
  `current.md`, `docs/phone-verification-system.md`, `docs/rest-mapping.md`
- `apollo-seo/ROADMAP.md` — forward-looking
- `apollo-events/_sandbox/*.md` — working specs for the live Node/PHP harness
  (`build-portal-harness.mjs`), which is real, running tooling
- `apollo-events/styles/base/template-parts/**` — docs sitting beside the code
  they describe

## `apollo-waha` was excluded entirely

Its 10 `.md` files were **not touched**. That plugin is frozen under an open
security interlock (`WAHA-CREDENTIAL-ROTATION-VERIFY.md` →
`AWAITING_CREDENTIAL_OWNER`) and a pending relocation
(`WAHA-EXPERIMENT-RELOCATION.md` → `BLOCKED`). Both artifacts forbid editing,
archiving or moving it until the credential owner acts.

---

## Restore

Single file:

```bash
cd D:/dev/_apollo.rio.br/plugins
mv superseeded/<plugin>/<file>.md <plugin>/<file>.md
```

Everything, exactly where it came from:

```bash
cd D:/dev/_apollo.rio.br/plugins/superseeded
find . -name '*.md' ! -name 'README.md' -exec sh -c \
  'mkdir -p "../$(dirname "$1")" && mv "$1" "../$1"' _ {} \;
```

All 29 files were **clean in git** at move time (none was among the 279
uncommitted paths), so `git status` shows each as a tracked deletion plus an
untracked copy here — fully reversible with no lost work.
