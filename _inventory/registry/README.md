# Apollo Modular Registry

The monolithic `apollo-registry.json` (2.4 MB, 31 top-level keys, 48 plugins) is a
chaptered SSOT. Nothing is deleted: rebuilding every chapter in `load_order` reproduces
the monolith **exactly**.

## Read this first

**Two different files are called `apollo-registry.json`. They are not the same thing.**

| | Documentation monolith | Runtime registry |
|---|---|---|
| Path | `plugins/_inventory/apollo-registry.json` | `wp-content/apollo-registry.json` |
| Produced by | `node build.js --write` | a manual deploy step |
| Read by | humans, AI agents, audits | `Apollo\Core\Registry` at request time |
| Constant | — | `APOLLO_REGISTRY_PATH` (set in `mu-plugins/apollo-brain.php`) |

`build.js` deliberately does **not** write the runtime file. So editing a chapter changes
nothing on the live site until someone builds *and* copies. Nothing currently records when
that last happened — see `22-mu-plugins.json` → `$registry_path_warning`.

## Two scripts, two different questions

```bash
cd _inventory/registry

node build.js            # validate + round-trip check, writes nothing
node build.js --write    # promote → apollo-registry.json + apollo-registry-2.json
node doctor.js           # is the registry TRUE? compare every claim against disk
node doctor.js --strict  # same, exit 1 on any ERROR  (use in CI)
node doctor.js --json    # machine-readable, for an agent
python3 verify.py        # lossless proof of the chapter split
```

`build.js` answers *"do the chapters assemble?"* — missing or unparseable chapter, two
chapters claiming one top-level key, plugin count mismatch, missing `apollo-core` master.

`doctor.js` answers *"do the chapters tell the truth?"* — it walks the actual plugin
folders and `mu-plugins/`, and grades findings `ERROR` / `WARN` / `INFO`:

- registry entry with no folder (and not declared absent) — or a folder with no entry
- plugin count disagreeing between the map, `architecture`, and `summary`
- a registry entry with no architecture layer
- version drift: registry vs docblock, **and docblock vs `APOLLO_*_VERSION` constant**
- an mu-plugin on disk that no chapter documents
- chapters modified after the monolith was last built

A registry that assembles but lies is worse than no registry. Run `doctor.js` before
trusting anything in here.

## Layout

```
_inventory/
├── apollo-registry.json          ← canonical monolith (BUILD OUTPUT — do not hand-edit)
├── apollo-registry-2.json        ← mirror, byte-identical since 2026-08-31
├── data-registry.json            ← mockup ↔ DB field map (separate SSOT)
└── registry/
    ├── 00-registry-map.json      ← master index: chapters, load_order, build_rules
    ├── build.js                  ← reassembles chapters → monolith
    ├── doctor.js                 ← registry claims vs disk reality
    ├── verify.py                 ← proves the split is lossless
    ├── 01-philosophy.json … 22-mu-plugins.json
    └── 09-plugins/
        └── {slug}.json           ← one file per plugin (48)
```

## Editing

Edit the **chapter**, never the monolith. Then `node build.js --write`, then `node doctor.js`.

## Merge semantics

`deep_merge_last_wins`. Objects merge recursively; **arrays are replaced wholesale**.
The `$chapter` key in every file is metadata and is stripped at build time.

`$deep_audit` is deliberately split four ways — `04-audit` (run metadata),
`04-audit-reports` (per-plugin reports), `10-coupling`, `11-security` — and deep-merges
back into one object. It is the only shared top-level key; every other key has exactly one
owner, and the builder enforces that.

Plugin files hold the **bare** entry. The builder wraps each one as `plugins.{slug}` using
its filename, per `chapters["09-plugins"].wrap`.

## Chapters

| Chapter | Owns | Owner |
|---|---|---|
| 01-philosophy | `$philosophy` | apollo-core |
| 02-header | `$header` + the 4 root scalars | apollo-core |
| 03-apollo-rule | `$apollo_rule` | apollo-core |
| 04-audit | `$audit`, `$deep_audit` run metadata | apollo-core |
| 04-audit-reports | `$deep_audit.reports` | apollo-core |
| 05-global-architecture | `$GLOBAL_ARCHITECTURE` | apollo-core |
| 06-cdn | `cdn` | apollo-templates |
| 07-icons | `icons` | apollo-core |
| 08-architecture-layers | `architecture` | apollo-core |
| 09-plugins/ | `plugins.*` (48 files) | each plugin |
| 10-coupling | `$deep_audit.coupling_edges`, `.high_risk_plugins` | apollo-core |
| 11-security | `$deep_audit.open_rest_total`, `.remediations_applied` | apollo-login |
| 12-data-sync | `$data_registry_sync` | apollo-core |
| 13-runtime | `runtime_wordpress_packages` | apollo-runtime *(nominal — plugin absent)* |
| 14-routing | `$routing_canonical`, `$third_party_plugins` | apollo-core |
| 15-conventions | `roles`, `timeDisplay`, `namingRules`, `constants` | apollo-core |
| 16-summary | `summary`, `quick_lookup`, `divergences` | apollo-core |
| 17-backlog | `$to_be_done` | apollo-core |
| 18-canvas-shell | `$canvas_shell` | apollo-templates |
| 19-ssot-audit | `$ssot_audit` | apollo-core |
| 20-meta-schemas | `$meta_schemas` | apollo-core |
| 21-mockup-field-contract | `$mockup_field_contract` | apollo-core |
| 22-mu-plugins | `$mu_plugins` | apollo-core |

