# Apollo Deep Error Investigation (source-accurate)

Generated: **2026-07-20**
**Excluded:** `apollo-login`, `apollo-telegram` (working / fine per user)
**Method:** re-parse live `register_rest_route` multi-method configs + permission method bodies
**Does not modify** `apollo-registry.json` — companion to `apollo-registry-2.json`

## Counts

| Severity | Count |
|----------|------:|
| CRITICAL | 0 |
| HIGH | 6 |
| MEDIUM | 66 |
| LOW | 8 |
| INFO | 4 |

### Categories

- **PUBLIC_READ_REVIEW**: 46
- **PERM_REVIEW**: 12
- **ABSPATH_COVERAGE**: 10
- **SENSITIVE_PUBLIC_READ**: 6
- **LOW_PREPARE_RATIO**: 4
- **INTENTIONAL_PUBLIC_WRITE**: 4
- **EXAMPLE_CODE_ROUTE**: 2

## P0 — CRITICAL public WRITE (must fix)

_No true public-write routes after multi-method re-parse._

> Note: earlier registry-2 “CRITICAL” rows that mixed `GET+POST` on one line were **false positives** when POST used `can_edit` / `create_item_permissions_check` in a separate method array.
## P1 — HIGH sensitive public reads

- **apollo-core** `GET` `/pane-mode/status` — SENSITIVE_PUBLIC_READ: Public read of potentially sensitive/internal data
  - `src/API/PaneModeController.php` · perm=__return_true
- **apollo-dashboard** `GET` `/dashboard/widgets` — SENSITIVE_PUBLIC_READ: Public read of potentially sensitive/internal data
  - `includes/class-plugin.php` · perm=__return_true
- **apollo-users** `GET` `/users/radar` — SENSITIVE_PUBLIC_READ: Public read of potentially sensitive/internal data
  - `src/API/UsersController.php` · perm=__return_true
- **apollo-users** `GET` `/users/(?P<username>[a-zA-Z0-9_-]+)` — SENSITIVE_PUBLIC_READ: Public read of potentially sensitive/internal data
  - `src/API/UsersController.php` · perm=__return_true
- **apollo-users** `GET` `/users/(?P<id>\d+)` — SENSITIVE_PUBLIC_READ: Public read of potentially sensitive/internal data
  - `src/API/UsersController.php` · perm=__return_true
- **apollo-users** `GET` `/profile/(?P<username>[a-zA-Z0-9_-]+)` — SENSITIVE_PUBLIC_READ: Public read of potentially sensitive/internal data
  - `src/API/UsersController.php` · perm=__return_true

## P2 — MEDIUM

