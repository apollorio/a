# W4 STRICT BOOT

| Field | Value |
|---|---|
| Coordinator | Grok |
| Worker | Composer 2.5 |
| Branch | `cursor/registry-all-plugins-incl-waha` |
| Commit | `d04902f` |
| PHP product edits | **NONE** |
| Scope | Boot-layer recon + drift ledger only |

---

## 1. Brain / MU-plugins — absence in this repo

**Confirmed:** the Apollo boot layer (`wp-content/mu-plugins/`) and `apollo-brain.php` are **not** part of this plugins tree.

| Probe | Result |
|---|---|
| `find . -name 'apollo-brain.php'` | **0 hits** |
| `wp-content/mu-plugins/` in repo | **absent** |
| `mu-plugins/` at plugins root | **absent** |
| `apollo-core/mu-plugins/` | **present** — 2 install-source stubs only (`apollo-htaccess-lock.php`, `apollo-webp-force.php`); copied to `wp-content/mu-plugins/` by `apollo_core_*_install_mu_plugin()` on `plugins_loaded @ 0`, not the live boot layer |

The registry documents the real boot layer under **`wp-content/mu-plugins/` on the WordPress host**, outside this GitHub repo. Chapter **22-mu-plugins** is the SSOT; chapter **14-routing** explicitly moved `$mu_plugins` out on 2026-08-31.

---

## 2. REG `$mu_plugins` (chapter `22-mu-plugins.json`)

**Source:** `_inventory/registry/22-mu-plugins.json`  
**Disk scan (registry):** 2026-08-31 · `wp-content/mu-plugins/` · 16 PHP · 14 Apollo-owned · 2 third-party · 2 non-PHP debris

### Load order (alphabetical — WordPress has no dependency resolution)

```
apollo-brain → apollo-debug → apollo-dev-routing → apollo-error-handler
→ apollo-events-helpers-guard → apollo-force-open-registration → apollo-htaccess-lock
→ apollo-locale-pt-br → apollo-route-setup → apollo-webp-force
→ elementor-safe-mode → endurance-page-cache
→ force-load-apollo-events → force-load-apollo-login → force-load-apollo-telegram
→ force-load-apollo-templates
```

### Boot sequence (registry)

1. WP loads every MU file alphabetically.
2. `apollo-brain` hooks `muplugins_loaded @ -100` → defines `APOLLO_REGISTRY_PATH`, `require_once` `apollo-core.php`.
3. `apollo-core` registers bootstrap on `plugins_loaded @ 1` (CPT/Meta/security — **not** in brain).
4. `force-load-*` require plugin mains directly, bypassing `active_plugins`.
5. `apollo-route-setup` one-time maintenance on `init @ 20`.
6. `apollo-webp-force` registers image filters (lazy until an image is processed).

### Documented MU entries (15 PHP + 2 third-party)

| Key | File | Status | Role |
|---|---|---|---|
| `apollo-brain` | `mu-plugins/apollo-brain.php` | active | boot — defines `APOLLO_REGISTRY_PATH`, loads core |
| `apollo-debug` | `mu-plugins/apollo-debug.php` | dev-only | observability / Query Monitor panel |
| `apollo-dev-routing` | `mu-plugins/apollo-dev-routing.php` | dev-only | route-handler probe |
| `apollo-error-handler` | `mu-plugins/apollo-error-handler.php` | active | 4xx/5xx funnel (`template_redirect` `PHP_INT_MAX`) |
| `apollo-events-helpers-guard` | `mu-plugins/apollo-events-helpers-guard.php` | active | shim — `apollo_event_*` globals |
| `apollo-force-open-registration` | `mu-plugins/apollo-force-open-registration.php` | active | policy override `users_can_register` |
| `apollo-htaccess-lock` | `mu-plugins/apollo-htaccess-lock.php` | active | `.htaccess` restore/lock (source in `apollo-core`) |
| `apollo-locale-pt-br` | `mu-plugins/apollo-locale-pt-br.php` | active | forces `pt_BR` |
| `apollo-route-setup` | `mu-plugins/apollo-route-setup.php` | active | one-time route/slug maintenance |
| `apollo-webp-force` | `mu-plugins/apollo-webp-force.php` | active | WebP image pipeline (source in `apollo-core/mu-plugins/`) |
| `force-load-apollo-events` | `mu-plugins/force-load-apollo-events.php` | active | force-load → `apollo-events` |
| `force-load-apollo-login` | `mu-plugins/force-load-apollo-login.php` | active | force-load → `apollo-login` |
| `force-load-apollo-templates` | `mu-plugins/force-load-apollo-templates.php` | active | force-load → `apollo-templates` |
| `force-load-apollo-telegram` | `mu-plugins/force-load-apollo-telegram.php` | active | force-load → `apollo-telegram` (no idempotency guard — registry flags risk) |
| `$third_party_mu.elementor-safe-mode` | `mu-plugins/elementor-safe-mode.php` | dormant | Elementor safe mode |
| `$third_party_mu.endurance-page-cache` | `mu-plugins/endurance-page-cache.php` | active | host page cache |

