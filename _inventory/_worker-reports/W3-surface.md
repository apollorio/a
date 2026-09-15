# W3 SURFACE Report

**Worker:** W3 SURFACE (Composer 2.5)  
**Generated:** 2026-09-15  
**Scope:** `apollo-*` plugins (excludes `apollo-waha`)  
**Sources:** live PHP scan + `_inventory/registry/09-plugins/*.json`  
**Reference docs:** `_inventory/APOLLO_ROUTES_INVENTORY.md`, `_inventory/registry/11-security.json`  
**Note:** chapter files `06-REST` / `08-SHORTCODES` / `12-SECURITY` not present on disk (registry uses `11-security.json`, `14-routing.json`)

## Summary

| Surface | Count | In registry | Missing from registry |
|---------|------:|------------:|----------------------:|
| REST (`register_rest_route`) | 390 | 300 | 90 |
| AJAX (`wp_ajax_nopriv_`) | 14 | 14 | 0 |
| Shortcodes (`add_shortcode`) | 90 | 86 | 4 |
| **Total rows** | **494** | | |

**Plugins scanned:** 41

## TSV columns

`endpoint | methods | permission | file:line | in_registry?`

Full machine-readable output: [W3-surface.tsv](./W3-surface.tsv)

## REST — not in registry (90)

