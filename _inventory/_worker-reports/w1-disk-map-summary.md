# W1 DISK MAP — apollo-* plugins

**Worker:** W1 DISK MAP  
**Date:** 2026-09-15  
**Sources:** `_inventory/registry/09-plugins/`, `_inventory/registry/08-architecture-layers.json`

## Counts

| Metric | Value |
|--------|-------|
| On disk (apollo-* dirs) | 41 |
| In registry (09-plugins) | 47 |
| MATCH | 41 |
| MISSING_ON_DISK | 6 |
| MISSING_IN_REG | 0 |
| SKIP | 0 |

## MISSING_ON_DISK (registry only)

- `apollo-cena` (L8_industry)
- `apollo-classifieds`
- `apollo-pwa` (L9_pwa)
- `apollo-runtime` (L12_runtime)
- `apollo-shortcodes`
- `apollo-suppliers` (L2_content)

## MISSING_IN_REG (disk only)

_none_

## Notes

- Registry declares **46** total plugins; architecture file records **40** on disk and **6** absent.
- Absent layers noted in registry: apollo-cena, apollo-pwa, apollo-runtime, apollo-suppliers.
- Non-directory file at repo root: `apollo-events-helpers-guard.php` (not counted as plugin dir).
- Six registry entries have no folder per project docs: apollo-cena, apollo-classifieds, apollo-pwa, apollo-runtime, apollo-shortcodes, apollo-suppliers.
