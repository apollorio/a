# W1 strict disk map

**Branch:** `cursor/registry-all-plugins-incl-waha`  
**Coordinator:** Grok · **Worker:** Composer 2.5  
**Generated:** 2026-09-15

## Scope

| Source | Path |
| --- | --- |
| Disk | `apollo-*` top-level directories in repo root |
| Registry | `chapters['09-plugins'].files` in `00-registry-map.json` (50 entries) |
| Layers | `architecture.layers` in `08-architecture-layers.json` |

**Policy overrides**

- `apollo-waha` included (was previously SKIP in loose W1).
- Third-party dirs excluded from scan: `elementor*`, `loginizer*`, `query-monitor`, `really-simple-ssl`, `wp-debugging`.

## Totals

| Metric | Count |
| --- | ---: |
| Registry entries (`09-plugins`) | 50 |
| `apollo-*` dirs on disk | 44 |
| MATCH | 44 |
| MISSING_ON_DISK | 6 |
| MISSING_IN_REG | 0 |

Registry file list and `09-plugins/*.json` are in sync (0 drift).

## Status breakdown

### MATCH (44)

All on-disk `apollo-*` plugins have a registry chapter and an architecture layer assignment.

Notable inclusions vs prior loose W1:

- `apollo-ui` — now `L6_frontend` (was MISSING_IN_REG).
- `apollo-waha` — now `L4_communication` (was SKIP).

### MISSING_ON_DISK (6)

Registry entries with no plugin folder (documented in `absent_from_disk`):

| Slug | Layer | Note |
| --- | --- | --- |
| apollo-cena | L8_industry | never built |
| apollo-classifieds | L2_content | absorbed into apollo-adverts |
| apollo-pwa | L9_pwa | never built |
| apollo-runtime | L12_runtime | never built |
| apollo-shortcodes | L0_foundation | absorbed into apollo-core |
| apollo-suppliers | L2_content | never built |

Layers L8, L9, L12 are effectively empty on disk.

### MISSING_IN_REG (0)

No orphan disk folders.

## Layer coverage (on-disk only)

| Layer | Count | Plugins |
| --- | ---: | --- |
| L0_foundation | 1 | apollo-core |
| L1_auth | 3 | apollo-login, apollo-users, apollo-membership |
| L2_content | 9 | apollo-adverts, apollo-calendar, apollo-dj-sync, apollo-djs, apollo-events, apollo-loc, apollo-maps, apollo-scheduler |
| L3_social | 5 | apollo-social, apollo-groups, apollo-wow, apollo-fav, apollo-comment |
| L4_communication | 6 | apollo-chat, apollo-email, apollo-notif, apollo-remind, apollo-telegram, **apollo-waha** |
| L5_documents | 4 | apollo-docs, apollo-sign, apollo-gestor, apollo-journal |
| L6_frontend | 7 | apollo-templates, apollo-dashboard, apollo-hub, apollo-radio, apollo-soundcloud, apollo-pane-engine, apollo-ui |
| L7_admin | 5 | apollo-admin, apollo-coauthor, apollo-lux-panels, apollo-mod, apollo-statistics |
| L10_seo | 1 | apollo-seo |
| L11_data | 1 | apollo-sheets |
| L13_pagebuilder | 2 | apollo-elementor, apollo-elementor-pro |
| L14_tooling | 1 | apollo-seed-runner |

## Verdict

Strict W1 is **green**: disk ↔ registry alignment is complete for all 44 on-disk plugins. The only drift is the six known absent registry entries — no action required unless retiring or building those plugins.

TSV artefact: `W1-strict.tsv` (pipe-delimited, 50 data rows + header).
