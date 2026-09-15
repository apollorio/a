# W4 BOOT

## REG.$mu_plugins
```json
{
  "description": "Must-Use plugins in wp-content/mu-plugins/. Loaded before regular plugins, in ALPHABETICAL filename order, and cannot be deactivated from wp-admin. This is the Apollo boot layer.",
  "$disk_scan": {
    "date": "2026-08-31",
    "path": "wp-content/mu-plugins/",
    "php_files": 16,
    "apollo_owned": 14,
    "third_party": 2,
    "non_php_debris": 2
  },
  "$load_order_note": "WordPress loads mu-plugins alphabetically; there is no dependency resolution. apollo-brain.php sorts first among the apollo-* files, which is why it can hook muplugins_loaded at -100 and still define APOLLO_REGISTRY_PATH before anything reads it. Renaming a file here silently changes boot order.",
  "$boot_sequence": [
    "1. WP loads every mu-plugin file alphabetically (apollo-brain → apollo-debug → apollo-dev-routing → apollo-error-handler → apollo-events-helpers-guard → apollo-force-open-registration → apollo-htaccess-lock → apollo-locale-pt-br → apollo-route-setup → apollo-webp-force → elementor-safe-mode → endurance-page-cache → force-load-apollo-events → force-load-apollo-login → force-load-apollo-telegram → force-load-apollo-templates)",
    "2. apollo-brain hooks muplugins_loaded @ -100 → defines APOLLO_REGISTRY_PATH, require_once apollo-core.php",
    "3. apollo-core registers its own bootstrap on plugins_loaded @ 1 (CPT/Meta/security init happens THERE, not in the brain)",
    "4. force-load-* files require their plugin main file directly, bypassing active_plugins",
    "5. apollo-route-setup runs its one-time maintenance on init @ 20",
    "6. apollo-webp-force registers three image filters; nothing runs until an image is actually processed"
  ],
  "apollo-brain": {
    "file": "mu-plugins/apollo-brain.php",
    "version": "1.2.0",
    "status": "active",
    "role": "boot",
    "namespace": "Apollo\\MUBrain",
    "hook": "muplugins_loaded priority -100",
    "loc": 305,
    "description": "THE BRAIN. Earliest Apollo code to run. Defines APOLLO_REGISTRY_PATH, validates the runtime registry JSON parses, require_once apollo-core/apollo-core.php so Core registers its bootstrap before active_plugins is processed, installs the script defer filter, and wires registry/page cache purge.",
    "constants_defined": [
      "APOLLO_REGISTRY_PATH",
      "APOLLO_BRAIN_LOADED"
    ],
    "does": [
      "APOLLO_REGISTRY_PATH := WP_CONTENT_DIR/apollo-registry.json (see $registry_path_warning below)",
      "json_decode validation of that file — logs to error_log only when WP_DEBUG_LOG, never die()s in production",
      "require_once WP_PLUGIN_DIR/apollo-core/apollo-core.php, guarded by defined('APOLLO_CORE_FILE')",
      "script_loader_tag filter: adds defer to front-end scripts, with an explicit allow-list that must stay synchronous",
      "save_post @ 999 → apollo_purge_single(permalink) + Registry::clear_cache()",
      "transition_post_status @ 999 → apollo_purge_all() + Registry::clear_cache() on first publish only",
      "plugins_loaded @ 20 → maybe_init_cache_integration()"
    ],
    "does_not": [
      "Does NOT init CPT / Meta / FieldEncryption / HMAC / SEOTextBlock / PaneMode — apollo_core_bootstrap() on plugins_loaded P1 owns all of that, so ordering is guaranteed and nothing double-inits",
      "Does NOT load the registry JSON into a constant — Apollo\\Core\\Registry + object cache own that, to avoid duplicating ~2.3 MB in RAM"
    ],
    "actions_fired": [
      "apollo/brain/loaded",
      "apollo/brain/before_apollo_core",
      "apollo/brain/after_apollo_core_file",
      "apollo/brain/bootstrap_complete",
      "apollo/brain/core_ready",
      "apollo/brain/page_cache_maybe_init"
    ],
    "filters_exposed": [
      "apollo/brain/defer_protected_handles",
      "apollo/brain/defer_protected_fragments"
    ],
    "defer_protected_handles": [
      "jquery-core",
      "jquery-migrate",
      "jquery-ui-core",
      "wp-polyfill",
      "wp-hooks",
      "wp-dom-ready",
      "wp-customize-support",
      "moment"
    ],
    "defer_protected_fragments": [
      "apollo-",
      "apollo_",
      "popper",
      "lenis",
      "gsap"
    ],
    "defer_hard_exclusions": [
      "any handle matching /^jquery/",
      "cdn.apollo.rio.br/v1(.x)/core.js — matched by regex on src",
      "is_admin()",
      "is_customize_preview()",
      "tags already carrying defer or async"
    ]
  },
  "apollo-debug": {
    "file": "mu-plugins/apollo-debug.php",
    "status": "dev-only",
    "role": "observability",
    "loc": 424,
    "guard": "WP_DEBUG || APOLLO_DEV_MODE — returns immediately otherwise, so production overhead is zero",
    "description": "Query Monitor panel for the Apollo ecosystem plus the apollo_debug_log() helper. Loads after apollo-brain (alphabetical: brain < debug).",
    "provides": [
      "apollo_debug_log()",
      "Query Monitor → Apollo panel"
    ],
    "panel_shows": [
      "Registry path, load status, plugin count",
      "Active Apollo plugins with version + status from the registry",
      "Boot timing (mu → plugins_loaded → init → rest_api_init)",
      "Whether the registry came from object cache or disk",
      "apollo_debug_log() entries collected this request"
    ],
    "$notable": "Hooks plugins_loaded @ 0 to reset display_errors=0, because the third-party wp-debugging plugin calls @ini_set('display_errors',1) at load time and apollo-core's plugins_loaded @ 1 textdomain notice would otherwise corrupt REST JSON responses."
  },
  "apollo-dev-routing": {
    "file": "mu-plugins/apollo-dev-routing.php",
    "status": "dev-only",
    "role": "observability",
    "loc": 53,
    "guard": "APOLLO_DEV_MODE — early return at file level",
    "description": "On template_redirect, records which route handler won into $GLOBALS['apollo_dev_route_handler'] and logs it. Reads apollo_login_page / apollo_home_page / apollo_feed_page query vars and $GLOBALS['apollo_current_route']."
  },
  "apollo-error-handler": {
    "file": "mu-plugins/apollo-error-handler.php",
    "version": "1.0.0",
    "status": "active",
    "role": "http",
    "namespace": "Apollo\\Core\\ErrorCompliance",
    "loc": 241,
    "hook": "template_redirect priority PHP_INT_MAX",
    "description": "Funnels 4xx/5xx through the central Apollo error pages so every plugin and WP core surface the same UX. Hooked last so each plugin still gets first chance to claim its route (e.g. apollo-login's virtual /acesso). Mirrors the Apache ErrorDocument directives in the project .htaccess.",
    "base_url": "https://apollo.rio.br/erro/",
    "supported_codes": [
      400,
      403,
      404,
      500
    ],
    "override_constant": "APOLLO_ERROR_BASE_URL",
    "$drift_fixed_2026_08_31": "14-routing described this as 'priority 999' from the docblock prose; the actual add_action calls use PHP_INT_MAX."
  },
  "apollo-events-helpers-guard": {
    "file": "mu-plugins/apollo-events-helpers-guard.php",
    "version": "1.0.1",
    "status": "active",
    "role": "shim",
    "loc": 92,
    "description": "Brutal early guarantee that apollo_event_* global helpers exist before any template runs. Force-requires apollo-events main file if not already loaded, calls apollo_event_ensure_helpers(), and defines a fallback apollo_event_parse_date() (with hardcoded pt-BR month/weekday tables) if the real one is missing.",
    "$registry_gap_closed": "Was entirely undocumented before 2026-08-31.",
    "$risk": "Declares global functions with a pt-BR date table duplicated from apollo-events. If apollo-events changes its own parse_date contract, this shim silently keeps serving the old shape whenever the plugin fails to load. Shim, not a feature — retire once apollo-events is reliably active."
  },
  "apollo-force-open-registration": {
    "file": "mu-plugins/apollo-force-open-registration.php",
    "version": "1.0.0",
    "status": "active",
    "role": "policy-override",
    "loc": 28,
    "hook": "filter option_users_can_register priority 10000",
    "description": "Forces WordPress 'Any
```

