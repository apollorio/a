# W3 STRICT SURFACE

Coordinator=Grok · Composer 2.5 · branch `cursor/registry-all-plugins-incl-waha`

Grepped `register_rest_route`, `wp_ajax_nopriv_`, `add_shortcode` under `apollo-*` (includes **apollo-waha**).
Cross-checked `in_registry?` against `_inventory/registry/09-plugins/*.json` and `_dev-registry/{06-REST,08-SHORTCODES,12-SECURITY-SURFACE}.md`.

| metric | count |
|---|---|
| REST `register_rest_route` endpoint rows | 483 |
| AJAX `wp_ajax_nopriv_` actions | 14 |
| `add_shortcode` registrations | 90 |
| **total rows** | 587 |
| `in_registry?=YES` | 461 |
| `in_registry?=DEV_REG` | 85 |
| `in_registry?=NO` | 38 |
| `in_registry?=DYNAMIC` | 3 |

## apollo-waha (included this run)

| endpoint | methods | permission | file:line | in_registry? |
|---|---|---|---|---|
| `apollo/v1/wa/webhook` | POST | verify | `apollo-waha/inc/Webhook/class-webhook.php:53` | YES |
| `apollo_wa_join` | N/A | N/A | `apollo-waha/public/class-public.php:19` | YES |

> `_dev-registry` excludes apollo-waha by interlock (`data/plugins.yaml:excluded`); inventory chapter `09-plugins/apollo-waha.json` is authoritative.

## Gaps (`in_registry?=NO`, 38 rows)

| endpoint | methods | permission | file:line |
|---|---|---|---|
| `apollo/v1/safety/signals` | GET | can | `apollo-adverts/src/API/SafetyController.php:42` |
| `apollo/v1/safety/vouch` | POST | can | `apollo-adverts/src/API/SafetyController.php:55` |
| `apollo/v1/safety/vouch/confirm` | POST | can | `apollo-adverts/src/API/SafetyController.php:70` |
| `apollo/v1/rest_base/(?P<id>\d+)/fragmento` | GET | __return_true | `apollo-core/includes/surface-contract.php:266` |
| `UNRESOLVED:ApolloRoute::EVENTS` | UNKNOWN | MISSING | `apollo-core/src/Config/ApolloRoute.php:10` |
| `apollo/v1/(?P<id>\d+)` | PUT | check_logged_in | `apollo-docs/src/API/FoldersController.php:40` |
| `apollo/v1/(?P<id>\d+)` | DELETE | check_admin | `apollo-docs/src/API/FoldersController.php:51` |
| `apollo/v1/iframe/(?P<slug>[a-z0-9-]+)` | GET | __return_true | `apollo-events/includes/iframe-proxy.php:88` |
| `apollo/v1/eventos/(?P<id>\d+)/fragmento` | GET | __return_true | `apollo-events/includes/render-single.php:1055` |
| `apollo/v1/eventos/importar-url/preview` | POST | can_import | `apollo-events/src/Import/UrlImportController.php:66` |
| `apollo/v1/eventos/importar-url` | POST | can_import | `apollo-events/src/Import/UrlImportController.php:77` |
| `apollo/v1/proximos` | GET | __return_true | `apollo-loc/src/API/Router.php:41` |
| `UNRESOLVED:$rb` | GET | __return_true | `apollo-loc/src/API/Router.php:58` |
| `UNRESOLVED:$rb` | POST | is_logged_in | `apollo-loc/src/API/Router.php:58` |
| `UNRESOLVED:$rb` | GET | __return_true | `apollo-loc/src/API/Router.php:58` |
| `UNRESOLVED:$rb` | PUT,PATCH | is_admin | `apollo-loc/src/API/Router.php:58` |
| `UNRESOLVED:$rb` | DELETE | is_admin | `apollo-loc/src/API/Router.php:58` |
| `apollo/v1/(?P<id>[\d]+)` | GET | __return_true | `apollo-loc/src/API/Router.php:90` |
| `apollo/v1/(?P<id>[\d]+)` | PUT,PATCH | is_admin | `apollo-loc/src/API/Router.php:90` |
| `apollo/v1/(?P<id>[\d]+)` | DELETE | is_admin | `apollo-loc/src/API/Router.php:90` |
| `apollo/v1/ical/(?P<id>\d+)` | GET | ical_permission | `apollo-scheduler/src/API/ICalController.php:23` |
| `apollo/v1/nucleo/(?P<nucleo_id>\d+)/catalog` | GET | catalog_permission | `apollo-scheduler/src/API/ManagerController.php:26` |
| `apollo/v1/agents/assign` | POST | logged_in_permission | `apollo-scheduler/src/API/ManagerController.php:38` |
| `apollo/v1/availability/block` | POST | logged_in_permission | `apollo-scheduler/src/API/ManagerController.php:56` |
| `apollo/v1/(?P<id>\d+)/placement` | POST | sign_permission | `apollo-sign/src/API/SignController.php:107` |
| `apollo/v1/(?P<id>\d+)/placement` | GET | sign_permission | `apollo-sign/src/API/SignController.php:155` |
| `apollo/v1/(?P<id>\d+)` | GET | create_permission | `apollo-sign/src/API/SignController.php:53` |
| `apollo/v1/(?P<id>\d+)/sign` | POST | sign_permission | `apollo-sign/src/API/SignController.php:71` |
| `apollo/v1/(?P<id>\d+)/audit` | GET | audit_permission | `apollo-sign/src/API/SignController.php:89` |
| `apollo/v1/(?P<hash>[a-f0-9]{64})` | GET | __return_true | `apollo-sign/src/API/VerifyController.php:46` |
| `apollo/v1/metrics` | GET | is_admin | `apollo-statistics/src/API/StatsController.php:33` |
| `apollo/v1/metric/(?P<slug>[a-z0-9_-]+)` | GET | is_admin | `apollo-statistics/src/API/StatsController.php:42` |
| `apollo/v1/metric/(?P<slug>[a-z0-9_-]+)/toggle` | PUT,PATCH | is_admin | `apollo-statistics/src/API/StatsController.php:52` |
| `apollo/v1/dashboard` | GET | is_admin | `apollo-statistics/src/API/StatsController.php:67` |
| `apollo/v1/profile/(?P<user_id>\d+)` | GET | profile_permission | `apollo-statistics/src/API/StatsController.php:77` |
| `UNRESOLVED:$this->route` | $this->method | [$this, 'checkPermission'] | `apollo-telegram/src/API/BaseEndpoint.php:51` |
| `apollo/v1/radar/stats` | GET | __return_true | `apollo-templates/examples/user-radar-examples.php:208` |
| `apollo/v1/radar/top` | GET | __return_true | `apollo-templates/examples/user-radar-examples.php:218` |


## Column legend

| column | meaning |
|---|---|
| endpoint | REST `namespace+path`, AJAX action name, or shortcode tag |
| methods | HTTP verb(s), `POST` for AJAX, or `N/A` for shortcodes |
| permission | `permission_callback` summary, `nopriv`, or `N/A` |
| file:line | source registration site |
| in_registry? | `YES` = `_inventory/registry`; `DEV_REG` = `_dev-registry` only; `NO` = neither; `DYNAMIC` = variable tag |

TSV: `W3-strict.tsv` (587 rows)

_No PHP edits._
