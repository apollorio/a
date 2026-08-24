# Apollo Modular Registry

The monolithic `apollo-registry.json` (2.2 MB, 27 top-level keys, 45 plugins) is now a
chaptered SSOT. Nothing was deleted: rebuilding every chapter in `load_order` reproduces
the monolith **exactly**.

## Layout

```
_inventory/
├── apollo-registry.json          ← canonical monolith (BUILD OUTPUT — do not hand-edit)
├── apollo-registry-2.json        ← stale mirror (see Divergences)
├── data-registry.json            ← mockup ↔ DB field map (separate SSOT)
└── registry/
    ├── 00-registry-map.json      ← master index: chapters, load_order, build_rules
    ├── build.js                  ← reassembles chapters → monolith
    ├── verify.py                 ← proves the split is lossless
    ├── 01-philosophy.json … 18-canvas-shell.json
    └── 09-plugins/
        └── {slug}.json           ← one file per plugin (45)
```

## Editing

Edit the **chapter**, never the monolith.

```bash
cd _inventory/registry
node build.js            # validate + round-trip check, writes nothing
node build.js --write    # promote → apollo-registry.json + apollo-registry-2.json
python3 verify.py        # lossless proof, subtracting declared intentional additions
```

`build.js` exits `1` on any validation failure: a missing or unparseable chapter, two
chapters claiming the same top-level key, a plugin count mismatch, or a missing
`apollo-core` master entry.

## Merge semantics

`deep_merge_last_wins`. Objects merge recursively; **arrays are replaced wholesale**.
The `$chapter` key in every file is metadata and is stripped at build time.

`$deep_audit` is deliberately split four ways — `04-audit` (run metadata),
`04-audit-reports` (39 per-plugin reports), `10-coupling`, `11-security` — and
deep-merges back into one object. It is the only shared top-level key; every other key
has exactly one owner, and the builder enforces that.

Plugin files hold the **bare** entry. The builder wraps each one as `plugins.{slug}`
using its filename, per `chapters["09-plugins"].wrap`.

## Chapters

| Chapter | Owns | Owner |
|---|---|---|
| 01-philosophy | `$philosophy` | apollo-core |
| 02-header | `$header` + the 4 root scalars | apollo-core |
| 03-apollo-rule | `$apollo_rule` | apollo-core |
| 04-audit | `$audit`, `$deep_audit` run metadata | apollo-core |
| 04-audit-reports | `$deep_audit.reports` (39) | apollo-core |
| 05-global-architecture | `$GLOBAL_ARCHITECTURE` | apollo-core |
| 06-cdn | `cdn` | apollo-templates |
| 07-icons | `icons` | apollo-core |
| 08-architecture-layers | `architecture` | apollo-core |
| 09-plugins/ | `plugins.*` (45 files) | each plugin |
| 10-coupling | `$deep_audit.coupling_edges`, `.high_risk_plugins` | apollo-core |
| 11-security | `$deep_audit.open_rest_total`, `.remediations_applied` | apollo-login |
| 12-data-sync | `$data_registry_sync` | apollo-core |
| 13-runtime | `runtime_wordpress_packages` | apollo-runtime |
| 14-routing | `$routing_canonical`, `$mu_plugins`, `$third_party_plugins` | apollo-core |
| 15-conventions | `roles`, `timeDisplay`, `namingRules`, `constants` | apollo-core |
| 16-summary | `summary`, `quick_lookup`, `divergences` | apollo-core |
| 17-backlog | `$to_be_done` | apollo-core |
| 18-canvas-shell | `$canvas_shell` | apollo-templates |
| 19-ssot-audit | `$ssot_audit` | apollo-core |

## Adjustments made to the base map

The proposed base index was v1.0.0. It is now v1.1.0 because reality differed —
every adjustment is recorded in `$adjustments_from_base_v1`:

- **45 plugins, not 42.** Added `apollo-calendar`, `apollo-classifieds`, `apollo-shortcodes`.
- **Nested keys were listed as top-level.** `FORBIDDEN_CONCEPTS`, `data_flow`,
  `security_audit`, `ecosystem_hook_spine`, `nav_header_v6`, `layers`, `total_plugins`
  and others live inside their parents; they are owned via the parent, not separately.
