# W2 Contract Audit — apollo-core config vs disk

**Worker:** Composer 2.5 (W2 CONTRACT)  
**Coordinator:** Grok  
**Date:** 2026-09-15  
**Scope:** Read-only reconciliation of `apollo-core/config/{cpts,taxonomies,meta,tables,routes}.php` against plugin folders on disk and closest `_inventory` registry chapters.  
**Action taken:** Report only — no product PHP edits.

---

## Inventory doc match

Requested chapters `03-CPT`, `04-TAXONOMIES`, `05-META` under `_inventory` **not found** on disk.

Matched against nearest registry/inventory sources instead:

| Requested | Matched source |
|-----------|----------------|
| 03-CPT | `_inventory/registry/09-plugins/apollo-core.json` → `MASTER_REGISTRY.cpts`; `_inventory/registry/16-summary.json` → `quick_lookup.all_cpt_slugs`; `_inventory/CPT-REGISTRATION-MAP-2026-08-11.md` |
| 04-TAXONOMIES | `_inventory/registry/09-plugins/apollo-core.json` → `MASTER_REGISTRY.taxonomies`; `_inventory/registry/16-summary.json` → `quick_lookup.all_taxonomy_slugs` |
| 05-META | `_inventory/registry/05-global-architecture.json` (Meta claim); `_inventory/registry/20-meta-schemas.json`; `meta.php` header (explicit MetaRegistry drift note) |
| Owner phantoms | `_inventory/registry/PLUGIN-DEPLOY-MAP.md` §2 |

---

## files-read

| File | Role |
|------|------|
| `apollo-core/config/cpts.php` | 15 CPT definitions |
| `apollo-core/config/taxonomies.php` | 18 taxonomy definitions |
| `apollo-core/config/meta.php` | Meta lookup table (not registrar) |
| `apollo-core/config/tables.php` | 50 custom table stubs |
| `apollo-core/config/routes.php` | 187 REST route entries |
| `_inventory/registry/09-plugins/apollo-core.json` | `MASTER_REGISTRY` CPT/tax baseline |
| `_inventory/registry/16-summary.json` | `quick_lookup` CPT/tax/table slugs |
| `_inventory/registry/05-global-architecture.json` | Centralization claims |
| `_inventory/registry/20-meta-schemas.json` | Structured meta contracts |
| `_inventory/registry/PLUGIN-DEPLOY-MAP.md` | Phantom plugin policy |
| `_inventory/CPT-REGISTRATION-MAP-2026-08-11.md` | CPT/meta ownership narrative |

Disk enumeration: `ls -d apollo-*` at repo root (41 `apollo-*` directories).

---

## declared-not-on-disk

### Plugin owners referenced in config but with no folder on disk

| Plugin | Declared in | Notes |
|--------|-------------|-------|
| `apollo-suppliers` | `cpts.php` (`supplier` CPT owner); `routes.php` (`/suppliers/*`) | LOCKED_NEXT_VERSION phantom per PLUGIN-DEPLOY-MAP §2 |
| `apollo-cena` | `tables.php` (`apollo_industry_calendar`); `routes.php` (`/cena/*`) | LOCKED_NEXT_VERSION phantom |
| `apollo-pwa` | `routes.php` (`/pwa/manifest`, `/pwa/sw`) | PLANNED phantom |
| `apollo-shortcodes` | `routes.php` (`/shortcodes/*`, `/search`, `/newsletter/subscribe`) | DEPRECATED — absorbed into `apollo-core` per PLUGIN-DEPLOY-MAP §2 |

**Count:** 4 unique phantom owners across the five config files.

### CPT slugs whose declared owner is not on disk

| CPT slug | Owner | Config file |
|----------|-------|-------------|
| `supplier` | `apollo-suppliers` | `cpts.php` |

All other 14 CPT owners resolve to on-disk folders.

### Registry / config slug drift (declared in inventory, absent from `cpts.php`)

| Slug | In registry | In `cpts.php` | Verdict |
|------|-------------|---------------|---------|
| `journal_news` | `MASTER_REGISTRY.cpts`, `16-summary` | ✗ | **DRIFT** — no `apollo-core/config` entry; no grep hit under `apollo-core/` |
| `journal_nota` | `MASTER_REGISTRY.cpts`, `16-summary` | ✗ | **DRIFT** — same |
| `loc` | `MASTER_REGISTRY.cpts` (slug `loc`) | ✗ (config uses `local`) | **DRIFT** — slug rename not reflected in registry `MASTER_REGISTRY` |

### Taxonomy slug drift (registry vs `taxonomies.php`)

| Config slug | Registry `MASTER_REGISTRY` slug | Verdict |
|-------------|----------------------------------|---------|
| `local_type` | `loc_type` | **DRIFT** — rename not propagated to registry |
| `local_area` | `loc_area` | **DRIFT** — rename not propagated to registry |