## 22-mu-plugins — the boot layer

Added 2026-08-31. `$mu_plugins` used to live in `14-routing` and documented **5 of 15**
real files. The MU layer runs before `active_plugins`, cannot be switched off from
wp-admin, and loads in plain alphabetical order with no dependency resolution — so an
undocumented file there is an invisible global side effect.

What the chapter now records, beyond the five that were already known:

- **`apollo-brain.php`** — the actual brain. Defines `APOLLO_REGISTRY_PATH`, requires
  `apollo-core.php` before `active_plugins`, installs the script-defer filter with its
  allow-list, and wires cache purges. Explicitly does **not** init CPT/Meta/security —
  `apollo_core_bootstrap()` on `plugins_loaded` P1 owns that.
- **Four `force-load-apollo-*.php` files** — they `require` a plugin's main file directly,
  bypassing `active_plugins`. The cost is documented under `$force_load_pattern`:
  wp-admin's Plugins screen stops telling the truth, deactivating there does nothing, and
  `register_activation_hook` never fires. Three of the four say "remove this" in their own
  headers.
- **`apollo-force-open-registration.php`** — filters `option_users_can_register` to `'1'`
  at priority 10000. Security-relevant and invisible: wp-admin shows the box checked while
  the stored value may be `'0'`. Closing registration means deleting this file; unchecking
  the box will not work.
- **`apollo-events-helpers-guard.php`**, **`apollo-htaccess-lock.php`**,
  **`apollo-locale-pt-br.php`** — shims and policy files, each with its risk noted.
- **Third-party**: `endurance-page-cache.php` (2161 LOC, host cache) and
  `elementor-safe-mode.php` — the latter would stop the entire Apollo ecosystem loading if
  its option were ever switched on.

## Reconciliation — 2026-08-31

`doctor.js` was written and run for the first time. It found 5 ERROR + 22 WARN; all are
now fixed and the full list lives in `00-registry-map.json` → `$last_reconciliation`.
Headlines:

- **15 stale plugin versions**, worst `apollo-templates` 1.5.1 → 1.6.24 and `apollo-core`
  6.3.0 → 6.5.2.
- **`apollo-adverts` docblock/constant split** (1.2.0 vs 1.2.2) — WP reads the docblock,
  cache-busting reads the constant, so a mismatch means cache-busting lies. Docblock fixed.
- **`apollo-seed-runner`** existed on disk since 2026-08-29 with no entry. Recorded, in a
  new `L14_tooling` layer.
- **Plugin count disagreed three ways** across the map, `architecture` and `summary`.
- **`bytes` removed from all 21 chapter descriptors** — a field that invalidates on every
  edit; 5 had already drifted. `doctor.js` replaces the intent.
- **Monolith rebuilt.** It was behind its own chapters, and `apollo-registry-2.json` was a
  known-stale mirror. Both are now byte-identical and round-trip clean.

## Earlier: SSOT audit — 2026-07-28

Every registry claim checked against source: **40 plugin folders, 1327 PHP files, ~259k
LOC**. Full record in `19-ssot-audit.json`; raw inputs in `../audit/ground-truth.json`.
Found 6 phantom entries, 1 missing entry (`apollo-lux-panels`, 69 meta keys), 7 version
drifts and 2 version splits.

## data-registry.json — v1.1.0

Merge-verified: `bak`…`bak4` are all strict subsets of the live file, so nothing was lost
across the backup chain. Totals hold at event 42 / dj 21 / local 20 / shell 4 = **87
fields**. Carries `$canvas_shell_contract`, `sources.input_surfaces`,
`$input_surface_audit` (50 of 59 declared meta keys have a metabox input; gaps 11 → 13)
and the append-only `$data_registry_sync_log`.

> Scanner caveat worth remembering: a call-site regex found only **2** meta keys for
> `apollo-lux-panels`. The plugin writes meta through a generic `update_post_meta()` loop
> driven by declarative config, so the set had to be re-derived from the `'key' => '_x'`
> literals — actual count **69**. Regex call-site scans undercount declarative writers.
