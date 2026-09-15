# Apollo DRIFT ledger (merged)

Generated: 2026-09-15 local coordinator pass
PHP product edits: NONE
Monolith hand-edit: NONE
gen-dev-registry + verify: 22 pass / 0 fail

## Inventory mismatch
| status | items |
|---|---|
| MISSING_ON_DISK | apollo-cena, apollo-classifieds, apollo-pwa, apollo-runtime, apollo-shortcodes, apollo-suppliers |
| MISSING_IN_REG | apollo-ui |
| SKIP | apollo-waha + third-party + tooling |

## Boot / root drift (report-only)
- plugins/force-load-apollo-events.php
- plugins/apollo-events-helpers-guard.php
- plugins/debug-*.log
- REG $mu_plugins still documents force-load-* as boot path (temporary)

## Surface (W3)
- register_rest_route: 461
- wp_ajax_nopriv_: 14
- add_shortcode: 125

## Forbidden / naming (W5 triage counts)
- TRUE_VIOLATION: 67 (incl. register_post_type outside core samples e.g. apollo-adverts; enable_follow UI)
- FALSE_POSITIVE: 47
- NEEDS_REVIEW: 125
- CORE_OK: 26

## Slim
- plugins/_inventory/_worker-reports/W6-slim.draft.json (27987 bytes, 43 plugins)
- chapters/ absent — did not write chapters/_slim.draft.json

## Runtime copy
- BLOCKED until wp-content path for live WP install is confirmed
- Brain source in tree: D:\dev\_apollo.rio.br\mu-plugin\apollo-brain.php → APOLLO_REGISTRY_PATH = WP_CONTENT_DIR/apollo-registry.json

## Next tickets needing human OK
1. Patch REG intent chapters for missing-on-disk (mark removed/absent) + add apollo-ui
2. Authorize any product PHP ticket for CPT-outside-core / follow UI
3. Confirm live wp-content path before copying slim runtime JSON
## Local Sites probe (2026-09-15)
- C:\Users\User\Local Sites\a — wp-content exists, **0** apollo-* plugins, **no** apollo-registry.json
- C:\Users\User\Local Sites\fim — wp-content exists, **no** plugins dir, **no** apollo-registry.json
- Intent chapters written: chapters/01-disk-vs-plugins.json, chapters/05-forbidden-surface.json
