# Apollo Deep Technical Audit

Generated: **2026-07-20**
Mode: **DEEP_TECHNICAL_STATIC_ANALYSIS**

## Ecosystem totals

| Metric | Value |
|--------|------:|
| Plugins in registry | 45 |
| Deep-audited targets | 39 |
| PHP LOC (audited) | 254993 |
| Classes | 559 |
| REST endpoints (live) | 352 |
| Open REST (HIGH/CRITICAL perm) | 102 |
| Unique CPTs | 14 |
| Unique taxonomies | 12 |
| Unique tables | 78 |
| Unique meta keys | 492 |
| Pages/rewrites | 92 |
| Shortcodes | 151 |

## New plugins (not in old registry)

- `apollo-calendar`
- `apollo-elementor-pro`
- `apollo-telegram`

## Version changes

- `apollo-chat`: 2.0.0 → **2.0.1**
- `apollo-dj-sync`: 1.0.0 → **1.0.1**
- `apollo-docs`: 1.0.0 → **1.0.1**
- `apollo-elementor`: 1.0.0 → **1.0.1**
- `apollo-gestor`: 1.0.0 → **1.0.1**
- `apollo-pane-engine`: 2.0.0 → **2.0.1**
- `apollo-radio`: 1.0.0 → **1.0.1**
- `apollo-remind`: 1.0.0 → **1.0.1**
- `apollo-scheduler`: 1.0.0 → **1.0.1**
- `apollo-sign`: 1.2.0 → **1.2.1**
- `apollo-statistics`: 2.0.0 → **2.0.6**

## Security risk bands

### HIGH
- `apollo-events`
- `apollo-login`
- `apollo-membership`

### MEDIUM
- `apollo-templates`
- `apollo-users`
- `apollo-wow`
- `apollo-adverts`
- `apollo-chat`
- `apollo-core`
- `apollo-djs`
- `apollo-dj-sync`
- `apollo-email`
- `apollo-hub`
- `apollo-loc`
- `apollo-mod`
- `apollo-radio`
- `apollo-remind`
- `apollo-sheets`

## Per-plugin deep scores

