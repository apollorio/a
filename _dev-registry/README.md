# `_dev-registry/` — the Apollo developer registry

Machine-generated, human-read description of **what Apollo actually is as
built**: every CPT, taxonomy, meta key, REST route, hook, shortcode, AJAX
action, cron event, table, option, constant and cross-plugin dependency.

**Audience: developers with filesystem access. Nothing here is served to the
web, and nothing here is loaded by WordPress.**

## Why it is safe to keep this next to production code

This folder sits inside `wp-content/plugins/`, which is mirrored to
production. Three independent properties keep it private, and each was
verified against the live host rather than assumed:

| # | Mechanism | Why it holds |
|---|---|---|
| 1 | **No `.php`, no plugin header** | WordPress discovers plugins by scanning `plugins/*/*.php` for a `Plugin Name:` header. A directory with zero PHP contributes nothing to `get_plugins()` and can never appear in `active_plugins`. Same proof as `plugins/superseeded/`. |
| 2 | **Own `.htaccess` → `Require all denied`** | Byte-identical posture to `plugins/_inventory/.htaccess`, which returns **403** live. |
| 3 | **Extension deny in the parent** | `plugins/.htaccess` rule 1 denies `.md` and `.yaml` by extension, anywhere under `plugins/`, independent of any directory file. Verified live: `apollo-telegram/ARCHITECTURE.md` → 403. |

### Why there is no `.json` in this folder

This is the load-bearing detail. `plugins/.htaccess` rule 1 lists
`py sh bash ps1 bak sql log ini env yml yaml lock ffs_db md dist sqlite db`.
**`json` is not in it.** A `.json` file here would rest on mechanism 2 alone.
That is not theoretical — verified live while building this registry:

```
apollo-adverts/phpcs-logs/FINAL_VIPGO_20260212_100658.json   HTTP 200
```

So structured data here is **YAML**, which is double-locked. Keep it that way.

### The one layer that is NOT in place

`plugins/.htaccess` ends with a path-anchored `RedirectMatch 404` covering
`.git`, `.githooks`, `.cursor`, `.svn`, `.hg`, `_to_delete`, `_inventory`,
`mcps`. `_dev-registry` is **not** in that list. Adding it would give a 404
instead of a 403 — a 403 confirms the path exists, which is exactly the
reconnaissance signal that rule was written to remove.

That edit was deliberately **not** made here: `plugins/.htaccess` is a live
security file on a host with no staging tier, and its own header records two
past syntax changes that took the whole site to 500. The exact one-token
change, for a separate reviewed pass:

```
-RedirectMatch 404 (?i)/wp-content/plugins/(\.git|\.githooks|\.cursor|\.svn|\.hg|_to_delete|_inventory|mcps)(/|$)
+RedirectMatch 404 (?i)/wp-content/plugins/(\.git|\.githooks|\.cursor|\.svn|\.hg|_to_delete|_inventory|_dev-registry|mcps)(/|$)
```

## Layout

| File | Contents |
|---|---|
| `00-INDEX.md` | counts, entry points, how to navigate |
| `01-BOOT-AND-OWNERSHIP.md` | boot order, who owns what, the fallback rule |
| `02-PLUGINS.md` | every unit: version, LOC, guards, boot hooks |
| `03-CPT.md` | 15 declared CPTs, full args + as-built registration |
| `04-TAXONOMIES.md` | 18 declared taxonomies + as-built |
| `05-META-KEYS.md` | governed vs ungoverned meta, per CPT |
| `06-REST.md` | namespaces, routes, methods, permission posture |
| `07-HOOKS.md` | hooks published and consumed |
| `08-SHORTCODES.md` | every shortcode tag and its owner |
| `09-AJAX-AND-CRON.md` | AJAX actions (incl. `nopriv`) and scheduled work |
| `10-DATA-STORES.md` | tables, options, constants, capabilities |
| `11-DEPENDENCIES.md` | cross-plugin coupling, unguarded call sites |
| `12-SECURITY-SURFACE.md` | everything reachable without authentication |
| `13-KNOWN-DEFECTS.md` | open findings carried from the evidence root |
| `data/*.yaml` | the same facts, machine-readable |

## Two sources, kept apart on purpose

- **DECLARED** — `apollo-core/config/*.php`, evaluated by real PHP rather
  than pattern-matched. This is what Apollo intends to be.
- **AS-BUILT** — static analysis of every PHP file in every `apollo-*`
  plugin plus `mu-plugin/`. This is what the code actually does.

Where they disagree, both numbers are shown. A registry that silently
reconciles the two would hide precisely the drift it exists to expose.

## Excluded by interlock

`apollo-waha` is **not scanned and not inventoried**. It is frozen under an
open credential interlock (`AWAITING_CREDENTIAL_OWNER`) and a blocked
relocation, and both artifacts forbid inventorying it. Its absence here is
deliberate, not an oversight.

## Regenerating

```bash
node D:/dev/_cos/verify/gen-dev-registry.js
node D:/dev/_cos/verify/verify-dev-registry.js
```

The generator is read-only over product source and writes only into this
folder. It lives in the evidence root, not here, so that this folder stays
free of executable code.

---

_Generated 2026-09-15T05:29:41.847Z from source at `D:/dev/_apollo.rio.br`. Regenerate: `node D:/dev/_cos/verify/gen-dev-registry.js`._
