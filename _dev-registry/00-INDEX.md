# Apollo — DEV registry index

`2026-09-15T05:29:41.847Z`

## Scale

| metric | value |
|---|---|
| units scanned | 43 plugins + `mu-plugin` |
| units excluded | apollo-waha |
| PHP files | 1,549 |
| lines of PHP | 307,795 |
| classes / interfaces / traits | 590 |


## Surface

| surface | declared | as-built | file |
|---|---|---|---|
| CPTs | 15 | 14 registration sites | `03-CPT.md` |
| taxonomies | 18 | 15 sites | `04-TAXONOMIES.md` |
| post meta keys | 141 governed | 302 touched | `05-META-KEYS.md` |
| REST routes | 187 | 391 call sites / 441 endpoints | `06-REST.md` |
| hooks published | — | 552 distinct | `07-HOOKS.md` |
| shortcodes | — | 86 distinct | `08-SHORTCODES.md` |
| AJAX actions | — | 79 distinct | `09-AJAX-AND-CRON.md` |
| custom tables | 50 | 76 referenced | `10-DATA-STORES.md` |
| options | 22 | 159 touched | `10-DATA-STORES.md` |
| constants | 46 | 416 defined | `10-DATA-STORES.md` |
| roles | 5 | 22 caps checked | `10-DATA-STORES.md` |


## Immediate attention

| finding | count | where |
|---|---|---|
| REST **endpoints** with `permission_callback => __return_true` | 112 | `12-SECURITY-SURFACE.md` |
| — of those accepting writes | 15 | `12-SECURITY-SURFACE.md` |
| REST endpoints with **no** `permission_callback` | 0 | `12-SECURITY-SURFACE.md` |
| AJAX actions reachable logged-out (`nopriv`) | 14 | `12-SECURITY-SURFACE.md` |
| meta keys written but never governed | 224 | `05-META-KEYS.md` |
| governed meta keys never touched in code | 63 | `05-META-KEYS.md` |
| CPTs registered but absent from `cpts.php` | 2 (journal_news, journal_nota) | `03-CPT.md` |
| unguarded cross-plugin function calls | 43 | `11-DEPENDENCIES.md` |
| open defects carried from evidence root | 9 | `13-KNOWN-DEFECTS.md` |


## Reading order

Start at `01-BOOT-AND-OWNERSHIP.md`. It explains the single rule that makes
the rest of the numbers make sense: **apollo-core registers everything as a
fallback, and every owner plugin yields to it via an existence guard.**
Without that rule, `03-CPT.md` looks like fifteen duplicate registrations.

---

_Generated 2026-09-15T05:29:41.847Z from source at `D:/dev/_apollo.rio.br`. Regenerate: `node D:/dev/_cos/verify/gen-dev-registry.js`._
