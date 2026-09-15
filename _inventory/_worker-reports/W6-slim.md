# W6 SLIM INDEX — draft report

| Field | Value |
| --- | --- |
| Worker | W6 (SLIM INDEX) |
| Generated | 2026-09-15 |
| Output | `W6-slim.draft.json` |
| Byte size | **20180** (19.7 KiB) |
| Budget | 81920 bytes (80 KiB) |
| Within budget | yes |

## Coverage

| Metric | Count |
| --- | ---: |
| Plugins on disk | 41 |
| REG-only (status `missing`) | 6 |
| On disk, no registry chapter | 0 |
| Total plugin entries | 47 |
| Architecture layers | 14 |

## Keys emitted

- `$philosophy`
- `architecture.layers`
- `plugins.{slug,status,layer,cpts,rest_prefixes}`
- `namingRules`
- `constants` (registry + live `apollo-core/config/constants.php`)
- `quick_lookup`
- `cdn` (runtime load contract; `nav_header_v6` omitted)

## REG-only missing (no folder on disk)

- `apollo-cena`
- `apollo-classifieds`
- `apollo-pwa`
- `apollo-runtime`
- `apollo-shortcodes`
- `apollo-suppliers`

## On-disk without registry chapter

_none_

## Build

```bash
node _inventory/_worker-reports/build-w6-slim.mjs
```
