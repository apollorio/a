# W1 Disk Map — Apollo plugins vs registry

**Worker:** W1 DISK MAP (Composer 2.5)  
**Generated:** 2026-09-15  
**Slice:** top-level directories vs `architecture.layers` + `plugins{}` keys (registry chapters in `_inventory/registry/09-plugins/`)  
**Repo root:** `wp-content/plugins` (flat)

## Summary

| Status | Count |
| --- | ---: |
| MATCH | 41 |
| MISSING_ON_DISK | 6 |
| MISSING_IN_REG | 0 |
| SKIP | 12 |

| Source | Count |
| --- | ---: |
| Registry chapters (`09-plugins/*.json`) | 47 |
| Monolith `plugins{}` keys | 46 |
| `apollo-*` dirs on disk | 41 |
| `architecture.total_plugins` (layers) | 46 |

## MATCH (41)

All registry plugins present on disk with a layer assignment (except noted below).

## MISSING_ON_DISK (6)

Registry entry exists; no matching directory on disk.

| slug | layer | notes |
| --- | --- | --- |
| apollo-cena | L8_industry | documented absent in `$layers_absent_from_disk` |
| apollo-classifieds | — | absorbed into apollo-adverts (registry note) |
| apollo-pwa | L9_pwa | documented absent in `$layers_absent_from_disk` |
| apollo-runtime | L12_runtime | documented absent in `$layers_absent_from_disk` |
| apollo-shortcodes | — | absorbed into apollo-core (registry note) |
| apollo-suppliers | L2_content | documented absent in `$layers_absent_from_disk` |

## MISSING_IN_REG (0)

None — every `apollo-*` directory on disk has a registry chapter.

## SKIP (12)

Ignored per worker spec: `apollo-waha`, `elementor*`, `loginizer*`, `query-monitor`, `really-simple-ssl`, `wp-debugging`, `_to_delete`, `RecycleBin*`, `sync.ffs*`, `mcps`, `.git*`, `.vscode`, `.cursor`.

| slug | on_disk |
| --- | --- |
| .cursor | Y |
| .git | Y |
| .githooks | Y |
| RecycleBin~e278.ffs_tmp | Y |
| _to_delete | Y |
| elementor | Y |
| loginizer | Y |
| loginizer-security | Y |
| mcps | Y |
| query-monitor | Y |
| really-simple-ssl | Y |
| wp-debugging | Y |

## DRIFT (no changes made)

- **Chapter-only plugin keys** (in `09-plugins/` but absent from monolith `plugins{}`): `apollo-soundcloud`

- **Registry plugins without `architecture.layers` assignment:** `apollo-classifieds`, `apollo-shortcodes`
- Monolith `architecture.layers` omits `apollo-soundcloud` in L6_frontend (chapter `08-architecture-layers.json` includes it; monolith grep has zero hits).

## TSV

See [`W1-disk-map.tsv`](./W1-disk-map.tsv).