- **apollo-adverts** `GET` `/` — Public GET — confirm no private fields in response payload
- **apollo-adverts** `GET` `/` — Public GET — confirm no private fields in response payload
- **apollo-adverts** `GET` `/` — Public GET — confirm no private fields in response payload
- **apollo-comment** `GET` `/` — Public GET — confirm no private fields in response payload
- **apollo-comment** `GET` `/` — Public GET — confirm no private fields in response payload
- **apollo-core** `GET` `/` — Public GET — confirm no private fields in response payload
- **apollo-core** `GET` `/` — Public GET — confirm no private fields in response payload
- **apollo-dj-sync** ABSPATH_COVERAGE — ABSPATH coverage 80%
- **apollo-djs** `GET` `/djs` — Public GET — confirm no private fields in response payload
- **apollo-djs** `GET` `/djs/(?P<id>\d+)` — Public GET — confirm no private fields in response payload
- **apollo-djs** `GET` `/djs/(?P<id>\d+)/eventos` — Public GET — confirm no private fields in response payload
- **apollo-djs** `GET` `/djs/por-som/(?P<sound>[a-zA-Z0-9_-]+)` — Public GET — confirm no private fields in response payload
- **apollo-djs** `GET` `/djs/buscar` — Public GET — confirm no private fields in response payload
- **apollo-docs** LOW_PREPARE_RATIO — prepare_ratio=0.2 on 2709 LOC — review raw SQL
- **apollo-email** `GET` `/email/track/open/(?P<token>[a-zA-Z0-9]+)` — Public GET — confirm no private fields in response payload
- **apollo-email** `GET` `/email/track/click/(?P<token>[a-zA-Z0-9]+)` — Public GET — confirm no private fields in response payload
- **apollo-email** ABSPATH_COVERAGE — ABSPATH coverage 76%
- **apollo-events** `GET` `/eventos` — Public GET — confirm no private fields in response payload
- **apollo-events** `GET` `/eventos/(?P<id>\d+)` — Public GET — confirm no private fields in response payload
- **apollo-events** `GET` `/eventos/por-data/(?P<date>[\d-]+)` — Public GET — confirm no private fields in response payload
- **apollo-events** `GET` `/eventos/por-local/(?P<loc_id>\d+)` — Public GET — confirm no private fields in response payload
- **apollo-events** `GET` `/eventos/por-dj/(?P<dj_id>\d+)` — Public GET — confirm no private fields in response payload
- **apollo-events** `GET` `/eventos/(?P<id>\d+)/djs` — Public GET — confirm no private fields in response payload
- **apollo-events** `GET` `/eventos/buscar` — Public GET — confirm no private fields in response payload
- **apollo-events** `GET` `/eventos/(?P<id>\d+)/estatisticas` — Public GET — confirm no private fields in response payload
- **apollo-events** `GET` `/cena-rio/agenda` — Permission binding needs manual review
- **apollo-events** `POST` `/cena-rio/enviar` — Permission binding needs manual review
- **apollo-events** `POST` `/cena-rio/confirmar/(?P<id>\d+)` — Permission binding needs manual review
- **apollo-events** `POST` `/cena-rio/cancelar/(?P<id>\d+)` — Permission binding needs manual review
- **apollo-fav** `GET` `/` — Public GET — confirm no private fields in response payload
- **apollo-fav** LOW_PREPARE_RATIO — prepare_ratio=0.42 on 3988 LOC — review raw SQL
- **apollo-groups** `GET` `/groups` — Public GET — confirm no private fields in response payload
- **apollo-groups** `GET` `/groups/(?P<id>\d+)` — Public GET — confirm no private fields in response payload
- **apollo-groups** `GET` `/groups/comunas` — Public GET — confirm no private fields in response payload
- **apollo-groups** `GET` `/groups/(?P<id>\d+)/feed` — Public GET — confirm no private fields in response payload
- **apollo-hub** `GET` `/hubs` — Public GET — confirm no private fields in response payload
- **apollo-hub** `GET` `/hubs/(?P<username>[a-zA-Z0-9._-]+)` — Public GET — confirm no private fields in response payload
- **apollo-hub** `GET` `/hubs/(?P<username>[a-zA-Z0-9._-]+)/links` — Public GET — confirm no private fields in response payload
- **apollo-hub** `GET` `/hubs/(?P<username>[a-zA-Z0-9._-]+)/blocks` — Public GET — confirm no private fields in response payload
- **apollo-hub** `GET` `/hubs/(?P<username>[a-zA-Z0-9._-]+)/share/(?P<post_id>\d+)` — Public GET — confirm no private fields in response payload
- **apollo-hub** LOW_PREPARE_RATIO — prepare_ratio=0.25 on 4970 LOC — review raw SQL
- **apollo-loc** `GET` `/` — Public GET — confirm no private fields in response payload
- **apollo-loc** `GET` `/` — Public GET — confirm no private fields in response payload
- **apollo-loc** `GET` `/` — Public GET — confirm no private fields in response payload
- **apollo-mod** LOW_PREPARE_RATIO — prepare_ratio=0.43 on 959 LOC — review raw SQL
- **apollo-pane-engine** ABSPATH_COVERAGE — ABSPATH coverage 71%
- **apollo-radio** `GET` `/` — Public GET — confirm no private fields in response payload
- **apollo-radio** `GET` `/` — Public GET — confirm no private fields in response payload
- **apollo-radio** `GET` `/` — Public GET — confirm no private fields in response payload
- **apollo-radio** `GET` `/` — Public GET — confirm no private fields in response payload
- **apollo-remind** `POST` `/remind/push/subscribe` — Permission binding needs manual review
- **apollo-remind** `DELETE` `/remind/push/unsubscribe` — Permission binding needs manual review
- **apollo-scheduler** `GET` `/` — Public GET — confirm no private fields in response payload
- **apollo-scheduler** `GET` `/` — Public GET — confirm no private fields in response payload
- **apollo-sign** `GET` `/` — Public GET — confirm no private fields in response payload
- **apollo-social** `GET` `/activity/(?P<id>\d+)/replies` — Public GET — confirm no private fields in response payload
- **apollo-statistics** `POST` `/` — Permission binding needs manual review
- **apollo-statistics** `POST` `/` — Permission binding needs manual review
- **apollo-statistics** `POST` `/` — Permission binding needs manual review
- **apollo-statistics** `POST` `/` — Permission binding needs manual review
- **apollo-statistics** `POST` `/` — Permission binding needs manual review
- **apollo-statistics** `POST` `/` — Permission binding needs manual review
- **apollo-wow** `GET` `/wows/(?P<post_id>\d+)` — Public GET — confirm no private fields in response payload
- **apollo-wow** `GET` `/wows/types` — Public GET — confirm no private fields in response payload
- **apollo-wow** `GET` `/wows/chart/(?P<post_id>\d+)` — Public GET — confirm no private fields in response payload
- **apollo-wow** ABSPATH_COVERAGE — ABSPATH coverage 83%