**Debris (registry):** `debug-1098c0.log` (delete), `sync.ffs_db` (RealTimeSync tooling).

**Chapter 14-routing** retains only `$routing_canonical` + `$third_party_plugins`; `$mu_plugins_moved` pointer → chapter 22.

---

## 3. `APOLLO_REGISTRY_PATH` — intended runtime target

**Confirmed:** runtime registry is **`WP_CONTENT_DIR/apollo-registry.json`**, **not** the inventory monolith.

| Layer | Path | Read at request time? |
|---|---|---|
| **Runtime** | `wp-content/apollo-registry.json` | **YES** — via `APOLLO_REGISTRY_PATH` / `Registry::resolve_registry_file_path()` |
| **Documentation / build** | `plugins/_inventory/apollo-registry.json` | **NO** — chaptered SSOT monolith; `build.js --write` emits here + mirror only |
| **Deploy** | manual copy monolith → runtime | `build.js` logs `runtime target (deploy step, not written here): wp-content/apollo-registry.json` |

### Code + registry alignment

- `apollo-core/config/constants.php` — `'APOLLO_REGISTRY_PATH' => WP_CONTENT_DIR . '/apollo-registry.json'`
- `apollo-core/src/Core/Registry.php` — prefers `APOLLO_REGISTRY_PATH` constant, else `WP_CONTENT_DIR . '/apollo-registry.json'`
- `22-mu-plugins.json` → `apollo-brain` → `APOLLO_REGISTRY_PATH := WP_CONTENT_DIR/apollo-registry.json`
- `02-header.json` → `apollo_registry_runtime_file` — same path, constant set by `apollo-brain`
- `00-registry-map.json` → `$runtime_path: "wp-content/apollo-registry.json"`

`$registry_path_warning` in chapter 22 documents the naming collision between the two `apollo-registry.json` files and that they can silently diverge until a deploy step runs.

---

## 4. Plugins-root DRIFT (report-only — do not inventory as features)

Canonical locations are under **`wp-content/mu-plugins/`** on the host. Copies sitting at the **plugins repo root** are temporary drift.

| Pattern | Plugins root (this repo) | Canonical (registry) | Verdict |
|---|---|---|---|
| `force-load-*` | `force-load-apollo-events.php` **PRESENT** | `mu-plugins/force-load-apollo-events.php` | **DRIFT** — stray copy; not boot layer here |
| `*-guard.php` | `apollo-events-helpers-guard.php` **PRESENT** (v1.0.2) | `mu-plugins/apollo-events-helpers-guard.php` (v1.0.1 in REG) | **DRIFT** — stray copy + version skew vs registry |
| `debug-*.log` | **none at plugins root** | should not exist in repo | **CLEAN at root** |

### `debug-*.log` elsewhere (informational, not plugins-root)

| Path | Note |
|---|---|
| `apollo-events/debug-959e0d.log` | leftover debug beacon |
| `apollo-events/.cursor/debug-959e0d.log` | IDE artefact |
| `.cursor/debug-efc8c2.log` | IDE artefact |
| `_to_delete/p0-d08/debug-e031aa.log` | quarantine |
| `_to_delete/root-scratch/debug-e031aa.log` | quarantine |

**Action:** report only. No deletes, no moves, no product PHP edits in this pass.

---

## 5. Conclusion

| Assertion | Status |
|---|---|
| Brain + live MU boot layer live outside this plugins repo | **CONFIRMED** |
| `$mu_plugins` SSOT = chapter `22-mu-plugins.json` | **READ** |
| `APOLLO_REGISTRY_PATH` = `WP_CONTENT_DIR/apollo-registry.json` | **CONFIRMED** — not `_inventory/apollo-registry.json` monolith |
| Root `force-load-*` / `*-guard.php` | **DRIFT** (2 files) |
| Root `debug-*.log` | **absent** (subtree debris noted) |
| Product PHP edits | **NONE** |

---

*Generated: 2026-09-15 · W4 strict boot · report-only drift ledger*
