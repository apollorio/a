# W4 · BOOT — mu-plugins vs `$mu_plugins` + root drift

| Field | Value |
|---|---|
| **Worker** | W4 BOOT |
| **Coordinator** | Grok |
| **Model** | Composer 2.5 |
| **Slice** | boot / mu-plugins vs `$mu_plugins` in registry chapters; root `force-load-*` and `*-guard.php` |
| **Generated** | 2026-09-15 |
| **Repo** | `apollorio/a` (`/workspace` = `wp-content/plugins/`) |
| **Mode** | Report-only — no product PHP edits |

---

## 1 · Executive summary

The **canonical MU-plugin contract** lives in registry chapter **`14-routing.json`** under `$mu_plugins`. It documents **five** must-use plugins that live on production at `wp-content/mu-plugins/` — **none of which are present in this plugins monorepo**.

`apollo-brain.php` is **confirmed absent** from disk in this repo (expected: MU layer is out-of-tree).

Three **root-level drift artefacts** sit in the mirrored `plugins/` root and are **not** part of the registry MU contract. They are **report-only DRIFT** flags:

| File | Verdict |
|---|---|
| `force-load-apollo-events.php` | DRIFT — wrong tree; not in `$mu_plugins` |
| `apollo-events-helpers-guard.php` | DRIFT — wrong tree; diagnostic guard with agent log hook |
| `debug-e031aa.log` (+ copies) | DRIFT — live diagnostic logs with server paths |

---

## 2 · Method

1. Targeted read of registry chapter **`14-routing.json`** → key `$mu_plugins`.
2. Cross-check **`13-runtime.json`** → `runtime_wordpress_packages.must_use_plugins` (runtime-populated skeleton).
3. Cross-check **`02-header.json`** and **`05-global-architecture.json`** for boot spine references.
4. Glob + grep across `/workspace` for `mu-plugins/`, `apollo-brain.php`, `force-load-*`, `*-guard.php`, `debug-*.log`.
5. Read root drift PHP files in full.
6. No product PHP edits; no live WP filesystem access.

---

## 3 · Registry extract — `$mu_plugins`

**Chapter:** `_inventory/registry/14-routing.json`  
**Owner:** apollo-core  
**Merge:** `deep_merge_last_wins`  
**Source monolith:** `plugins/_inventory/apollo-registry.json`

> Must-Use plugins in `wp-content/mu-plugins/` — loaded before regular plugins; cannot be deactivated from wp-admin.

### 3.1 · apollo-brain

| Key | Value |
|---|---|
| **file** | `mu-plugins/apollo-brain.php` |
| **version** | 1.2.0 |
| **status** | active |
| **priority** | -100 |
| **hook** | `muplugins_loaded` priority -100 |
| **constants** | `APOLLO_REGISTRY_PATH`, `APOLLO_BRAIN_LOADED` |
| **actions** | `apollo/brain/before_apollo_core`, `apollo/brain/after_apollo_core_file`, `apollo/brain/bootstrap_complete`, `apollo/brain/loaded`, `apollo/brain/page_cache_maybe_init` |

**Role (registry):** Early boot — defines `APOLLO_REGISTRY_PATH` (`wp-content/apollo-registry.json`), validates JSON readable, `require_once apollo-core/apollo-core.php` **before** `active_plugins`. `script_loader_tag` defer for safe front bundles. `save_post` / `transition_post_status` purge `apollo_purge_*` and `Registry::clear_cache()`. Does **not** init CPT/CDN/security — `apollo_core_bootstrap` on `plugins_loaded` P1 does that.

### 3.2 · apollo-debug

| Key | Value |
|---|---|
| **file** | `mu-plugins/apollo-debug.php` |
| **status** | dev-only |
| **guard** | `WP_DEBUG` or `APOLLO_DEV_MODE` |
| **provides** | `apollo_debug_log()`, Query Monitor Apollo panel |

### 3.3 · apollo-dev-routing

| Key | Value |
|---|---|
| **file** | `mu-plugins/apollo-dev-routing.php` |
| **status** | dev-only |
| **guard** | `APOLLO_DEV_MODE` |

Logs which route handler wins (`template_redirect`) when `APOLLO_DEV_MODE` is true.

### 3.4 · apollo-route-setup

| Key | Value |
|---|---|
| **file** | `mu-plugins/apollo-route-setup.php` |
| **status** | active |
| **option_flag** | `apollo_route_setup_version` |
| **current_flag_example** | `2026.05.20.2` |