Journal taxonomies (`music`, `culture`, `rio`, `formato`) are in `taxonomies.php` but **not** in `MASTER_REGISTRY.taxonomies` nor `16-summary.quick_lookup.all_taxonomy_slugs`.

### Table inventory drift

`tables.php` declares **50** tables. `16-summary.quick_lookup.all_table_names` lists **78** names. Six tables in config are absent from summary; 34 summary tables are absent from config. Full diff deferred — **DRIFT** flagged, not resolved here.

### Meta drift (documented in source)

`meta.php` header states it is **not the registrar** and is already out of sync with `MetaRegistry.php` (11 `dj` / 13 `local` keys here vs 38 / 27 in MetaRegistry). **DRIFT** — pre-existing, acknowledged in-file.

---

## on-disk-not-declared

Apollo plugin folders present on disk but **not referenced as `owner` in any of the five config files**:

| Plugin folder | Likely role (not verified — no config owner) |
|---------------|-----------------------------------------------|
| `apollo-dj-sync` | DJ session sync companion |
| `apollo-elementor` | Page-builder integration |
| `apollo-elementor-pro` | Elementor Pro bridge |
| `apollo-lux-panels` | Admin metabox panels |
| `apollo-maps` | Map surfaces |
| `apollo-pane-engine` | REST test harness / pane host |
| `apollo-radio` | Radio feature |
| `apollo-remind` | Reminder subsystem |
| `apollo-soundcloud` | SoundCloud playback provider |
| `apollo-telegram` | Telegram integration |

**Count:** 10 of 41 on-disk `apollo-*` folders.

These are expected infrastructure/feature plugins without central config contract entries; not automatically errors.

---

## Contract counts (config ground truth)

| Surface | Count in config | Registry claim | Summary claim |
|---------|-----------------|----------------|---------------|
| CPTs | **15** (`cpts.php`; file header still says "9") | 10 (`MASTER_REGISTRY`) | 17 (`quick_lookup`) |
| Taxonomies | **18** | 13 (`MASTER_REGISTRY`) | 12 (`quick_lookup`) |
| Tables | **50** | 22 (`MASTER_REGISTRY.tables`) | 78 (`quick_lookup`) |
| REST routes | **187** | 197+ (file header) | — |
| Meta keys (`meta.php`) | partial lookup | 100+ (`MASTER_REGISTRY`) | — |

---

## CPT inventory (`cpts.php`)

| Slug | Owner | Owner on disk |
|------|-------|---------------|
| `event` | `apollo-events` | ✓ |
| `dj` | `apollo-djs` | ✓ |
| `track` | `apollo-djs` | ✓ |
| `hostel` | `apollo-adverts` | ✓ |
| `local` | `apollo-loc` | ✓ |
| `classified` | `apollo-adverts` | ✓ |
| `supplier` | `apollo-suppliers` | ✗ |
| `doc` | `apollo-docs` | ✓ |
| `email_aprio` | `apollo-email` | ✓ |
| `hub` | `apollo-hub` | ✓ |
| `apollo_sheet` | `apollo-sheets` | ✓ |
| `appointment` | `apollo-scheduler` | ✓ |
| `service` | `apollo-scheduler` | ✓ |
| `resource` | `apollo-scheduler` | ✓ |
| `apollo_agent_log` | `apollo-membership` | ✓ |

---

## Taxonomy inventory (`taxonomies.php`)

18 slugs: `sound`, `season`, `event_category`, `event_type`, `event_tag`, `local_type`, `local_area`, `classified_domain`, `classified_intent`, `doc_folder`, `doc_type`, `supplier_category`, `supplier_service`, `music`, `culture`, `rio`, `formato`, `coauthor`.

Owners: `apollo-core` (14), `apollo-journal` (4).

---

## Summary verdict

| Check | Result |
|-------|--------|
| Phantom owners in config (`apollo-suppliers`, `apollo-cena`, `apollo-pwa`, `apollo-shortcodes`) | Expected per PLUGIN-DEPLOY-MAP §2 — config retains routes/CPT for future or absorbed plugins |
| `supplier` CPT owner missing on disk | **declared-not-on-disk** (known phantom) |
| Registry `MASTER_REGISTRY` vs live `cpts.php` | **DRIFT** — counts and slugs diverge (`local` vs `loc`, missing scheduler/track/hostel/sheet/agent_log, phantom journal CPTs in registry only) |
| Registry taxonomies vs `taxonomies.php` | **DRIFT** — `loc_*` vs `local_*` rename; journal taxonomies only in config |
| `meta.php` vs `MetaRegistry.php` | **DRIFT** — documented in `meta.php` header |
| On-disk plugins without config owner | 10 folders — informational, not contract violations |

**No changes made.** Coordinator should decide whether registry chapters or `apollo-core/config/*` are authoritative for each drift item.