| Plugin | Ver | PHP | LOC | Classes | REST | Open | Tables | MetaP/U | Rewrites | Risk | Score |
|--------|-----|----:|----:|--------:|-----:|-----:|-------:|--------:|---------:|------|------:|
| apollo-login | 1.0.41 | 53 | 14933 | 23 | 26 | 16 | 0 | 18/26 | 6 | HIGH | 75 |
| apollo-events | 1.4.0 | 94 | 16930 | 14 | 24 | 12 | 1 | 76/1 | 6 | HIGH | 69 |
| apollo-membership | 1.0.3 | 42 | 7989 | 12 | 23 | 11 | 5 | 13/8 | 0 | HIGH | 53 |
| apollo-loc | 1.0.1 | 63 | 8755 | 39 | 3 | 3 | 0 | 28/0 | 0 | MEDIUM | 44 |
| apollo-email | 1.0.1 | 46 | 10797 | 19 | 16 | 3 | 4 | 6/8 | 0 | MEDIUM | 42 |
| apollo-sheets | 1.0.1 | 27 | 8523 | 19 | 4 | 0 | 0 | 4/0 | 0 | MEDIUM | 40 |
| apollo-hub | 1.0.1 | 31 | 4970 | 11 | 6 | 5 | 0 | 11/3 | 2 | MEDIUM | 39 |
| apollo-adverts | 1.0.2 | 52 | 10925 | 13 | 5 | 3 | 0 | 26/1 | 3 | MEDIUM | 38 |
| apollo-core | 6.2.3 | 63 | 18781 | 39 | 19 | 11 | 23 | 99/39 | 1 | MEDIUM | 35 |
| apollo-wow | 1.0.1 | 6 | 596 | 3 | 4 | 3 | 3 | 2/0 | 0 | MEDIUM | 35 |
| apollo-users | 1.0.1 | 30 | 13169 | 11 | 23 | 5 | 8 | 5/23 | 3 | MEDIUM | 33 |
| apollo-djs | 1.0.1 | 45 | 6112 | 10 | 5 | 5 | 0 | 29/0 | 0 | MEDIUM | 31 |
| apollo-chat | 2.0.1 | 9 | 4272 | 3 | 29 | 0 | 9 | 0/1 | 2 | MEDIUM | 30 |
| apollo-mod | 1.0.1 | 7 | 959 | 4 | 7 | 0 | 4 | 4/0 | 0 | MEDIUM | 30 |
| apollo-remind | 1.0.1 | 16 | 2092 | 11 | 13 | 2 | 4 | 2/1 | 0 | MEDIUM | 29 |
| apollo-dj-sync | 1.0.1 | 5 | 765 | 3 | 1 | 0 | 0 | 0/0 | 0 | MEDIUM | 28 |
| apollo-radio | 1.0.1 | 9 | 964 | 5 | 1 | 1 | 0 | 0/0 | 0 | MEDIUM | 27 |
| apollo-templates | 1.0.4 | 78 | 19976 | 10 | 6 | 5 | 0 | 38/3 | 10 | MEDIUM | 25 |
| apollo-comment | 1.0.1 | 10 | 1659 | 6 | 2 | 2 | 0 | 0/0 | 0 | LOW | 22 |
| apollo-maps | 1.0.1 | 9 | 792 | 5 | 1 | 1 | 0 | 6/0 | 0 | LOW | 18 |
| apollo-notif | 1.0.1 | 8 | 3645 | 4 | 13 | 1 | 3 | 0/6 | 1 | LOW | 18 |
| apollo-social | 1.0.1 | 24 | 3821 | 7 | 11 | 3 | 4 | 5/0 | 0 | LOW | 17 |
| apollo-calendar | 1.0.1 | 16 | 2883 | 12 | 5 | 1 | 0 | 8/2 | 1 | LOW | 15 |
| apollo-groups | 1.0.1 | 43 | 5712 | 4 | 24 | 0 | 6 | 0/1 | 5 | LOW | 15 |
| apollo-pane-engine | 2.0.1 | 7 | 1651 | 0 | 5 | 0 | 0 | 5/0 | 4 | LOW | 15 |
| apollo-telegram | 1.1.1 | 58 | 9880 | 47 | 7 | 0 | 1 | 4/2 | 2 | LOW | 15 |
| apollo-admin | 1.0.2 | 123 | 16062 | 28 | 18 | 0 | 0 | 20/3 | 4 | LOW | 10 |
| apollo-coauthor | 1.0.1 | 18 | 3707 | 14 | 3 | 0 | 0 | 1/0 | 0 | LOW | 10 |
| apollo-docs | 1.0.1 | 13 | 2709 | 9 | 4 | 0 | 2 | 9/1 | 1 | LOW | 10 |
| apollo-journal | 1.0.1 | 16 | 4517 | 7 | 3 | 3 | 0 | 8/0 | 1 | LOW | 9 |
| apollo-seo | 1.0.3 | 10 | 4317 | 8 | 3 | 3 | 0 | 23/0 | 3 | LOW | 9 |
| apollo-scheduler | 1.0.1 | 33 | 2864 | 25 | 3 | 1 | 0 | 23/7 | 6 | LOW | 6 |
| apollo-statistics | 2.0.6 | 47 | 10518 | 42 | 11 | 0 | 8 | 2/5 | 1 | LOW | 6 |
| apollo-dashboard | 1.0.1 | 16 | 4358 | 3 | 4 | 1 | 5 | 2/12 | 5 | LOW | 3 |
| apollo-fav | 1.0.1 | 11 | 3988 | 8 | 4 | 1 | 3 | 9/4 | 0 | LOW | 3 |
| apollo-sign | 1.2.1 | 39 | 7938 | 10 | 2 | 0 | 2 | 9/0 | 1 | LOW | 3 |
| apollo-elementor | 1.0.1 | 48 | 3862 | 26 | 0 | 0 | 0 | 16/8 | 0 | LOW | 0 |
| apollo-elementor-pro | 1.0.1 | 23 | 1712 | 21 | 0 | 0 | 0 | 2/3 | 0 | LOW | 0 |
| apollo-gestor | 1.0.1 | 46 | 6890 | 24 | 0 | 0 | 8 | 10/1 | 0 | LOW | 0 |

## Open REST routes (HIGH/CRITICAL) — permission body analysis

Methods are resolved from PHP source. `ADMIN_CAPABILITY` means body calls `current_user_can('manage_options')` etc. `RETURNS_TRUE_UNCONDITIONALLY` / `__return_true` means no auth. WRITE+public = CRITICAL.

### apollo-templates
- **HIGH** `GET` `/radar/stats` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `examples/user-radar-examples.php` · `return true;`
- **HIGH** `GET` `/radar/top` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `examples/user-radar-examples.php` · `return true;`
- **HIGH** `GET` `/templates` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `includes/class-plugin.php` · `return true;`
- **HIGH** `GET` `/templates/calendars` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `includes/class-plugin.php` · `return true;`
- **HIGH** `GET` `/canvas/blocks` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `includes/class-plugin.php` · `return true;`

### apollo-users
- **HIGH** `GET` `/users/radar` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/API/UsersController.php` · `return true;`
- **HIGH** `GET` `/users/(?P<username>[a-zA-Z0-9_-]+)` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/API/UsersController.php` · `return true;`
- **HIGH** `GET` `/users/(?P<id>\d+)` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/API/UsersController.php` · `return true;`
- **HIGH** `GET` `/profile/(?P<username>[a-zA-Z0-9_-]+)` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/API/UsersController.php` · `return true;`
- **CRITICAL** `POST` `/profile/(?P<username>[a-zA-Z0-9_-]+)/view` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true [WRITE method is publicly callable]
  - file: `src/API/UsersController.php` · `return true;`

### apollo-wow
- **CRITICAL** `DELETE,GET` `/wows/(?P<post_id>\d+)` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true [WRITE method is publicly callable]
  - file: `src/Plugin.php` · `return true;`
