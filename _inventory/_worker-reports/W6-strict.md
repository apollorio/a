# W6 STRICT SLIM

Coordinator: Grok · Composer 2.5 · branch `cursor/registry-all-plugins-incl-waha`

## Artefacts

| File | Bytes | Max |
|---|---:|---:|
| `_inventory/_worker-reports/W6-strict-slim.draft.json` | 18697 | 81920 |

## Keys (strict allowlist)

`$philosophy` · `architecture.layers` · `plugins.{slug,status,layer,cpts,rest_prefixes}` · `namingRules` · `constants` · `quick_lookup` · `cdn`

## Plugins on disk

**44** entries — all `apollo-*` directories present in workspace root.

| Slug | Layer | Status | REST prefix |
|---|---|---|---|
| apollo-waha | L4_communication | IMPLEMENTED | `wa` |
| apollo-ui | L6_frontend | IMPLEMENTED | — |
| apollo-seed-runner | L14_tooling | IMPLEMENTED | — |

Previously skipped in W6-slim: `apollo-waha` (now in registry + layers). `apollo-ui` promoted from `on_disk_not_in_reg` to `IMPLEMENTED` / `L6_frontend`.

## Strict deltas vs W6-slim.draft.json

1. **cdn** — `nav_header_v6` stripped (lives in chapter `06-cdn.json`; not slim-index material).
2. **plugins.rest_prefixes** — derived from each chapter's `rest[].endpoint` first segment (not left empty).
3. **architecture.layers** — synced from `08-architecture-layers.json` (includes `apollo-waha`, `apollo-ui`).
4. **quick_lookup.all_rest_prefixes** — `wa` added for apollo-waha webhook route.

## Absent-from-disk (layer placeholders only, not in `plugins`)

`apollo-cena` · `apollo-classifieds` · `apollo-pwa` · `apollo-runtime` · `apollo-shortcodes` · `apollo-suppliers`

## chapters/_slim.draft.json

**NOT written** — path does not exist (`_inventory/registry/chapters/_slim.draft.json` absent; coordinator rule).

## Source chapters

| Key | Chapter |
|---|---|
| `$philosophy` | `01-philosophy.json` |
| `architecture.layers` | `08-architecture-layers.json` |
| `plugins.*` | `09-plugins/{slug}.json` × 44 on-disk |
| `namingRules`, `constants` | `15-conventions.json` |
| `quick_lookup` | `16-summary.json` (+ `wa` prefix) |
| `cdn` | `06-cdn.json` (minus `nav_header_v6`) |