## apollo-brain.php candidates
- D:\dev\_apollo.rio.br\mu-plugin\apollo-brain.php

### headers/constants from D:\dev\_apollo.rio.br\mu-plugin\apollo-brain.php
```php
 * Description: APOLLO_REGISTRY_PATH + require Apollo Core antes dos plugins; defer seguro front; purge + entrada de cache; sem duplicar inits que o Core já faz em plugins_loaded.
 * ✓ Define cedo WP_CONTENT/apollo-registry.json como APOLLO_REGISTRY_PATH — fonte única no disco.
 * ✓ Require once do apollo-core.php para registar bootstrap em plugins_loaded:1 (*não* executa CPT/Meta/etc. aqui).
 * ✓ purge apollo_purge_* + Registry::clear_cache() em save/post status.
 * ✗ Não define o JSON inteiro como constante (evita RAM duplicada; usa Apollo\Core\Registry + object cache).
 * ✗ Não chama FieldEncryption/HMAC/SEOTextBlock/PaneMode aqui — isso é só em {@see apollo_core_bootstrap()} Apollo Core para ordem garantida sem dupla init.
 * MU-plugins separados (ex.: error handler, debug) continuam próprios ficheiros; não condicionados a WP_DEBUG.
 * THREE THINGS ARE CALLED "THE REGISTRY". They are not the same file.
 *   runtime      wp-content/apollo-registry.json   <- what the live site
 *                resolve_registry_path_early(), as APOLLO_REGISTRY_PATH.
 *                Read through Apollo\Core\Registry, never by hand.
 *   as-built     plugins/_dev-registry/            <- read-only snapshot
 *   A fourth file, plugins/_inventory/apollo-registry.json, is BUILD
 *   mu-plugin/*.php included alphabetically by WordPress
 *       -> require at file scope, BEFORE any Apollo hook exists
 *     apollo/brain/before_apollo_core   -> context: method
 *     require apollo-core.php
 *   active_plugins loop     WordPress requires every remaining plugin
 *   plugins_loaded:1        apollo_core_bootstrap()
 *   init:5                  CPTRegistry::register_fallback_cpts()
 * WHEN EACH PLUGIN BOOTS. There is no Requires Plugins: header anywhere
```
APOLLO_REGISTRY_PATH snippet: `APOLLO_REGISTRY_PATH + require Apollo Core antes dos plugins;`

## mu-plugins dirs
### D:\dev\_apollo.rio.br\plugins\apollo-core\mu-plugins
- apollo-htaccess-lock.php
- apollo-webp-force.php
### D:\dev\_apollo.rio.br\wt-p1\apollo-core\mu-plugins
- apollo-htaccess-lock.php

## plugins-root drift (report only)
- force-load-apollo-events.php: PRESENT
- apollo-events-helpers-guard.php: PRESENT
- debug-161c5c.log: PRESENT
- debug-e031aa.log: PRESENT

## conclusion
- Runtime registry path must remain wp-content/apollo-registry.json, NOT plugins/_inventory/apollo-registry.json monolith.
- force-load-* / *-guard.php / debug logs = DRIFT temporary, do not inventory as features.
- No edits made.