## INFO — intentional public (track, don’t panic)

- **apollo-email** `POST` `/newsletter/subscribe` — INTENTIONAL_PUBLIC_WRITE
- **apollo-membership** `POST` `/report` — INTENTIONAL_PUBLIC_WRITE
- **apollo-remind** `POST` `/remind/telegram/webhook` — INTENTIONAL_PUBLIC_WRITE
- **apollo-users** `POST` `/profile/(?P<username>[a-zA-Z0-9_-]+)/view` — INTENTIONAL_PUBLIC_WRITE

## Plugin priority queue

| Plugin | CRIT | HIGH | MED | LOW | INFO | Pri |
|--------|-----:|-----:|----:|----:|-----:|-----|
| apollo-users | 0 | 4 | 0 | 0 | 1 | P1 |
| apollo-core | 0 | 1 | 2 | 0 | 0 | P1 |
| apollo-events | 0 | 0 | 12 | 0 | 0 | P2 |
| apollo-dashboard | 0 | 1 | 0 | 0 | 0 | P1 |
| apollo-hub | 0 | 0 | 6 | 0 | 0 | P2 |
| apollo-statistics | 0 | 0 | 6 | 0 | 0 | P2 |
| apollo-djs | 0 | 0 | 5 | 0 | 0 | P2 |
| apollo-groups | 0 | 0 | 4 | 0 | 0 | P2 |
| apollo-radio | 0 | 0 | 4 | 1 | 0 | P2 |
| apollo-wow | 0 | 0 | 4 | 0 | 0 | P2 |
| apollo-adverts | 0 | 0 | 3 | 0 | 0 | P2 |
| apollo-email | 0 | 0 | 3 | 0 | 1 | P2 |
| apollo-loc | 0 | 0 | 3 | 0 | 0 | P2 |
| apollo-comment | 0 | 0 | 2 | 0 | 0 | P2 |
| apollo-fav | 0 | 0 | 2 | 0 | 0 | P2 |
| apollo-remind | 0 | 0 | 2 | 1 | 1 | P2 |
| apollo-scheduler | 0 | 0 | 2 | 0 | 0 | P2 |
| apollo-dj-sync | 0 | 0 | 1 | 0 | 0 | P2 |
| apollo-docs | 0 | 0 | 1 | 0 | 0 | P2 |
| apollo-mod | 0 | 0 | 1 | 1 | 0 | P2 |
| apollo-pane-engine | 0 | 0 | 1 | 0 | 0 | P2 |
| apollo-sign | 0 | 0 | 1 | 0 | 0 | P2 |
| apollo-social | 0 | 0 | 1 | 0 | 0 | P2 |
| apollo-chat | 0 | 0 | 0 | 1 | 0 | P3 |
| apollo-maps | 0 | 0 | 0 | 1 | 0 | P3 |
| apollo-notif | 0 | 0 | 0 | 1 | 0 | P3 |
| apollo-templates | 0 | 0 | 0 | 2 | 0 | P3 |
| apollo-membership | 0 | 0 | 0 | 0 | 1 | P3 |

## Verified false positives (from first-pass audit)

These looked CRITICAL in registry-2 because GET+POST methods were merged:

| Plugin | Endpoint | Reality |
|--------|----------|---------|
| apollo-events | POST /eventos | `can_edit` / `can_edit_event` — auth OK |
| apollo-hub | PATCH hubs/* | `can_edit_hub` on write arrays — auth OK |
| apollo-adverts | POST classifieds | `create_item_permissions_check` — auth OK |
| apollo-comment | POST depoimento | `create_item_permissions_check` — auth OK |
| apollo-djs | POST /djs | check live method arrays (same pattern) |
| apollo-loc | POST locals | check live method arrays (same pattern) |

---
Machine JSON: `apollo-registry-2-ERRORS-DEEP.json`