One-time maintenance on `init` P20: drafts WP Pages whose slugs collide with `apollo_get_reserved_virtual_slugs()`, `apollo_templates_add_rewrite_rules()`, `flush_rewrite_rules(false)`, `Registry::clear_cache()`, `FrontRouteDispatcher::clear_cache()`.

### 3.5 · apollo-error-handler

| Key | Value |
|---|---|
| **file** | `mu-plugins/apollo-error-handler.php` |
| **status** | active |

Late `template_redirect`: funnel `is_404` to `https://apollo.rio.br/erro/{code}/` (skips REST/AJAX as configured). Referenced in `$routing_canonical.error_funnel`.

---

## 4 · Boot spine (registry)

From **`05-global-architecture.json`** → `$GLOBAL_ARCHITECTURE.ecosystem_hook_spine`:

```
apollo/brain/* (MU)
  → apollo_core_bootstrap (plugins_loaded P1)
  → ApolloHook::CORE_INITIALIZED
  → apollo/login/registered | apollo/login/verification_email (apollo-email)
  → REST namespace apollo/v1 …
```

From **`02-header.json`**:

- Runtime registry file: `wp-content/apollo-registry.json` (constant `APOLLO_REGISTRY_PATH` set by **apollo-brain**).
- Deploy workflow step 3: bump `mu-plugins/apollo-route-setup.php` `apollo_route_setup_version` or `Registry::clear_cache()`.
- Optional: `POST /apollo/v1/registry/inventory-sync` refreshes `runtime_wordpress_packages`.

From **`13-runtime.json`** → `runtime_wordpress_packages`:

- Schema skeleton with empty `must_use_plugins: {}` and `paths.mu_plugins_dir: ""`.
- Populated at runtime by `Apollo\Core\PluginInventorySync::scan_mu_plugins()` → `get_mu_plugins()` (see `apollo-core/src/Core/PluginInventorySync.php`).

---

## 5 · Monorepo vs live — apollo-brain confirmed absent

| Check | Result |
|---|---|
| `**/mu-plugins/**` in repo | **0 files** — no `mu-plugins/` directory |
| `**/apollo-brain.php` in repo | **0 files** |
| `apollo-brain` string in PHP under repo | **0 matches** (only registry/audit JSON) |

**Conclusion:** `apollo-brain.php` is **not** in this plugins monorepo. This matches prior audit scope (`_inventory/audit/audit-summary.json` → `mu_plugins_out_of_scope`: apollo-brain, apollo-debug, apollo-dev-routing, apollo-route-setup, apollo-error-handler).

The MU layer is expected to live at **`wp-content/mu-plugins/`** on the WordPress host — a sibling of `wp-content/plugins/`, not inside the mirrored plugins tree.

---

## 6 · DRIFT — root `force-load-*` and `*-guard.php`

These files sit at **`plugins/` root** (this monorepo root). WordPress does **not** auto-load arbitrary PHP from the plugins directory root; they only take effect if manually copied to `mu-plugins/` or required elsewhere.

### 6.1 · `force-load-apollo-events.php`

| Attribute | Detail |
|---|---|
| **Path** | `/workspace/force-load-apollo-events.php` (904 B) |
| **In `$mu_plugins`?** | **No** |
| **Comment claims** | Same pattern as `force-load-apollo-login` / `force-load-apollo-telegram` |
| **Those siblings on disk?** | **No** — only `force-load-apollo-events.php` exists in monorepo |
| **Behaviour** | `require_once` `WP_CONTENT_DIR/plugins/apollo-events/apollo-events.php`; calls `apollo_event_ensure_helpers()` |
| **Deploy evidence** | `_to_delete/root-scratch/_tmp_ae_deploy_brutal.py` targets `wp-content/mu-plugins/force-load-apollo-events.php` on host |
| **htaccess plan** | `_inventory/_current/plugins-htaccess-DROP-IN.txt` would deny `^force-load-.*` (not yet installed per plan P0-2) |

**Verdict:** **DRIFT** — operational workaround parked in wrong tree; not registry-documented MU plugin.

### 6.2 · `apollo-events-helpers-guard.php`

| Attribute | Detail |
|---|---|
| **Path** | `/workspace/apollo-events-helpers-guard.php` (2679 B) |
| **In `$mu_plugins`?** | **No** |
| **Has Plugin Name header** | Yes — "Apollo Events Helpers Guard" v1.0.1 |
| **Behaviour** | Early `require_once` of apollo-events main; `apollo_event_ensure_helpers()`; fallback `apollo_event_parse_date()`; `wp` P0 re-assert; **`template_redirect` P0 agent log** writes `apollo-events/debug-e031aa.log` |
| **Plan reference** | `plan-001_260820.md` P0-2 — confirm nothing loads it, then move into `apollo-events/` or delete |