- **HIGH** `GET` `/wows/types` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/Plugin.php` · `return true;`
- **HIGH** `GET` `/wows/chart/(?P<post_id>\d+)` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/Plugin.php` · `return true;`

### apollo-adverts
- **CRITICAL** `GET,POST` `/` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true [WRITE method is publicly callable]
  - file: `src/API/ClassifiedsController.php` · `return true;`
- **CRITICAL** `DELETE,GET,PATCH,POST,PUT` `/` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true [WRITE method is publicly callable]
  - file: `src/API/ClassifiedsController.php` · `return true;`
- **HIGH** `GET` `/classifieds/(?P<id>[\d]+)/related` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/RelatedAds.php` · `return true;`

### apollo-calendar
- **HIGH** `GET` `/calendar/holidays` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/API/CalendarController.php` · `return true;`

### apollo-comment
- **CRITICAL** `GET,POST` `/` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true [WRITE method is publicly callable]
  - file: `src/API/DepoimentoController.php` · `return true;`
- **CRITICAL** `DELETE,GET,PATCH,POST,PUT` `/` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true [WRITE method is publicly callable]
  - file: `src/API/DepoimentoController.php` · `return true;`

### apollo-core
- **HIGH** `GET` `/health` — **PUBLIC_UNAUTHENTICATED** via `__return_true` · intentional?
  - permission_callback is __return_true [likely intentional public surface]
  - file: `src/API/HealthController.php` · `return true;`
- **HIGH** `GET` `/pane-mode/status` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/API/PaneModeController.php` · `return true;`
- **HIGH** `GET` `/registry/cpts` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/API/RegistryController.php` · `return true;`
- **HIGH** `GET` `/registry/taxonomies` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/API/RegistryController.php` · `return true;`
- **HIGH** `GET` `/registry/status` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/API/RegistryController.php` · `return true;`
- **HIGH** `GET` `/search` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/API/SearchController.php` · `return true;`
- **HIGH** `GET` `/search/` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/API/SearchController.php` · `return true;`
- **HIGH** `GET` `/` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/API/ShortcodesController.php` · `return true;`
- **HIGH** `GET` `/sounds` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/API/SoundController.php` · `return true;`
- **HIGH** `GET` `/sounds/(?P<id>\d+)` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/API/SoundController.php` · `return true;`
- **HIGH** `GET` `/sounds/popular` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/API/SoundController.php` · `return true;`

### apollo-dashboard
- **HIGH** `GET` `/dashboard/widgets` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `includes/class-plugin.php` · `return true;`

### apollo-djs
- **CRITICAL** `GET,POST` `/djs` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true [WRITE method is publicly callable]
  - file: `src/API/DJsController.php` · `return true;`
- **CRITICAL** `DELETE,GET,PATCH,POST,PUT` `/djs/(?P<id>\d+)` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true [WRITE method is publicly callable]
  - file: `src/API/DJsController.php` · `return true;`
- **HIGH** `GET` `/djs/(?P<id>\d+)/eventos` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/API/DJsController.php` · `return true;`
- **HIGH** `GET` `/djs/por-som/(?P<sound>[a-zA-Z0-9_-]+)` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/API/DJsController.php` · `return true;`
- **HIGH** `GET` `/djs/buscar` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/API/DJsController.php` · `return true;`

### apollo-email
- **HIGH** `GET` `/email/track/open/(?P<token>[a-zA-Z0-9]+)` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/API/EmailController.php` · `return true;`
- **HIGH** `GET` `/email/track/click/(?P<token>[a-zA-Z0-9]+)` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/API/EmailController.php` · `return true;`
- **CRITICAL** `POST` `/newsletter/subscribe` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true [WRITE method is publicly callable]
  - file: `src/Newsletter.php` · `return true;`

### apollo-events
- **CRITICAL** `GET,POST` `/eventos` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true [WRITE method is publicly callable]
  - file: `src/API/EventsController.php` · `return true;`
- **CRITICAL** `DELETE,GET,PATCH,POST,PUT` `/eventos/(?P<id>\d+)` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true [WRITE method is publicly callable]
  - file: `src/API/EventsController.php` · `return true;`
- **HIGH** `GET` `/eventos/proximos` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/API/EventsController.php` · `return true;`
- **HIGH** `GET` `/eventos/passados` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/API/EventsController.php` · `return true;`
- **HIGH** `GET` `/eventos/hoje` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/API/EventsController.php` · `return true;`
- **HIGH** `GET` `/eventos/por-data/(?P<date>[\d-]+)` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/API/EventsController.php` · `return true;`
- **HIGH** `GET` `/eventos/por-local/(?P<loc_id>\d+)` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/API/EventsController.php` · `return true;`
- **HIGH** `GET` `/eventos/por-dj/(?P<dj_id>\d+)` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/API/EventsController.php` · `return true;`
- **CRITICAL** `DELETE,GET,POST` `/eventos/(?P<id>\d+)/djs` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true [WRITE method is publicly callable]
  - file: `src/API/EventsController.php` · `return true;`
- **HIGH** `GET` `/eventos/buscar` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/API/EventsController.php` · `return true;`
- **HIGH** `GET` `/eventos/calendario/(?P<year>\d{4})/(?P<month>\d{1,2})` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/API/EventsController.php` · `return true;`
- **HIGH** `GET` `/eventos/(?P<id>\d+)/estatisticas` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/API/EventsController.php` · `return true;`