- `apollo/v1/{rest_base}/(?P<id>\d+)/fragmento` GET — apollo-core/includes/surface-contract.php:66
- `apollo/v1/$this->route` $this->method — apollo-telegram/src/API/BaseEndpoint.php:33
- `apollo/v1/classifieds` GET,POST — apollo-adverts/src/API/ClassifiedsController.php:16
- `apollo/v1/classifieds/(?P<id>[\d]+)` DELETE,GET,PATCH,POST,PUT — apollo-adverts/src/API/ClassifiedsController.php:40
- `apollo/v1/classifieds/my` GET — apollo-adverts/src/API/ClassifiedsController.php:69
- `apollo/v1/classifieds/search` GET — apollo-adverts/src/API/SearchController.php:19
- `apollo/v1/coauthors/(?P<post_id>[\d]+)` GET,PATCH,POST,PUT — apollo-coauthor/src/API/CoauthorController.php:19
- `apollo/v1/coauthors/search` GET — apollo-coauthor/src/API/CoauthorController.php:91
- `apollo/v1/depoimentos` GET,POST — apollo-comment/src/API/DepoimentoController.php:19
- `apollo/v1/depoimentos/(?P<id>[\d]+)` DELETE,GET,PATCH,POST,PUT — apollo-comment/src/API/DepoimentoController.php:68
- `apollo/v1/dj/session` POST — apollo-dj-sync/src/API/DJPermissionsController.php:11
- `apollo/v1/docs` GET — apollo-docs/src/API/DocsController.php:18
- `apollo/v1/docs` POST — apollo-docs/src/API/DocsController.php:28
- `apollo/v1/docs/(?P<id>\d+)` GET — apollo-docs/src/API/DocsController.php:39
- `apollo/v1/docs/(?P<id>\d+)` PUT — apollo-docs/src/API/DocsController.php:49
- `apollo/v1/docs/(?P<id>\d+)` DELETE — apollo-docs/src/API/DocsController.php:58
- `apollo/v1/docs/(?P<id>\d+)/download` GET — apollo-docs/src/API/DocsController.php:67
- `apollo/v1/docs/(?P<id>\d+)/finalize` POST — apollo-docs/src/API/DocsController.php:94
- `apollo/v1/docs/(?P<id>\d+)/lock` POST — apollo-docs/src/API/DocsController.php:84
- `apollo/v1/docs/(?P<id>\d+)/upload` POST — apollo-docs/src/API/DocsController.php:105
- `apollo/v1/docs/(?P<id>\d+)/versions` GET — apollo-docs/src/API/DocsController.php:77
- `apollo/v1/docs/folders` GET — apollo-docs/src/API/FoldersController.php:14
- `apollo/v1/docs/folders` POST — apollo-docs/src/API/FoldersController.php:23
- `apollo/v1/docs/folders/(?P<id>\d+)` PUT — apollo-docs/src/API/FoldersController.php:33
- `apollo/v1/docs/folders/(?P<id>\d+)` DELETE — apollo-docs/src/API/FoldersController.php:42
- `apollo/v1/docs/upload` POST — apollo-docs/src/API/DocsController.php:114
- `apollo/v1/eventos/(?P<id>\d+)/fragmento` GET — apollo-events/includes/render-single.php:568
- `apollo/v1/eventos/importar-url` POST — apollo-events/src/Import/UrlImportController.php:48
- `apollo/v1/eventos/importar-url/preview` POST — apollo-events/src/Import/UrlImportController.php:30
- `apollo/v1/favs` GET,POST — apollo-fav/includes/class-rest-controller.php:9
- `apollo/v1/favs/(?P<post_id>\d+)` DELETE — apollo-fav/includes/class-rest-controller.php:65
- `apollo/v1/favs/check/(?P<post_id>\d+)` GET — apollo-fav/includes/class-rest-controller.php:116
- `apollo/v1/favs/count/(?P<post_id>\d+)` GET — apollo-fav/includes/class-rest-controller.php:99
- `apollo/v1/favs/toggle/(?P<post_id>\d+)` POST — apollo-fav/includes/class-rest-controller.php:80
- `apollo/v1/iframe/(?P<slug>[a-z0-9-]+)` GET — apollo-events/includes/iframe-proxy.php:17
- `apollo/v1/local` GET,POST — apollo-loc/src/API/LocalsController.php:12
- `apollo/v1/local` GET,POST — apollo-loc/src/API/Router.php:50
- `apollo/v1/local/(?P<id>[\d]+)` DELETE,GET,PATCH,POST,PUT — apollo-loc/src/API/LocalsController.php:55
- `apollo/v1/local/(?P<id>[\d]+)` DELETE,GET,PATCH,POST,PUT — apollo-loc/src/API/Router.php:71
- `apollo/v1/local/proximos` GET — apollo-loc/src/API/LocalsController.php:84
- `apollo/v1/local/proximos` GET — apollo-loc/src/API/Router.php:30
- `apollo/v1/pane/section/{$safe_slug}` GET — apollo-pane-engine/includes/section-renderer.php:59
- `apollo/v1/profile-stats/(?P<user_id>\d+)` GET — apollo-statistics/src/API/ProfileController.php:19
- `apollo/v1/profile-stats/visibility` PATCH,POST,PUT — apollo-statistics/src/API/ProfileController.php:37
- `apollo/v1/radar/stats` GET — apollo-templates/examples/user-radar-examples.php:134
- `apollo/v1/radar/top` GET — apollo-templates/examples/user-radar-examples.php:139
- `apollo/v1/radio/now` GET — apollo-radio/src/API/RadioController.php:31
- `apollo/v1/radio/playlist` GET — apollo-radio/src/API/RadioController.php:38
- `apollo/v1/radio/status` GET — apollo-radio/src/API/RadioController.php:21
- `apollo/v1/radio/stream` GET — apollo-radio/src/API/RadioController.php:55
- `apollo/v1/safety/signals` GET — apollo-adverts/src/API/SafetyController.php:10
- `apollo/v1/safety/vouch` POST — apollo-adverts/src/API/SafetyController.php:26
- `apollo/v1/safety/vouch/confirm` POST — apollo-adverts/src/API/SafetyController.php:48
- `apollo/v1/scheduler/agents/assign` POST — apollo-scheduler/src/API/ManagerController.php:32
- `apollo/v1/scheduler/availability` GET — apollo-scheduler/src/API/AvailabilityController.php:21
- `apollo/v1/scheduler/availability/block` POST — apollo-scheduler/src/API/ManagerController.php:49
- `apollo/v1/scheduler/availability/grid` GET — apollo-scheduler/src/API/AvailabilityController.php:32
- `apollo/v1/scheduler/book` POST — apollo-scheduler/src/API/BookingController.php:24
- `apollo/v1/scheduler/ical/(?P<id>\d+)` GET — apollo-scheduler/src/API/ICalController.php:20
- `apollo/v1/scheduler/nucleo/(?P<nucleo_id>\d+)/catalog` GET — apollo-scheduler/src/API/ManagerController.php:23
- `apollo/v1/scheduler/reschedule/(?P<id>\d+)` PATCH,POST,PUT — apollo-scheduler/src/API/BookingController.php:43
- `apollo/v1/search/{$type}` GET — apollo-core/src/API/SearchController.php:20
- `apollo/v1/sheets` GET,POST — apollo-sheets/src/API/SheetsController.php:22
- `apollo/v1/sheets/(?P<id>[\w]+)` DELETE,GET,PATCH,POST,PUT — apollo-sheets/src/API/SheetsController.php:52
- `apollo/v1/sheets/(?P<id>[\w]+)/copy` POST — apollo-sheets/src/API/SheetsController.php:89
- `apollo/v1/sheets/(?P<id>[\w]+)/export` GET — apollo-sheets/src/API/SheetsController.php:126
- `apollo/v1/sheets/(?P<id>[\w]+)/preview` GET — apollo-sheets/src/API/SheetsController.php:152
- `apollo/v1/sheets/import` POST — apollo-sheets/src/API/SheetsController.php:110
- `apollo/v1/shortcodes/(?P<tag>[a-z_]+)` GET — apollo-core/src/API/ShortcodesController.php:49
- `apollo/v1/signatures` POST — apollo-sign/src/API/SignController.php:21
- `apollo/v1/signatures/(?P<id>\d+)` GET — apollo-sign/src/API/SignController.php:41
- `apollo/v1/signatures/(?P<id>\d+)/audit` GET — apollo-sign/src/API/SignController.php:75
- `apollo/v1/signatures/(?P<id>\d+)/placement` GET — apollo-sign/src/API/SignController.php:138
- `apollo/v1/signatures/(?P<id>\d+)/placement` POST — apollo-sign/src/API/SignController.php:91
- `apollo/v1/signatures/(?P<id>\d+)/sign` POST — apollo-sign/src/API/SignController.php:58
- `apollo/v1/signatures/doc/(?P<doc_id>[\d]+)` GET — apollo-sign/src/API/RequestController.php:52
- `apollo/v1/signatures/request` POST — apollo-sign/src/API/RequestController.php:26
- `apollo/v1/signatures/users` GET — apollo-sign/src/API/RequestController.php:41
- `apollo/v1/signatures/verify/(?P<hash>[a-f0-9]{64})` GET — apollo-sign/src/API/VerifyController.php:20
- `apollo/v1/stats/dashboard` GET — apollo-statistics/src/API/StatsController.php:54
- … and 10 more (see TSV)

## AJAX nopriv — not in registry (0)

_none_

## Shortcodes — not in registry (4)

- `[apollo_adverts_chat_button]` — apollo-adverts/includes/integrations.php:375
- `[apollo_event_single]` — apollo-events/includes/render-single.php:1039
- `[apollo_user_badge]` — apollo-templates/examples/user-radar-examples.php:310
- `[apollo_user_ranking]` — apollo-templates/examples/user-radar-examples.php:332

## Notes

- Registry REST paths matched on normalized route suffix (namespace stripped, regex escapes unified).
- `permission` = raw `permission_callback` expression (or `nopriv`/`public` for AJAX/shortcodes).
- Dynamic routes left unresolved when `rest_base`/`base` cannot be inferred from the same file.
- Example-only surfaces: `apollo-templates/examples/user-radar-examples.php`.