**Verdict:** **DRIFT** — brutal diagnostic guard; writes per-request JSON logs; exposes host paths in log payload when active.

### 6.3 · Other `*-guard.php` in repo (in-scope note)

| File | Location | Note |
|---|---|---|
| `guest-home-guard.php` | `apollo-templates/includes/` | Legitimate plugin-internal guard — **not** root drift |

---

## 7 · DRIFT — `debug-*.log` inventory

| Path | Size (approx) | Session | Source |
|---|---|---|---|
| `debug-e031aa.log` | 20 KB | e031aa | Root copy — **DRIFT** |
| `apollo-events/debug-e031aa.log` | large (32k+ lines) | e031aa | Written by helpers-guard `template_redirect` hook |
| `apollo-events/debug-959e0d.log` | very large (103k+ lines) | 959e0d | Legacy probe session; `Plugin.php` debug hooks now no-op per registry E27 |
| `.cursor/debug-efc8c2.log` | — | efc8c2 | IDE scratch |
| `apollo-events/.cursor/debug-959e0d.log` | — | 959e0d | IDE scratch |
| `RecycleBin~e278.ffs_tmp/**/debug-e031aa.log` | — | e031aa | RecycleBin copies |
| `_to_delete/**/debug-e031aa.log` | — | e031aa | Staged deletion copies |

**Content risk:** `debug-e031aa.log` entries include absolute server paths (`/home1/rvall260/apollo.rio.br/wp-content/...`), template paths, and per-request telemetry. Site-root `.htaccess` blocks `wp-content/debug.log` but **not** `debug-e031aa.log` (documented in `plan-001_260820.md` P0-1/P0-2).

**Verdict:** All `debug-*.log` under `plugins/` are **DRIFT report-only** — delete/move per P0-2; deny via `plugins/.htaccess` drop-in.

---

## 8 · Gap matrix — registry vs disk (this monorepo)

| Registry MU entry | Expected path (live WP) | In plugins monorepo? |
|---|---|---|
| apollo-brain | `wp-content/mu-plugins/apollo-brain.php` | **Absent** (expected) |
| apollo-debug | `wp-content/mu-plugins/apollo-debug.php` | **Absent** (expected) |
| apollo-dev-routing | `wp-content/mu-plugins/apollo-dev-routing.php` | **Absent** (expected) |
| apollo-route-setup | `wp-content/mu-plugins/apollo-route-setup.php` | **Absent** (expected) |
| apollo-error-handler | `wp-content/mu-plugins/apollo-error-handler.php` | **Absent** (expected) |
| *(undocumented)* | `force-load-apollo-events.php` | **Present at plugins root** — DRIFT |
| *(undocumented)* | `apollo-events-helpers-guard.php` | **Present at plugins root** — DRIFT |
| *(undocumented)* | `debug-e031aa.log` | **Present at plugins root** — DRIFT |

---

## 9 · Coordinator actions (report-only — no edits made)

1. **Confirm live `mu-plugins/`** on host matches the five registry entries; `apollo-brain.php` is the boot anchor — verify version 1.2.0 and `APOLLO_REGISTRY_PATH` behaviour independently of this repo.
2. **Resolve root DRIFT** per `plan-001_260820.md` P0-2:
   - Determine whether `force-load-apollo-events.php` / `apollo-events-helpers-guard.php` are still deployed under live `mu-plugins/`; if the root copies are stale, delete from `plugins/` root.
   - Purge `debug-e031aa.log` (root + `apollo-events/`) and install `plugins/.htaccess` drop-in.
3. **Registry hygiene:** `$mu_plugins` does not document `force-load-apollo-events` — either add as explicit `status: interim` entry with sunset note, or keep out of registry and treat as ops debt until removed from production.
4. **inventory-sync:** Run `POST /apollo/v1/registry/inventory-sync` on live to populate `13-runtime.json` skeleton `must_use_plugins` with ground truth from `get_mu_plugins()`.

---

## 10 · Evidence commands (reproducible)

```bash
# Registry chapter
cat _inventory/registry/14-routing.json | jq '."$mu_plugins"'

# Absence checks
find /workspace -name 'apollo-brain.php'
find /workspace -path '*/mu-plugins/*'

# Root drift
ls -la force-load-apollo-events.php apollo-events-helpers-guard.php debug-e031aa.log

# All debug logs (depth 3)
find /workspace -maxdepth 3 -name 'debug-*.log' | sort
```

---

*End of W4 BOOT report.*