### apollo-fav
- **HIGH** `GET` `/` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `includes/class-rest-controller.php` · `return true;`

### apollo-hub
- **HIGH** `GET` `/hubs` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/API/HubController.php` · `return true;`
- **CRITICAL** `GET,PATCH,POST,PUT` `/hubs/(?P<username>[a-zA-Z0-9._-]+)` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true [WRITE method is publicly callable]
  - file: `src/API/HubController.php` · `return true;`
- **CRITICAL** `GET,PATCH,POST,PUT` `/hubs/(?P<username>[a-zA-Z0-9._-]+)/links` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true [WRITE method is publicly callable]
  - file: `src/API/HubController.php` · `return true;`
- **CRITICAL** `GET,PATCH,POST,PUT` `/hubs/(?P<username>[a-zA-Z0-9._-]+)/blocks` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true [WRITE method is publicly callable]
  - file: `src/API/HubController.php` · `return true;`
- **HIGH** `GET` `/hubs/(?P<username>[a-zA-Z0-9._-]+)/share/(?P<post_id>\d+)` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/API/HubController.php` · `return true;`

### apollo-journal
- **HIGH** `GET` `/journal/posts` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/API/PostsController.php` · `return true;`
- **HIGH** `GET` `/journal/news` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/API/PostsController.php` · `return true;`
- **HIGH** `GET` `/journal/notas` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/API/PostsController.php` · `return true;`

### apollo-loc
- **CRITICAL** `GET,POST` `/` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true [WRITE method is publicly callable]
  - file: `src/API/LocalsController.php` · `return true;`
- **CRITICAL** `DELETE,GET,PATCH,POST,PUT` `/` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true [WRITE method is publicly callable]
  - file: `src/API/LocalsController.php` · `return true;`
- **HIGH** `GET` `/` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/API/LocalsController.php` · `return true;`

### apollo-login
- **HIGH** `GET` `/dj/config` — **PUBLIC_UNAUTHENTICATED** via `__return_true` · intentional?
  - permission_callback is __return_true [likely intentional public surface]
  - file: `src/API/AppAuthController.php` · `return true;`
- **CRITICAL** `POST` `/auth/login` — **PUBLIC_UNAUTHENTICATED** via `__return_true` · intentional?
  - permission_callback is __return_true [WRITE method is publicly callable] [likely intentional public surface]
  - file: `src/API/AuthController.php` · `return true;`
- **CRITICAL** `POST` `/auth/register` — **PUBLIC_UNAUTHENTICATED** via `__return_true` · intentional?
  - permission_callback is __return_true [WRITE method is publicly callable] [likely intentional public surface]
  - file: `src/API/AuthController.php` · `return true;`
- **CRITICAL** `POST` `/auth/reset-request` — **PUBLIC_UNAUTHENTICATED** via `__return_true` · intentional?
  - permission_callback is __return_true [WRITE method is publicly callable] [likely intentional public surface]
  - file: `src/API/AuthController.php` · `return true;`
- **HIGH** `GET` `/auth/check-username` — **PUBLIC_UNAUTHENTICATED** via `__return_true` · intentional?
  - permission_callback is __return_true [likely intentional public surface]
  - file: `src/API/AuthController.php` · `return true;`
- **HIGH** `GET` `/auth/check-email` — **PUBLIC_UNAUTHENTICATED** via `__return_true` · intentional?
  - permission_callback is __return_true [likely intentional public surface]
  - file: `src/API/AuthController.php` · `return true;`
- **CRITICAL** `POST` `/auth/reset-confirm` — **PUBLIC_UNAUTHENTICATED** via `__return_true` · intentional?
  - permission_callback is __return_true [WRITE method is publicly callable] [likely intentional public surface]
  - file: `src/API/AuthController.php` · `return true;`
- **CRITICAL** `POST` `/auth/verify-email` — **PUBLIC_UNAUTHENTICATED** via `__return_true` · intentional?
  - permission_callback is __return_true [WRITE method is publicly callable] [likely intentional public surface]
  - file: `src/API/AuthController.php` · `return true;`
- **CRITICAL** `POST` `/auth/resend-verification` — **PUBLIC_UNAUTHENTICATED** via `__return_true` · intentional?
  - permission_callback is __return_true [WRITE method is publicly callable] [likely intentional public surface]
  - file: `src/API/AuthController.php` · `return true;`
- **HIGH** `GET` `/quiz/questions` — **PUBLIC_UNAUTHENTICATED** via `__return_true` · intentional?
  - permission_callback is __return_true [likely intentional public surface]
  - file: `src/API/QuizController.php` · `return true;`
- **CRITICAL** `POST` `/quiz/submit` — **PUBLIC_UNAUTHENTICATED** via `__return_true` · intentional?
  - permission_callback is __return_true [WRITE method is publicly callable] [likely intentional public surface]
  - file: `src/API/QuizController.php` · `return true;`
