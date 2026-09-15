# Known open defects

Carried from the evidence root `D:/dev/_cos/verify/`. Each was verified
against source and left unrepaired for a stated reason. This file is
hand-maintained — it records human conclusions, not scanner output.

| # | Defect | Evidence | Why still open |
|---|---|---|---|
| 1 | `track.map_meta_cap` — core registers effective `false`, owner declares `true` | `CPT-ARGUMENT-DRIFT.md` | `apollo-core/config/cpts.php` is a protected dirty file; no isolated rollback exists |
| 2 | `APOLLO_TELEGRAM/v1` non-conformant REST namespace | `doctrine-audit.js` (the one open violation) | 6 sites / 5 files, and `apollo-telegram.php:251` sends the literal string to Telegram `setWebhook` on a daily cron. A partial rename silently drops inbound messages for up to 24 h |
| 3 | `classified.has_archive` — fallback-only latent drift | `CPT-ARGUMENT-DRIFT.md` | only reachable if the owner registration ever wins the race |
| 4 | `$GLOBALS['apollo_current_route']` written with no set-once guard | `RUNTIME-CONTEXT-OWNERSHIP-VERIFY.md` | CTO decision: preserve the legacy route global; BRAIN-01 has zero external consumers |
| 5 | `apollo-hub` calls `register_post_meta()` directly at `init:10`, never hooks `apollo_core_register_meta` | `CPT-REST-CELL-OWNERSHIP-VERIFY.md` | bypasses meta governance; repair not authorised |
| 6 | `/tracks` archive is an unclaimed domain surface | `CPT-REST-CELL-OWNERSHIP-VERIFY.md` | no owner assigned |
| 7 | Two Plus-API consumers unguarded — fatal if `apollo-templates` is absent | `UI-RENDERING-OWNERSHIP-VERIFY.md` | `apollo-events/styles/base/create-event.php:360,398` and `dashboard-event.php:105,143` |
| 8 | `apollo_purge_single()` / `apollo_purge_all()` called but never defined anywhere | `CACHE-BOUNDARY-VERIFY.md` | dead branches inside a `function_exists()` guard, so inert — but the cache integration they imply does not exist |
| 9 | `apollo/brain/page_cache_maybe_init` has zero listeners; `Apollo\Core\Cache` does not exist | `CACHE-BOUNDARY-VERIFY.md` | Phase 8 boundary is unmapped; hook is dormant by design |

## Incidental finding from building this registry

PHPCS report JSON under `apollo-adverts/phpcs-logs/` is **world-readable**:

```
GET /wp-content/plugins/apollo-adverts/phpcs-logs/FINAL_VIPGO_20260212_100658.json -> HTTP 200
```

`plugins/.htaccess` rule 1 denies `yml`, `yaml`, `log`, `ini` and `md`, but
not `json`. These reports embed absolute server paths and a file-by-file map
of the codebase. Not repaired here: changing `plugins/.htaccess` is a live
security-file edit and belongs in its own reviewed pass. Two candidate fixes
— add `json` to rule 1 (broad; verify no plugin ships a runtime-fetched
JSON asset first), or add a `phpcs-logs` path rule (narrow, safe).

---

_Generated 2026-09-15T05:29:41.847Z from source at `D:/dev/_apollo.rio.br`. Regenerate: `node D:/dev/_cos/verify/gen-dev-registry.js`._
