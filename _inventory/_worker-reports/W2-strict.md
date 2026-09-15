# W2 STRICT CONTRACT

Generated: 2026-09-15T16:21:00.079Z
Coordinator: Grok · Worker: Composer 2.5 · **No PHP edits**
Branch: `cursor/registry-all-plugins-incl-waha`

## files-read
- `apollo-core/config/cpts.php`: 21851 bytes
- `apollo-core/config/taxonomies.php`: 21025 bytes
- `apollo-core/config/meta.php`: 17160 bytes
- `apollo-core/config/tables.php`: 6820 bytes
- `apollo-core/config/routes.php`: 30946 bytes

## registry docs matched
- `_dev-registry/03-CPT.md`
- `_dev-registry/04-TAXONOMIES.md`
- `_dev-registry/05-META-KEYS.md` (governed population only; ungoverned/touched keys excluded)
- `tables.php` / `routes.php`: read for inventory; no strict MD chapter

## CPT (`03-CPT.md` declared vs `cpts.php`)

- declared (_dev-registry): **15**
- on-disk (apollo-core/config): **15**

### declared-not-on-disk

- _(none)_

### on-disk-not-declared

- _(none)_


### CPT — owner-registered, not in central `cpts.php` (documented gap, not drift)

- `journal_news`
- `journal_nota`

## Taxonomies (`04-TAXONOMIES.md` vs `taxonomies.php`)

- declared (_dev-registry): **18**
- on-disk (apollo-core/config): **18**

### declared-not-on-disk

- _(none)_

### on-disk-not-declared

- _(none)_


## Meta keys (`05-META-KEYS.md` governed vs `meta.php`)

- declared (_dev-registry): **141**
- on-disk (apollo-core/config): **141**

### declared-not-on-disk

- _(none)_

### on-disk-not-declared

- _(none)_


## tables.php (on-disk inventory)

**50** suffix keys under `{wp_prefix}apollo_*`.

## routes.php (on-disk inventory)

**187** paths under namespace `apollo/v1`.

## summary

| surface | declared | on-disk | declared-not-on-disk | on-disk-not-declared |
|---|---:|---:|---:|---:|
| CPT | 15 | 15 | 0 | 0 |
| Taxonomies | 18 | 18 | 0 | 0 |
| Meta (governed) | 141 | 141 | 0 | 0 |
| Tables | — | 50 | — | — |
| Routes | — | 187 | — | — |

## notes
- **declared** = generated `_dev-registry` markdown (03/04/05).
- **on-disk** = `apollo-core/config/*.php` return-array keys.
- Meta governed set excludes the 224 *touched-but-ungoverned* keys listed in 05-META-KEYS.md § Ungoverned.
- `meta.php` header documents drift vs `MetaRegistry.php` registrar; this report compares MD ↔ `meta.php` only.
- No PHP edits made.