- **CRITICAL** `POST` `/simon/submit` — **PUBLIC_UNAUTHENTICATED** via `__return_true` · intentional?
  - permission_callback is __return_true [WRITE method is publicly callable] [likely intentional public surface]
  - file: `src/API/QuizController.php` · `return true;`
- **HIGH** `GET` `/simon/highscores` — **PUBLIC_UNAUTHENTICATED** via `__return_true` · intentional?
  - permission_callback is __return_true [likely intentional public surface]
  - file: `src/API/QuizController.php` · `return true;`
- **CRITICAL** `POST` `/auth/token` — **PUBLIC_UNAUTHENTICATED** via `__return_true` · intentional?
  - permission_callback is __return_true [WRITE method is publicly callable] [likely intentional public surface]
  - file: `src/Security/JWTAuth.php` · `return true;`
- **CRITICAL** `POST` `/auth/token/refresh` — **PUBLIC_UNAUTHENTICATED** via `__return_true` · intentional?
  - permission_callback is __return_true [WRITE method is publicly callable] [likely intentional public surface]
  - file: `src/Security/JWTAuth.php` · `return true;`
- **CRITICAL** `POST` `/csp-report` — **PUBLIC_UNAUTHENTICATED** via `__return_true` · intentional?
  - permission_callback is __return_true [WRITE method is publicly callable] [likely intentional public surface]
  - file: `src/Security/SecurityHeaders.php` · `return true;`

### apollo-maps
- **HIGH** `GET` `/map/explorer` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/API/ExplorerController.php` · `return true;`

### apollo-membership
- **HIGH** `GET` `/achievements` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/API/AchievementsController.php` · `return true;`
- **HIGH** `GET` `/achievements/(?P<id>\d+)` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/API/AchievementsController.php` · `return true;`
- **HIGH** `GET` `/membership/evidence/(?P<achievement_id>\d+)` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/API/AchievementsController.php` · `return true;`
- **HIGH** `GET` `/membership/verify/(?P<hash>[a-zA-Z0-9]+)` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/API/AchievementsController.php` · `return true;`
- **HIGH** `GET` `/leaderboard` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/API/LeaderboardController.php` · `return true;`
- **HIGH** `GET` `/leaderboard/user/(?P<id>\d+)` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/API/LeaderboardController.php` · `return true;`
- **HIGH** `GET` `/membership-badge` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/API/LeaderboardController.php` · `return true;`
- **HIGH** `GET` `/ranks` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/API/RanksController.php` · `return true;`
- **HIGH** `GET` `/ranks/(?P<id>\d+)` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/API/RanksController.php` · `return true;`
- **CRITICAL** `POST` `/report` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true [WRITE method is publicly callable]
  - file: `src/API/ReportController.php` · `return true;`
- **HIGH** `GET` `/membership/triggers` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/API/TriggersController.php` · `return true;`

### apollo-notif
- **HIGH** `GET` `/notifications/push/vapid-public-key` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/Plugin.php` · `return true;`

### apollo-radio
- **HIGH** `GET` `/` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/API/RadioController.php` · `return true;`

### apollo-remind
- **HIGH** `GET` `/remind/push/vapid-key` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/API/PushController.php` · `return true;`
- **CRITICAL** `POST` `/remind/telegram/webhook` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true [WRITE method is publicly callable]
  - file: `src/API/TelegramWebhook.php` · `return true;`

### apollo-scheduler
- **HIGH** `GET` `/` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/API/AvailabilityController.php` · `return true;`

### apollo-seo
- **HIGH** `GET` `/seo/post/(?P<id>\d+)` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/Plugin.php` · `return true;`
- **HIGH** `GET` `/seo/term/(?P<id>\d+)` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/Plugin.php` · `return true;`
- **HIGH** `GET` `/seo/home` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/Plugin.php` · `return true;`