- **Four named keys do not exist** in the monolith: `MASTER_REGISTRY` (it is
  `architecture.master_registry`), `js_data_contract`, `js_modules_map`,
  `PluginInventorySync`. Dropped rather than invented.
- **Eleven top-level keys had no chapter** — `roles`, `timeDisplay`, `namingRules`,
  `constants`, `summary`, `quick_lookup`, `divergences`, `$routing_canonical`,
  `$mu_plugins`, `$third_party_plugins`, `$to_be_done`. Chapters 14–17 exist so the
  split loses nothing.
- **Chapter 18 added** to record the Blank Canvas migration.

## SSOT audit — 2026-07-28

Every registry claim was checked against the real source: **40 plugin folders,
1327 PHP files, ~259k LOC**. Full record in `19-ssot-audit.json`; raw inputs in
`../audit/ground-truth.json` and `../audit/registry-ssot-audit.json`.

What it found:

- **6 phantom entries** — `apollo-cena`, `apollo-classifieds`, `apollo-pwa`,
  `apollo-runtime`, `apollo-shortcodes`, `apollo-suppliers` have no folder on
  disk. Entries are **preserved and flagged** `$disk_status: "ABSENT"`, never
  deleted. Decide per plugin: build it, or retire the entry.
- **1 missing entry** — `apollo-lux-panels` is a real, implemented plugin the
  registry never recorded. Entry created from a source scan, placed in `L7_admin`.
  It is the admin metabox input surface for event/dj/local (**69 meta keys**).
- **7 version drifts** corrected from disk, and **2 version splits** fixed where
  the docblock disagreed with the `APOLLO_*_VERSION` constant (`apollo-templates`
  1.3.0 vs 1.3.2, `apollo-telegram` 1.1.1 vs 1.1.2). WordPress reads the docblock;
  the constant drives cache-busting — a mismatch means cache-busting lies.
- **Counts corrected**: `architecture.total_plugins` 34 → 46,
  `summary.total_plugins` 45 → 46. Five plugins had no layer.

## Still open (recorded, not silently fixed)

- `apollo-core` source was edited for the canvas contract but its version stayed
  `6.2.3`. Bump before deploy.
- 6 registry entries have no plugin on disk.
- `dj.name` and `local.hero_image` claim a metabox that `apollo-lux-panels`
  does not render.
- `apollo-registry-2.json` is **stale** — identical except
  `plugins.{events,djs,loc}.meta` (pre data-registry rename) and it lacks
  `$data_registry_sync`. `apollo-registry.json` is canonical.
- `php -l` not run — no PHP binary in the audit sandbox.

## data-registry.json — v1.1.0

Merge-verified: `bak`, `bak2`, `bak3`, `bak4` are all **strict subsets** of the
live file, so nothing was ever lost across the backup chain. Totals hold at
event 42 / dj 21 / local 20 / shell 4 = **87 fields**.

Added this pass:

- `$canvas_shell_contract` — binds `surfaces.shell` to the Apollo / Apollo+
  variants, with the constraint that `shell.user` and `shell.radar` are
  guaranteed **only on Apollo+** pages.
- `sources.input_surfaces` — names `apollo-lux-panels` as the metabox provider
  for the first time.
- `$input_surface_audit` — 50 of 59 declared meta keys have a metabox input.
  **15 fields said `input.metabox: null` while the metabox demonstrably renders
  them** — corrected to `true`. Two new gaps opened (`dj.name`,
  `local.hero_image`); gaps 11 → 13.
- `$data_registry_sync_log` — append-only change log.

> Scanner caveat worth remembering: a call-site regex found only **2** meta keys
> for `apollo-lux-panels`. The plugin writes meta through a generic
> `update_post_meta($id, $key, ...)` loop driven by declarative config, so the
> set had to be re-derived from the `'key' => '_x'` literals — actual count **69**.