### apollo-social
- **HIGH** `GET` `/depo/(?P<user_id>\d+)` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/Plugin.php` · `return true;`
- **CRITICAL** `GET,POST` `/activity/(?P<id>\d+)/replies` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true [WRITE method is publicly callable]
  - file: `src/Plugin.php` · `return true;`
- **HIGH** `GET` `/members` — **PUBLIC_UNAUTHENTICATED** via `__return_true`
  - permission_callback is __return_true
  - file: `src/Plugin.php` · `return true;`

## Permission effective classes (all routes)

- **apollo-templates**: PUBLIC_UNAUTHENTICATED:5, CAPABILITY_CHECK:1
- **apollo-users**: LOGGED_IN_ONLY:12, CAPABILITY_CHECK:6, PUBLIC_UNAUTHENTICATED:5
- **apollo-wow**: PUBLIC_UNAUTHENTICATED:3, OTHER:1
- **apollo-admin**: ADMIN_CAPABILITY:15, LOGGED_IN_ONLY:3
- **apollo-adverts**: PUBLIC_UNAUTHENTICATED:3, LOGGED_IN_ONLY:2
- **apollo-calendar**: LOGGED_IN_ONLY:4, PUBLIC_UNAUTHENTICATED:1
- **apollo-chat**: OTHER:27, ADMIN_CAPABILITY:2
- **apollo-coauthor**: CAPABILITY_CHECK:3
- **apollo-comment**: PUBLIC_UNAUTHENTICATED:2
- **apollo-core**: PUBLIC_UNAUTHENTICATED:11, ADMIN_CAPABILITY:5, LOGGED_IN_ONLY:2, CAPABILITY_CHECK:1
- **apollo-dashboard**: LOGGED_IN_ONLY:3, PUBLIC_UNAUTHENTICATED:1
- **apollo-djs**: PUBLIC_UNAUTHENTICATED:5
- **apollo-dj-sync**: DELEGATES_TO_METHOD:1
- **apollo-docs**: LOGGED_IN_ONLY:4
- **apollo-email**: ADMIN_CAPABILITY:12, PUBLIC_UNAUTHENTICATED:3, LOGGED_IN_PLUS:1
- **apollo-events**: PUBLIC_UNAUTHENTICATED:12, CAPABILITY_CHECK:5, ADMIN_CAPABILITY:4, LOGGED_IN_ONLY:3
- **apollo-fav**: LOGGED_IN_PLUS:3, PUBLIC_UNAUTHENTICATED:1
- **apollo-groups**: OTHER:23, CAPABILITY_CHECK:1
- **apollo-hub**: PUBLIC_UNAUTHENTICATED:5, LOGGED_IN_PLUS:1
- **apollo-journal**: PUBLIC_UNAUTHENTICATED:3
- **apollo-loc**: PUBLIC_UNAUTHENTICATED:3
- **apollo-login**: PUBLIC_UNAUTHENTICATED:16, CUSTOM_LOGIC:4, ADMIN_CAPABILITY:3, LOGGED_IN_ONLY:2, DELEGATES_TO_METHOD:1
- **apollo-maps**: PUBLIC_UNAUTHENTICATED:1
- **apollo-membership**: PUBLIC_UNAUTHENTICATED:11, DYNAMIC_CAPABILITY:7, LOGGED_IN_ONLY:5
- **apollo-mod**: OTHER:6, LOGGED_IN_ONLY:1
- **apollo-notif**: LOGGED_IN_ONLY:12, PUBLIC_UNAUTHENTICATED:1
- **apollo-pane-engine**: LOGGED_IN_ONLY:5
- **apollo-radio**: PUBLIC_UNAUTHENTICATED:1
- **apollo-remind**: LOGGED_IN_ONLY:11, PUBLIC_UNAUTHENTICATED:2
- **apollo-scheduler**: LOGGED_IN_ONLY:2, PUBLIC_UNAUTHENTICATED:1
- **apollo-seo**: PUBLIC_UNAUTHENTICATED:3
- **apollo-sheets**: DELEGATES_TO_METHOD:3, CAPABILITY_CHECK:1
- **apollo-sign**: LOGGED_IN_PLUS:2
- **apollo-social**: LOGGED_IN_ONLY:8, PUBLIC_UNAUTHENTICATED:3
- **apollo-statistics**: ADMIN_CAPABILITY:9, LOGGED_IN_ONLY:1, OTHER:1
- **apollo-telegram**: CUSTOM_LOGIC:5, ALWAYS_DENY:1, ADMIN_CAPABILITY:1

## Coupling edges (depends_on)

- `apollo-admin` → `apollo-adverts`
- `apollo-admin` → `apollo-chat`
- `apollo-admin` → `apollo-coauthor`
- `apollo-admin` → `apollo-comment`
- `apollo-admin` → `apollo-core`
- `apollo-admin` → `apollo-dashboard`
- `apollo-admin` → `apollo-djs`
- `apollo-admin` → `apollo-docs`
- `apollo-admin` → `apollo-email`
- `apollo-admin` → `apollo-events`
- `apollo-admin` → `apollo-fav`
- `apollo-admin` → `apollo-groups`
- `apollo-admin` → `apollo-hub`
- `apollo-admin` → `apollo-loc`
- `apollo-admin` → `apollo-login`
- `apollo-admin` → `apollo-membership`
- `apollo-admin` → `apollo-mod`
- `apollo-admin` → `apollo-notif`
- `apollo-admin` → `apollo-seo`
- `apollo-admin` → `apollo-social`
- `apollo-admin` → `apollo-statistics`
- `apollo-admin` → `apollo-templates`
- `apollo-admin` → `apollo-users`
- `apollo-admin` → `apollo-wow`
- `apollo-adverts` → `apollo-admin`
- `apollo-adverts` → `apollo-chat`
- `apollo-adverts` → `apollo-core`
- `apollo-adverts` → `apollo-dashboard`
- `apollo-adverts` → `apollo-fav`
- `apollo-adverts` → `apollo-login`
- `apollo-adverts` → `apollo-notif`
- `apollo-adverts` → `apollo-social`
- `apollo-adverts` → `apollo-templates`
- `apollo-adverts` → `apollo-wow`
- `apollo-calendar` → `apollo-core`
- `apollo-calendar` → `apollo-remind`
- `apollo-chat` → `apollo-core`
- `apollo-chat` → `apollo-notif`
- `apollo-chat` → `apollo-users`
- `apollo-coauthor` → `apollo-core`
- `apollo-comment` → `apollo-core`
- `apollo-core` → `apollo-admin`
- `apollo-core` → `apollo-adverts`
- `apollo-core` → `apollo-calendar`
- `apollo-core` → `apollo-chat`
- `apollo-core` → `apollo-coauthor`
- `apollo-core` → `apollo-comment`
- `apollo-core` → `apollo-dashboard`
- `apollo-core` → `apollo-djs`
- `apollo-core` → `apollo-docs`
- `apollo-core` → `apollo-email`
- `apollo-core` → `apollo-events`
- `apollo-core` → `apollo-fav`
- `apollo-core` → `apollo-gestor`
- `apollo-core` → `apollo-groups`
- `apollo-core` → `apollo-hub`
- `apollo-core` → `apollo-journal`
- `apollo-core` → `apollo-loc`
- `apollo-core` → `apollo-login`
- `apollo-core` → `apollo-membership`
- `apollo-core` → `apollo-mod`
- `apollo-core` → `apollo-notif`
- `apollo-core` → `apollo-pane-engine`
- `apollo-core` → `apollo-scheduler`
- `apollo-core` → `apollo-seo`
- `apollo-core` → `apollo-sheets`
- `apollo-core` → `apollo-sign`
- `apollo-core` → `apollo-social`
- `apollo-core` → `apollo-statistics`
- `apollo-core` → `apollo-templates`
- `apollo-core` → `apollo-users`
- `apollo-core` → `apollo-wow`
- `apollo-dashboard` → `apollo-core`
- `apollo-dashboard` → `apollo-groups`
- `apollo-dashboard` → `apollo-templates`
- `apollo-dashboard` → `apollo-users`
- `apollo-dj-sync` → `apollo-core`
- `apollo-dj-sync` → `apollo-login`
- `apollo-dj-sync` → `apollo-users`
- `apollo-djs` → `apollo-comment`
- `apollo-djs` → `apollo-core`
- `apollo-djs` → `apollo-dashboard`
- `apollo-djs` → `apollo-events`
- `apollo-djs` → `apollo-fav`
- `apollo-djs` → `apollo-statistics`
- `apollo-djs` → `apollo-templates`
- `apollo-djs` → `apollo-wow`
- `apollo-docs` → `apollo-core`
- `apollo-elementor` → `apollo-core`
- `apollo-elementor` → `elementor`
- `apollo-elementor-pro` → `apollo-core`
- `apollo-elementor-pro` → `apollo-elementor`
- `apollo-elementor-pro` → `apollo-events`
- `apollo-elementor-pro` → `apollo-fav`
- `apollo-elementor-pro` → `apollo-hub`
- `apollo-elementor-pro` → `apollo-membership`
- `apollo-elementor-pro` → `apollo-wow`
- `apollo-email` → `apollo-admin`
- `apollo-email` → `apollo-core`
- `apollo-email` → `apollo-events`
- `apollo-email` → `apollo-gestor`
- `apollo-email` → `apollo-login`
- `apollo-email` → `apollo-membership`
- `apollo-email` → `apollo-users`
- `apollo-events` → `apollo-coauthor`
- `apollo-events` → `apollo-core`
- `apollo-events` → `apollo-fav`
- `apollo-events` → `apollo-loc`
- `apollo-events` → `apollo-mod`
- `apollo-events` → `apollo-social`
- `apollo-events` → `apollo-statistics`
- `apollo-events` → `apollo-wow`
- `apollo-fav` → `apollo-core`
- `apollo-fav` → `apollo-dashboard`
- `apollo-fav` → `apollo-events`
- `apollo-fav` → `apollo-login`
- `apollo-fav` → `apollo-notif`
- `apollo-fav` → `apollo-social`
- `apollo-fav` → `apollo-statistics`
- `apollo-fav` → `apollo-users`
- `apollo-gestor` → `apollo-core`
- `apollo-gestor` → `apollo-email`
- `apollo-groups` → `apollo-core`
- `apollo-groups` → `apollo-mod`
- `apollo-groups` → `apollo-templates`
- `apollo-groups` → `apollo-users`
- `apollo-hub` → `apollo-admin`
- `apollo-hub` → `apollo-core`
- `apollo-hub` → `apollo-events`
- `apollo-hub` → `apollo-login`
- `apollo-hub` → `apollo-maps`
- `apollo-hub` → `apollo-social`
- `apollo-hub` → `apollo-users`
- `apollo-journal` → `apollo-core`
- `apollo-loc` → `apollo-comment`
- `apollo-loc` → `apollo-core`
- `apollo-loc` → `apollo-fav`
- `apollo-loc` → `apollo-social`
- `apollo-loc` → `apollo-templates`
- `apollo-loc` → `apollo-wow`
- `apollo-login` → `apollo-chat`
- `apollo-login` → `apollo-core`
- `apollo-login` → `apollo-email`
- `apollo-login` → `apollo-loc`
- `apollo-login` → `apollo-membership`
- `apollo-login` → `apollo-social`
- `apollo-login` → `apollo-telegram`
- `apollo-login` → `apollo-templates`
- `apollo-maps` → `apollo-core`
- `apollo-maps` → `apollo-loc`
- `apollo-membership` → `apollo-core`
- `apollo-membership` → `apollo-login`
- `apollo-membership` → `apollo-notif`
- `apollo-membership` → `apollo-statistics`
- `apollo-membership` → `apollo-users`
- `apollo-mod` → `apollo-core`
- `apollo-notif` → `apollo-core`
- `apollo-notif` → `apollo-email`
- `apollo-notif` → `apollo-hub`
- `apollo-notif` → `apollo-templates`
- `apollo-pane-engine` → `apollo-adverts`
- `apollo-pane-engine` → `apollo-chat`
- `apollo-pane-engine` → `apollo-comment`
- `apollo-pane-engine` → `apollo-core`
- `apollo-pane-engine` → `apollo-dashboard`
- `apollo-pane-engine` → `apollo-djs`
- `apollo-pane-engine` → `apollo-email`
- `apollo-pane-engine` → `apollo-events`
- `apollo-pane-engine` → `apollo-fav`
- `apollo-pane-engine` → `apollo-groups`
- `apollo-pane-engine` → `apollo-hub`
- `apollo-pane-engine` → `apollo-loc`
- `apollo-pane-engine` → `apollo-login`
- `apollo-pane-engine` → `apollo-maps`
- `apollo-pane-engine` → `apollo-membership`
- `apollo-pane-engine` → `apollo-mod`
- `apollo-pane-engine` → `apollo-notif`
- `apollo-pane-engine` → `apollo-radio`
- `apollo-pane-engine` → `apollo-seo`
- `apollo-pane-engine` → `apollo-sheets`
- `apollo-pane-engine` → `apollo-social`
- `apollo-pane-engine` → `apollo-statistics`
- `apollo-pane-engine` → `apollo-templates`
- `apollo-pane-engine` → `apollo-users`
- `apollo-pane-engine` → `apollo-wow`
- `apollo-radio` → `apollo-core`
- `apollo-remind` → `apollo-core`
- `apollo-remind` → `apollo-email`
- `apollo-remind` → `apollo-notif`
- `apollo-remind` → `apollo-telegram`
- `apollo-scheduler` → `apollo-core`
- `apollo-scheduler` → `apollo-email`
- `apollo-scheduler` → `apollo-events`
- `apollo-scheduler` → `apollo-gestor`
- `apollo-scheduler` → `apollo-groups`
- `apollo-scheduler` → `apollo-users`
- `apollo-sheets` → `apollo-core`
- `apollo-sign` → `apollo-core`
- `apollo-sign` → `apollo-docs`
- `apollo-sign` → `apollo-email`
- `apollo-sign` → `openssl`
- `apollo-social` → `apollo-core`
- `apollo-social` → `apollo-membership`
- `apollo-social` → `apollo-mod`
- `apollo-social` → `apollo-templates`
- `apollo-social` → `apollo-users`
- `apollo-statistics` → `apollo-admin`
- `apollo-statistics` → `apollo-core`
- `apollo-statistics` → `apollo-dj-sync`
- `apollo-statistics` → `apollo-fav`
- `apollo-statistics` → `apollo-login`
- `apollo-statistics` → `apollo-membership`
- `apollo-statistics` → `apollo-radio`
- `apollo-statistics` → `apollo-users`
- `apollo-telegram` → `apollo-core`
- `apollo-telegram` → `apollo-events`
- `apollo-templates` → `apollo-admin`
- `apollo-templates` → `apollo-adverts`
- `apollo-templates` → `apollo-chat`
- `apollo-templates` → `apollo-coauthor`
- `apollo-templates` → `apollo-comment`
- `apollo-templates` → `apollo-core`
- `apollo-templates` → `apollo-dashboard`
- `apollo-templates` → `apollo-djs`
- `apollo-templates` → `apollo-docs`
- `apollo-templates` → `apollo-email`
- `apollo-templates` → `apollo-events`
- `apollo-templates` → `apollo-fav`
- `apollo-templates` → `apollo-groups`
- `apollo-templates` → `apollo-hub`
- `apollo-templates` → `apollo-loc`
- `apollo-templates` → `apollo-login`
- `apollo-templates` → `apollo-membership`
- `apollo-templates` → `apollo-mod`
- `apollo-templates` → `apollo-notif`
- `apollo-templates` → `apollo-radio`
- `apollo-templates` → `apollo-sign`
- `apollo-templates` → `apollo-social`
- `apollo-templates` → `apollo-statistics`
- `apollo-templates` → `apollo-users`
- `apollo-templates` → `apollo-wow`
- `apollo-users` → `apollo-core`
- `apollo-users` → `apollo-login`
- `apollo-users` → `apollo-membership`
- `apollo-users` → `apollo-social`
- `apollo-users` → `apollo-templates`
- `apollo-wow` → `apollo-core`

---
Registry: `apollo-registry-2.json` · Machine report: `apollo-registry-2-deep-report.json`