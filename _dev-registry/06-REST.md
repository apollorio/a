# REST API

**187 routes declared** in `apollo-core/config/routes.php`; 
**391 `register_rest_route()` sites** found in code.

> Namespace and route are read as **positional arguments** via a
> balanced-paren parse. An earlier regex that took the first quoted string
> inside the call reported `methods` as the namespace 340 times. If you
> extend this tooling, do not go back to that shortcut.

## Namespaces as built

| namespace | sites |
|---|---|
| `apollo/v1` | 309 |
| `UNRESOLVED $this->namespace` | 70 |
| `apollo-telegram/v1` | 7 |
| `UNRESOLVED $ns` | 3 |
| `UNRESOLVED defined( 'APOLLO_EVENT_REST_NAMESPACE' ) ? APOLLO_EVENT_REST_NAMESPACE : 'apollo` | 1 |
| `v1` | 1 |


Only 30 of 391 sites pass the namespace as a string literal. The rest
pass `$this->namespace`, `$ns` or a class constant, so the value is resolved
from its defining site and the method is recorded:

| resolution | sites | meaning |
|---|---|---|
| `LOCAL_ASSIGN` | 89 | traced to a local assignment |
| `PROPERTY_DEFAULT` | 83 | traced to a property default or assignment |
| `UNKNOWN` | 74 | **not resolved — treat as UNKNOWN, not as absent** |
| `PROPERTY_ASSIGN_CONSTANT_DEFINE` | 62 | traced to a `define()` with a literal value |
| `LITERAL` | 30 | quoted at the call site |
| `CLASS_CONST` | 29 | traced to a `const` in the same class |
| `CONSTANT_DEFINE` | 10 | traced to a `define()` with a literal value |
| `LOCAL_ASSIGN_CONSTANT_DEFINE` | 7 | traced to a `define()` with a literal value |
| `LOCAL_ASSIGN_TERNARY_FALLBACK` | 7 | traced to a local assignment |


Unresolved entries are reported as the raw expression. They are **UNKNOWN**,
which is not the same as absent — do not read a blank as "no namespace".

`278` of `391` sites resolve to a complete public URL.

## Permission posture

> Counted per **endpoint**, not per `register_rest_route()` call. One call
> often declares a public `READABLE` entry and a protected `CREATABLE` entry
> side by side. Attributing the first callback to the whole call reported 33
> public write endpoints where only 15 exist.

| posture | endpoints | meaning |
|---|---|---|
| explicit `permission_callback` | 441 | a decision was made |
| `__return_true` | 112 | deliberately public — anyone on the internet |
| — of those accepting writes | **15** | public mutation; each needs its own in-handler check |
| **no** `permission_callback` | 0 | WordPress warns; treated as public |


See `12-SECURITY-SURFACE.md` for the public routes enumerated.

## Declared route table

| route | owner | methods | auth |
|---|---|---|---|
| `/health` | `apollo-core` | GET | public |
| `/registry` | `apollo-core` | GET | admin |
| `/registry/cpts` | `apollo-core` | GET | admin |
| `/registry/taxonomies` | `apollo-core` | GET | admin |
| `/registry/status` | `apollo-core` | GET | admin |
| `/registry/tables` | `apollo-core` | GET | admin |
| `/sounds` | `apollo-core` | GET | public |
| `/sounds/tree` | `apollo-core` | GET | public |
| `/sounds/{id}` | `apollo-core` | GET | public |
| `/sounds/user` | `apollo-core` | GET POST | required |
| `/sounds/popular` | `apollo-core` | GET | public |
| `/auth/login` | `apollo-login` | POST | public |
| `/auth/register` | `apollo-login` | POST | public |
| `/auth/logout` | `apollo-login` | POST | required |
| `/auth/reset-request` | `apollo-login` | POST | public |
| `/auth/reset-confirm` | `apollo-login` | POST | public |
| `/auth/verify-email` | `apollo-login` | POST | public |
| `/auth/resend-verification` | `apollo-login` | POST | public |
| `/auth/check-username` | `apollo-login` | GET | public |
| `/auth/check-email` | `apollo-login` | GET | public |
| `/quiz/submit` | `apollo-login` | POST | public |
| `/quiz/questions` | `apollo-login` | GET | public |
| `/simon/submit` | `apollo-login` | POST | public |
| `/simon/highscores` | `apollo-login` | GET | public |
| `/users` | `apollo-users` | GET | public |
| `/users/me` | `apollo-users` | GET PUT | required |
| `/users/{id}` | `apollo-users` | GET PUT | public |
| `/users/{id}/preferences` | `apollo-users` | GET PUT | required |
| `/users/{id}/matchmaking` | `apollo-users` | GET | required |
| `/users/{id}/fields` | `apollo-users` | GET PUT | required |
| `/users/search` | `apollo-users` | GET | public |
| `/users/radar` | `apollo-users` | GET | public |
| `/profile/{username}` | `apollo-users` | GET | public |
| `/profile/{username}/view` | `apollo-users` | POST | public |
| `/profile/avatar` | `apollo-users` | POST DELETE | required |
| `/profile/cover` | `apollo-users` | POST DELETE | required |
| `/profile/views` | `apollo-users` | GET | required |
| `/events` | `apollo-events` | GET POST | mixed |
| `/events/{id}` | `apollo-events` | GET PUT DELETE | mixed |
| `/events/upcoming` | `apollo-events` | GET | public |
| `/events/past` | `apollo-events` | GET | public |
| `/events/today` | `apollo-events` | GET | public |
| `/events/by-date/{date}` | `apollo-events` | GET | public |
| `/events/by-loc/{loc_id}` | `apollo-events` | GET | public |
| `/events/by-dj/{dj_id}` | `apollo-events` | GET | public |
| `/events/{id}/djs` | `apollo-events` | GET POST DELETE | mixed |
| `/djs` | `apollo-djs` | GET POST | mixed |
| `/djs/{id}` | `apollo-djs` | GET PUT DELETE | mixed |
| `/djs/{id}/events` | `apollo-djs` | GET | public |
| `/djs/by-sound/{sound}` | `apollo-djs` | GET | public |
| `/djs/search` | `apollo-djs` | GET | public |
| `/locals` | `apollo-loc` | GET POST | mixed |
| `/locals/{id}` | `apollo-loc` | GET PUT DELETE | mixed |
| `/locals/nearby` | `apollo-loc` | GET | public |
| `/classifieds` | `apollo-adverts` | GET POST | mixed |
| `/classifieds/{id}` | `apollo-adverts` | GET PUT DELETE | mixed |
| `/classifieds/search` | `apollo-adverts` | GET | public |
| `/classifieds/my` | `apollo-adverts` | GET | required |
| `/feed` | `apollo-social` | GET | required |
| `/feed/post` | `apollo-social` | POST | required |
| `/activity/{id}` | `apollo-social` | DELETE | required |
| `/followers/{user_id}` | `apollo-social` | GET | public |
| `/following/{user_id}` | `apollo-social` | GET | public |
| `/favs` | `apollo-fav` | GET POST | required |
| `/favs/{post_id}` | `apollo-fav` | DELETE | required |
| `/favs/toggle/{post_id}` | `apollo-fav` | POST | required |
| `/favs/count/{post_id}` | `apollo-fav` | GET | public |
| `/favs/check/{post_id}` | `apollo-fav` | GET | required |
| `/wows` | `apollo-wow` | POST | required |
| `/wows/{post_id}` | `apollo-wow` | GET DELETE | mixed |
| `/wows/types` | `apollo-wow` | GET | public |
| `/depoimentos` | `apollo-comment` | GET POST | mixed |
| `/depoimentos/{id}` | `apollo-comment` | GET PUT DELETE | mixed |
| `/docs` | `apollo-docs` | GET POST | required |
| `/docs/{id}` | `apollo-docs` | GET PUT DELETE | required |
| `/docs/{id}/download` | `apollo-docs` | GET | required |
| `/docs/{id}/versions` | `apollo-docs` | GET | required |
| `/docs/{id}/lock` | `apollo-docs` | POST | required |
| `/docs/{id}/finalize` | `apollo-docs` | POST | required |
| `/docs/{id}/upload` | `apollo-docs` | POST | required |
| `/docs/folders` | `apollo-docs` | GET POST | required |
| `/docs/folders/{id}` | `apollo-docs` | PUT DELETE | required |
| `/signatures` | `apollo-sign` | POST | required |
| `/signatures/{id}` | `apollo-sign` | GET | required |
| `/signatures/{id}/sign` | `apollo-sign` | POST | required |
| `/signatures/{id}/audit` | `apollo-sign` | GET | required |
| `/signatures/verify/{hash}` | `apollo-sign` | GET | public |
| `/chat/threads` | `apollo-chat` | GET POST | required |
| `/chat/threads/{id}` | `apollo-chat` | GET DELETE | required |
| `/chat/threads/{id}/messages` | `apollo-chat` | GET POST | required |
| `/chat/threads/{id}/read` | `apollo-chat` | POST | required |
| `/chat/threads/{id}/members` | `apollo-chat` | GET POST DELETE | required |
| `/chat/messages/{id}` | `apollo-chat` | PUT DELETE | required |
| `/chat/messages/{id}/react` | `apollo-chat` | POST | required |
| `/chat/messages/{id}/pin` | `apollo-chat` | POST | required |
| `/chat/messages/{id}/forward` | `apollo-chat` | POST | required |
| `/chat/typing` | `apollo-chat` | POST | required |
| `/chat/poll` | `apollo-chat` | GET | required |
| `/chat/unread` | `apollo-chat` | GET | required |
| `/chat/more` | `apollo-chat` | GET | required |
| `/chat/presence` | `apollo-chat` | PUT | required |
| `/chat/online` | `apollo-chat` | GET | required |
| `/chat/search` | `apollo-chat` | GET | required |
| `/chat/upload` | `apollo-chat` | POST | required |
| `/chat/block` | `apollo-chat` | POST | required |
| `/chat/unblock` | `apollo-chat` | POST | required |
| `/chat/mute` | `apollo-chat` | POST | required |
| `/chat/unmute` | `apollo-chat` | POST | required |
| `/notifications` | `apollo-notif` | GET | required |
| `/notifications/{id}/read` | `apollo-notif` | POST | required |
| `/notifications/read-all` | `apollo-notif` | POST | required |
| `/notifications/unread-count` | `apollo-notif` | GET | required |
| `/notifications/preferences` | `apollo-notif` | GET PUT | required |
| `/email/send` | `apollo-email` | POST | admin |
| `/email/test` | `apollo-email` | POST | admin |
| `/email/stats` | `apollo-email` | GET | admin |
| `/email/queue` | `apollo-email` | GET | admin |
| `/email/templates` | `apollo-email` | GET POST | admin |
| `/email/templates/{id}` | `apollo-email` | GET PUT DELETE | admin |
| `/email/log` | `apollo-email` | GET | admin |
| `/email/preferences` | `apollo-email` | GET PUT | required |
| `/membership/achievements` | `apollo-membership` | GET | public |
| `/membership/achievements/{id}` | `apollo-membership` | GET | public |
| `/membership/user-achievements` | `apollo-membership` | GET | optional |
| `/membership/achievements/award` | `apollo-membership` | POST | admin |
| `/membership/achievements/revoke` | `apollo-membership` | POST | admin |
| `/membership/points` | `apollo-membership` | GET | required |
| `/membership/points/award` | `apollo-membership` | POST | admin |
| `/membership/points/deduct` | `apollo-membership` | POST | admin |
| `/membership/ranks` | `apollo-membership` | GET | public |
| `/membership/ranks/{id}` | `apollo-membership` | GET | public |
| `/membership/leaderboard` | `apollo-membership` | GET | public |
| `/membership/user-summary` | `apollo-membership` | GET | required |
| `/suppliers` | `apollo-suppliers` | GET POST | industry |
| `/suppliers/{id}` | `apollo-suppliers` | GET PUT DELETE | industry |
| `/suppliers/search` | `apollo-suppliers` | GET | industry |
| `/suppliers/categories` | `apollo-suppliers` | GET | industry |
| `/groups` | `apollo-groups` | GET POST | mixed |
| `/groups/{id}` | `apollo-groups` | GET PUT DELETE | mixed |
| `/groups/{id}/members` | `apollo-groups` | GET | public |
| `/groups/{id}/join` | `apollo-groups` | POST | required |
| `/groups/{id}/leave` | `apollo-groups` | DELETE | required |
| `/groups/my` | `apollo-groups` | GET | required |
| `/settings` | `apollo-admin` | GET | admin |
| `/settings/{slug}` | `apollo-admin` | GET POST | admin |
| `/settings/export` | `apollo-admin` | GET | admin |
| `/settings/import` | `apollo-admin` | POST | admin |
| `/stats/overview` | `apollo-statistics` | GET | admin |
| `/stats/events` | `apollo-statistics` | GET | admin |
| `/stats/users` | `apollo-statistics` | GET | admin |
| `/stats/content` | `apollo-statistics` | GET | admin |
| `/stats/export` | `apollo-statistics` | GET | admin |
| `/cena/calendar` | `apollo-cena` | GET | industry |
| `/cena/calendar/save-date` | `apollo-cena` | POST | industry |
| `/cena/calendar/{id}` | `apollo-cena` | PUT DELETE | industry |
| `/cena/members` | `apollo-cena` | GET | industry |
| `/cena/access/request` | `apollo-cena` | POST | required |
| `/sheets` | `apollo-sheets` | GET POST | required |
| `/sheets/{id}` | `apollo-sheets` | GET PUT DELETE | required |
| `/sheets/{id}/copy` | `apollo-sheets` | POST | required |
| `/sheets/import` | `apollo-sheets` | POST | required |
| `/sheets/{id}/export` | `apollo-sheets` | GET | required |
| `/sheets/{id}/preview` | `apollo-sheets` | GET | required |
| `/shortcodes` | `apollo-shortcodes` | GET | public |
| `/shortcodes/render` | `apollo-shortcodes` | POST | public |
| `/search` | `apollo-shortcodes` | GET | public |
| `/newsletter/subscribe` | `apollo-shortcodes` | POST | public |
| `/templates` | `apollo-templates` | GET | public |
| `/canvas/save` | `apollo-templates` | POST | required |
| `/canvas/blocks` | `apollo-templates` | GET | public |
| `/coauthors/{post_id}` | `apollo-coauthor` | GET PUT | required |
| `/dashboard` | `apollo-dashboard` | GET | required |
| `/dashboard/widgets` | `apollo-dashboard` | GET | public |
| `/dashboard/settings` | `apollo-dashboard` | GET PUT | required |
| `/dashboard/layout` | `apollo-dashboard` | GET PUT | required |
| `/hubs` | `apollo-hub` | GET | public |
| `/hubs/{username}` | `apollo-hub` | GET PUT | mixed |
| `/hubs/{username}/links` | `apollo-hub` | GET PUT | mixed |
| `/mod/queue` | `apollo-mod` | GET | mod |
| `/mod/queue/{id}/approve` | `apollo-mod` | POST | mod |
| `/mod/queue/{id}/reject` | `apollo-mod` | POST | mod |
| `/mod/queue/{id}/flag` | `apollo-mod` | POST | mod |
| `/mod/log` | `apollo-mod` | GET | mod |
| `/mod/stats` | `apollo-mod` | GET | mod |
| `/seo/sitemap` | `apollo-seo` | GET | public |
| `/pwa/manifest` | `apollo-pwa` | GET | public |
| `/pwa/sw` | `apollo-pwa` | GET | public |


## All as-built registration sites

| unit | resolved route | methods | permission_callback | ns via | file:line |
|---|---|---|---|---|---|
| `apollo-admin` | `/wp-json/apollo/v1/admin/memberships` | GET | **MISSING** | constant_define | `plugins/apollo-admin/src/Frontend/Controller/MembershipController.php:30` |
| `apollo-admin` | `/wp-json/apollo/v1/admin/pending` | GET | **MISSING** | constant_define | `plugins/apollo-admin/src/Frontend/Controller/PendingController.php:31` |
| `apollo-admin` | `/wp-json/apollo/v1/admin/pending/(?P<id>\d+)/approve` | POST | **MISSING** | constant_define | `plugins/apollo-admin/src/Frontend/Controller/PendingController.php:43` |
| `apollo-admin` | `/wp-json/apollo/v1/admin/pending/(?P<id>\d+)/reject` | POST | **MISSING** | constant_define | `plugins/apollo-admin/src/Frontend/Controller/PendingController.php:65` |
| `apollo-admin` | `/wp-json/apollo/v1/admin/registry` | READABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-admin/src/Rest/SettingsController.php:110` |
| `apollo-admin` | `/wp-json/apollo/v1/admin/registry/refresh` | CREATABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-admin/src/Rest/SettingsController.php:121` |
| `apollo-admin` | `/wp-json/apollo/v1/errorlog` | READABLE | **MISSING** | local_assign_constant_define | `plugins/apollo-admin/src/ErrorLogViewer.php:60` |
| `apollo-admin` | `/wp-json/apollo/v1/errorlog` | DELETABLE | **MISSING** | local_assign_constant_define | `plugins/apollo-admin/src/ErrorLogViewer.php:90` |
| `apollo-admin` | `/wp-json/apollo/v1/preferences` | READABLE | **MISSING** | local_assign_constant_define | `plugins/apollo-admin/src/UserPreferences.php:158` |
| `apollo-admin` | `/wp-json/apollo/v1/preferences` | CREATABLE | **MISSING** | local_assign_constant_define | `plugins/apollo-admin/src/UserPreferences.php:171` |
| `apollo-admin` | `/wp-json/apollo/v1/preferences/(?P<key>[a-z0-9_-]+)` | DELETABLE | **MISSING** | local_assign_constant_define | `plugins/apollo-admin/src/UserPreferences.php:184` |
| `apollo-admin` | `/wp-json/apollo/v1/settings` | READABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-admin/src/Rest/SettingsController.php:45` |
| `apollo-admin` | `/wp-json/apollo/v1/settings/(?P<slug>[a-z0-9_-]+)` | READABLE CREATABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-admin/src/Rest/SettingsController.php:56` |
| `apollo-admin` | `/wp-json/apollo/v1/settings/(?P<slug>[a-z0-9_-]+)/schema` | READABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-admin/src/Rest/SettingsController.php:91` |
| `apollo-admin` | `/wp-json/apollo/v1/settings/export` | READABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-admin/src/Rest/SettingsController.php:133` |
| `apollo-admin` | `/wp-json/apollo/v1/settings/import` | CREATABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-admin/src/Rest/SettingsController.php:143` |
| `apollo-admin` | `/wp-json/apollo/v1/toolbar` | READABLE | **MISSING** | local_assign_constant_define | `plugins/apollo-admin/src/ToolbarCustomizer.php:136` |
| `apollo-admin` | `/wp-json/apollo/v1/toolbar` | CREATABLE | **MISSING** | local_assign_constant_define | `plugins/apollo-admin/src/ToolbarCustomizer.php:149` |
| `apollo-adverts` | _'/' . $this->rest_base_ | READABLE CREATABLE | **MISSING** | **unresolved** | `plugins/apollo-adverts/src/API/ClassifiedsController.php:33` |
| `apollo-adverts` | _'/' . $this->rest_base_ | READABLE | **MISSING** | **unresolved** | `plugins/apollo-adverts/src/API/SearchController.php:29` |
| `apollo-adverts` | _'/' . $this->rest_base . '/(?P<id>[\d]+)'_ | READABLE EDITABLE DELETABLE | **MISSING** | **unresolved** | `plugins/apollo-adverts/src/API/ClassifiedsController.php:53` |
| `apollo-adverts` | _'/' . $this->rest_base . '/my'_ | READABLE | **MISSING** | **unresolved** | `plugins/apollo-adverts/src/API/ClassifiedsController.php:82` |
| `apollo-adverts` | `/wp-json/apollo/v1/classifieds/(?P<id>[\d]+)/related` | READABLE | **MISSING** | constant_define | `plugins/apollo-adverts/src/RelatedAds.php:102` |
| `apollo-adverts` | `/wp-json/apollo/v1/events/request` | POST | **MISSING** | literal | `plugins/apollo-adverts/includes/event-selector.php:821` |
| `apollo-adverts` | `/wp-json/apollo/v1/safety/signals` | READABLE | **MISSING** | class_const | `plugins/apollo-adverts/src/API/SafetyController.php:42` |
| `apollo-adverts` | `/wp-json/apollo/v1/safety/vouch` | CREATABLE | **MISSING** | class_const | `plugins/apollo-adverts/src/API/SafetyController.php:55` |
| `apollo-adverts` | `/wp-json/apollo/v1/safety/vouch/confirm` | CREATABLE | **MISSING** | class_const | `plugins/apollo-adverts/src/API/SafetyController.php:70` |
| `apollo-calendar` | `? /calendar` | READABLE | **MISSING** | **unresolved** | `plugins/apollo-calendar/src/API/CalendarController.php:41` |
| `apollo-calendar` | `? /calendar/appointments` | CREATABLE | **MISSING** | **unresolved** | `plugins/apollo-calendar/src/API/CalendarController.php:69` |
| `apollo-calendar` | `? /calendar/appointments/(?P<id>\d+)` | EDITABLE DELETABLE | **MISSING** | **unresolved** | `plugins/apollo-calendar/src/API/CalendarController.php:80` |
| `apollo-calendar` | `? /calendar/holidays` | READABLE | **MISSING** | **unresolved** | `plugins/apollo-calendar/src/API/CalendarController.php:115` |
| `apollo-calendar` | `? /calendar/ical` | READABLE | **MISSING** | **unresolved** | `plugins/apollo-calendar/src/API/CalendarController.php:143` |
| `apollo-chat` | `/wp-json/apollo/v1/chat/block/(?P<user_id>\d+)` | POST | **MISSING** | local_assign | `plugins/apollo-chat/src/Plugin.php:424` |
| `apollo-chat` | `/wp-json/apollo/v1/chat/block/(?P<user_id>\d+)` | DELETE | **MISSING** | local_assign | `plugins/apollo-chat/src/Plugin.php:433` |
| `apollo-chat` | `/wp-json/apollo/v1/chat/dm` | POST | **MISSING** | local_assign | `plugins/apollo-chat/src/Plugin.php:305` |
| `apollo-chat` | `/wp-json/apollo/v1/chat/gif-search` | GET | **MISSING** | local_assign | `plugins/apollo-chat/src/Plugin.php:550` |
| `apollo-chat` | `/wp-json/apollo/v1/chat/messages/(?P<id>\d+)` | PUT | **MISSING** | local_assign | `plugins/apollo-chat/src/Plugin.php:380` |
| `apollo-chat` | `/wp-json/apollo/v1/chat/messages/(?P<id>\d+)` | DELETE | **MISSING** | local_assign | `plugins/apollo-chat/src/Plugin.php:391` |
| `apollo-chat` | `/wp-json/apollo/v1/chat/messages/(?P<id>\d+)/forward` | POST | **MISSING** | local_assign | `plugins/apollo-chat/src/Plugin.php:488` |
| `apollo-chat` | `/wp-json/apollo/v1/chat/messages/(?P<id>\d+)/react` | POST | **MISSING** | local_assign | `plugins/apollo-chat/src/Plugin.php:402` |
| `apollo-chat` | `/wp-json/apollo/v1/chat/messages/(?P<id>\d+)/report` | POST | **MISSING** | local_assign | `plugins/apollo-chat/src/Plugin.php:575` |
| `apollo-chat` | `/wp-json/apollo/v1/chat/more` | GET | **MISSING** | local_assign | `plugins/apollo-chat/src/Plugin.php:335` |
| `apollo-chat` | `/wp-json/apollo/v1/chat/poll` | GET | **MISSING** | local_assign | `plugins/apollo-chat/src/Plugin.php:325` |
| `apollo-chat` | `/wp-json/apollo/v1/chat/presence` | POST | **MISSING** | local_assign | `plugins/apollo-chat/src/Plugin.php:444` |
| `apollo-chat` | `/wp-json/apollo/v1/chat/reports` | GET | **MISSING** | local_assign | `plugins/apollo-chat/src/Plugin.php:597` |
| `apollo-chat` | `/wp-json/apollo/v1/chat/reports/(?P<id>\d+)/action` | POST | **MISSING** | local_assign | `plugins/apollo-chat/src/Plugin.php:610` |
| `apollo-chat` | `/wp-json/apollo/v1/chat/search` | GET | **MISSING** | local_assign | `plugins/apollo-chat/src/Plugin.php:413` |
| `apollo-chat` | `/wp-json/apollo/v1/chat/send` | POST | **MISSING** | local_assign | `plugins/apollo-chat/src/Plugin.php:294` |
| `apollo-chat` | `/wp-json/apollo/v1/chat/thread-for-context` | POST | **MISSING** | local_assign | `plugins/apollo-chat/src/Plugin.php:521` |
| `apollo-chat` | `/wp-json/apollo/v1/chat/threads` | GET | **MISSING** | local_assign | `plugins/apollo-chat/src/Plugin.php:274` |
| `apollo-chat` | `/wp-json/apollo/v1/chat/threads/(?P<id>\d+)` | GET | **MISSING** | local_assign | `plugins/apollo-chat/src/Plugin.php:284` |
| `apollo-chat` | `/wp-json/apollo/v1/chat/threads/(?P<id>\d+)` | DELETE | **MISSING** | local_assign | `plugins/apollo-chat/src/Plugin.php:345` |
| `apollo-chat` | `/wp-json/apollo/v1/chat/threads/(?P<id>\d+)/export` | GET | **MISSING** | local_assign | `plugins/apollo-chat/src/Plugin.php:586` |
| `apollo-chat` | `/wp-json/apollo/v1/chat/threads/(?P<id>\d+)/members` | GET POST DELETE | **MISSING** | local_assign | `plugins/apollo-chat/src/Plugin.php:466` |
| `apollo-chat` | `/wp-json/apollo/v1/chat/threads/(?P<id>\d+)/mute` | POST | **MISSING** | local_assign | `plugins/apollo-chat/src/Plugin.php:477` |
| `apollo-chat` | `/wp-json/apollo/v1/chat/threads/(?P<id>\d+)/older` | GET | **MISSING** | local_assign | `plugins/apollo-chat/src/Plugin.php:455` |
| `apollo-chat` | `/wp-json/apollo/v1/chat/threads/(?P<id>\d+)/pin` | POST DELETE | **MISSING** | local_assign | `plugins/apollo-chat/src/Plugin.php:499` |
| `apollo-chat` | `/wp-json/apollo/v1/chat/threads/(?P<id>\d+)/pinned` | GET | **MISSING** | local_assign | `plugins/apollo-chat/src/Plugin.php:510` |
| `apollo-chat` | `/wp-json/apollo/v1/chat/threads/(?P<id>\d+)/read` | POST | **MISSING** | local_assign | `plugins/apollo-chat/src/Plugin.php:369` |
| `apollo-chat` | `/wp-json/apollo/v1/chat/typing` | POST | **MISSING** | local_assign | `plugins/apollo-chat/src/Plugin.php:358` |
| `apollo-chat` | `/wp-json/apollo/v1/chat/unread` | GET | **MISSING** | local_assign | `plugins/apollo-chat/src/Plugin.php:315` |
| `apollo-chat` | `/wp-json/apollo/v1/chat/upload` | POST | **MISSING** | local_assign | `plugins/apollo-chat/src/Plugin.php:623` |
| `apollo-coauthor` | _'/' . $this->rest_base . '/(?P<post_id>[\d]+)'_ | READABLE EDITABLE | **MISSING** | **unresolved** | `plugins/apollo-coauthor/src/API/CoauthorController.php:58` |
| `apollo-coauthor` | _'/' . $this->rest_base . '/search'_ | READABLE | **MISSING** | **unresolved** | `plugins/apollo-coauthor/src/API/CoauthorController.php:115` |
| `apollo-coauthor` | `/wp-json/apollo/v1/coauthors/bulk` | CREATABLE | **MISSING** | constant_define | `plugins/apollo-coauthor/src/Components/BulkEdit.php:169` |
| `apollo-comment` | _'/' . $this->rest_base_ | READABLE CREATABLE | **MISSING** | property_default | `plugins/apollo-comment/src/API/DepoimentoController.php:26` |
| `apollo-comment` | _'/' . $this->rest_base . '/(?P<id>[\d]+)'_ | READABLE EDITABLE DELETABLE | **MISSING** | property_default | `plugins/apollo-comment/src/API/DepoimentoController.php:79` |
| `apollo-core` | _'/' . $this->rest_base_ | READABLE | **MISSING** | **unresolved** | `plugins/apollo-core/src/API/ShortcodesController.php:70` |
| `apollo-core` | _'/' . $this->rest_base . '/(?P<tag>[a-z_]+)'_ | READABLE | **MISSING** | **unresolved** | `plugins/apollo-core/src/API/ShortcodesController.php:81` |
| `apollo-core` | _'/' . $this->rest_base . '/render'_ | CREATABLE | **MISSING** | **unresolved** | `plugins/apollo-core/src/API/ShortcodesController.php:100` |
| `apollo-core` | `? /health` | READABLE | **MISSING** | **unresolved** | `plugins/apollo-core/src/API/HealthController.php:33` |
| `apollo-core` | `? /pane-mode/disable` | CREATABLE | **MISSING** | **unresolved** | `plugins/apollo-core/src/API/PaneModeController.php:53` |
| `apollo-core` | `? /pane-mode/enable` | CREATABLE | **MISSING** | **unresolved** | `plugins/apollo-core/src/API/PaneModeController.php:42` |
| `apollo-core` | `? /pane-mode/status` | READABLE | **MISSING** | **unresolved** | `plugins/apollo-core/src/API/PaneModeController.php:31` |
| `apollo-core` | _'/search/' . $type_ | READABLE | **MISSING** | property_default | `plugins/apollo-core/src/API/SearchController.php:54` |
| `apollo-core` | `/wp-json/apollo/v1/registry` | READABLE | **MISSING** | class_const | `plugins/apollo-core/src/API/RegistryController.php:41` |
| `apollo-core` | `/wp-json/apollo/v1/registry/cpts` | READABLE | **MISSING** | class_const | `plugins/apollo-core/src/API/RegistryController.php:54` |
| `apollo-core` | `/wp-json/apollo/v1/registry/inventory-sync` | CREATABLE | **MISSING** | class_const | `plugins/apollo-core/src/API/RegistryController.php:106` |
| `apollo-core` | `/wp-json/apollo/v1/registry/status` | READABLE | **MISSING** | class_const | `plugins/apollo-core/src/API/RegistryController.php:80` |
| `apollo-core` | `/wp-json/apollo/v1/registry/tables` | READABLE | **MISSING** | class_const | `plugins/apollo-core/src/API/RegistryController.php:93` |
| `apollo-core` | `/wp-json/apollo/v1/registry/taxonomies` | READABLE | **MISSING** | class_const | `plugins/apollo-core/src/API/RegistryController.php:67` |
| `apollo-core` | `/wp-json/apollo/v1/search` | READABLE | **MISSING** | property_default | `plugins/apollo-core/src/API/SearchController.php:40` |
| `apollo-core` | `/wp-json/apollo/v1/sounds` | READABLE | **MISSING** | class_const | `plugins/apollo-core/src/API/SoundController.php:42` |
| `apollo-core` | `/wp-json/apollo/v1/sounds/(?P<id>\d+)` | READABLE | **MISSING** | class_const | `plugins/apollo-core/src/API/SoundController.php:55` |
| `apollo-core` | `/wp-json/apollo/v1/sounds/popular` | READABLE | **MISSING** | class_const | `plugins/apollo-core/src/API/SoundController.php:115` |
| `apollo-core` | `/wp-json/apollo/v1/sounds/user` | READABLE | **MISSING** | class_const | `plugins/apollo-core/src/API/SoundController.php:76` |
| `apollo-core` | `/wp-json/apollo/v1/sounds/user` | CREATABLE | **MISSING** | class_const | `plugins/apollo-core/src/API/SoundController.php:89` |
| `apollo-dashboard` | `/wp-json/apollo/v1/dashboard` | GET | **MISSING** | literal | `plugins/apollo-dashboard/includes/class-plugin.php:218` |
| `apollo-dashboard` | `/wp-json/apollo/v1/dashboard/layout` | GET PUT | **MISSING** | literal | `plugins/apollo-dashboard/includes/class-plugin.php:251` |
| `apollo-dashboard` | `/wp-json/apollo/v1/dashboard/settings` | GET PUT | **MISSING** | literal | `plugins/apollo-dashboard/includes/class-plugin.php:240` |
| `apollo-dashboard` | `/wp-json/apollo/v1/dashboard/widgets` | GET | **MISSING** | literal | `plugins/apollo-dashboard/includes/class-plugin.php:229` |
| `apollo-dj-sync` | _'/' . $this->rest_base . '/session'_ | CREATABLE | **MISSING** | property_default | `plugins/apollo-dj-sync/src/API/DJPermissionsController.php:38` |
| `apollo-djs` | `/wp-json/apollo/v1/djs` | READABLE CREATABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-djs/src/API/DJsController.php:36` |
| `apollo-djs` | `/wp-json/apollo/v1/djs/(?P<id>\d+)` | READABLE EDITABLE DELETABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-djs/src/API/DJsController.php:92` |
| `apollo-djs` | `/wp-json/apollo/v1/djs/(?P<id>\d+)/eventos` | READABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-djs/src/API/DJsController.php:115` |
| `apollo-djs` | `/wp-json/apollo/v1/djs/buscar` | READABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-djs/src/API/DJsController.php:137` |
| `apollo-djs` | `/wp-json/apollo/v1/djs/por-som/(?P<sound>[a-zA-Z0-9_-]+)` | READABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-djs/src/API/DJsController.php:126` |
| `apollo-docs` | _'/' . $this->base_ | GET | **MISSING** | property_default | `plugins/apollo-docs/src/API/DocsController.php:22` |
| `apollo-docs` | _'/' . $this->base_ | POST | **MISSING** | property_default | `plugins/apollo-docs/src/API/DocsController.php:34` |
| `apollo-docs` | _'/' . $this->base_ | GET | **MISSING** | property_default | `plugins/apollo-docs/src/API/FoldersController.php:18` |
| `apollo-docs` | _'/' . $this->base_ | POST | **MISSING** | property_default | `plugins/apollo-docs/src/API/FoldersController.php:29` |
| `apollo-docs` | _'/' . $this->base . '/(?P<id>\d+)'_ | GET | **MISSING** | property_default | `plugins/apollo-docs/src/API/DocsController.php:45` |
| `apollo-docs` | _'/' . $this->base . '/(?P<id>\d+)'_ | PUT | **MISSING** | property_default | `plugins/apollo-docs/src/API/DocsController.php:56` |
| `apollo-docs` | _'/' . $this->base . '/(?P<id>\d+)'_ | DELETE | **MISSING** | property_default | `plugins/apollo-docs/src/API/DocsController.php:67` |
| `apollo-docs` | _'/' . $this->base . '/(?P<id>\d+)'_ | PUT | **MISSING** | property_default | `plugins/apollo-docs/src/API/FoldersController.php:40` |
| `apollo-docs` | _'/' . $this->base . '/(?P<id>\d+)'_ | DELETE | **MISSING** | property_default | `plugins/apollo-docs/src/API/FoldersController.php:51` |
| `apollo-docs` | _'/' . $this->base . '/(?P<id>\d+)/download'_ | GET | **MISSING** | property_default | `plugins/apollo-docs/src/API/DocsController.php:78` |
| `apollo-docs` | _'/' . $this->base . '/(?P<id>\d+)/finalize'_ | POST | **MISSING** | property_default | `plugins/apollo-docs/src/API/DocsController.php:111` |
| `apollo-docs` | _'/' . $this->base . '/(?P<id>\d+)/lock'_ | POST | **MISSING** | property_default | `plugins/apollo-docs/src/API/DocsController.php:100` |
| `apollo-docs` | _'/' . $this->base . '/(?P<id>\d+)/upload'_ | POST | **MISSING** | property_default | `plugins/apollo-docs/src/API/DocsController.php:122` |
| `apollo-docs` | _'/' . $this->base . '/(?P<id>\d+)/versions'_ | GET | **MISSING** | property_default | `plugins/apollo-docs/src/API/DocsController.php:89` |
| `apollo-docs` | _'/' . $this->base . '/upload'_ | POST | **MISSING** | property_default | `plugins/apollo-docs/src/API/DocsController.php:133` |
| `apollo-email` | `/wp-json/apollo/v1/email/log` | READABLE | **MISSING** | property_default | `plugins/apollo-email/src/API/EmailController.php:248` |
| `apollo-email` | `/wp-json/apollo/v1/email/log/purge` | CREATABLE | **MISSING** | property_default | `plugins/apollo-email/src/API/EmailController.php:290` |
| `apollo-email` | `/wp-json/apollo/v1/email/preferences` | READABLE PUT | **MISSING** | property_default | `plugins/apollo-email/src/API/EmailController.php:347` |
| `apollo-email` | `/wp-json/apollo/v1/email/queue` | READABLE | **MISSING** | property_default | `plugins/apollo-email/src/API/EmailController.php:122` |
| `apollo-email` | `/wp-json/apollo/v1/email/queue/(?P<id>\d+)/cancel` | CREATABLE | **MISSING** | property_default | `plugins/apollo-email/src/API/EmailController.php:149` |
| `apollo-email` | `/wp-json/apollo/v1/email/queue/(?P<id>\d+)/retry` | CREATABLE | **MISSING** | property_default | `plugins/apollo-email/src/API/EmailController.php:159` |
| `apollo-email` | `/wp-json/apollo/v1/email/queue/purge` | CREATABLE | **MISSING** | property_default | `plugins/apollo-email/src/API/EmailController.php:169` |
| `apollo-email` | `/wp-json/apollo/v1/email/send` | CREATABLE | **MISSING** | property_default | `plugins/apollo-email/src/API/EmailController.php:59` |
| `apollo-email` | `/wp-json/apollo/v1/email/stats` | READABLE | **MISSING** | property_default | `plugins/apollo-email/src/API/EmailController.php:111` |
| `apollo-email` | `/wp-json/apollo/v1/email/templates` | READABLE CREATABLE | **MISSING** | property_default | `plugins/apollo-email/src/API/EmailController.php:187` |
| `apollo-email` | `/wp-json/apollo/v1/email/templates/(?P<id>\d+)` | READABLE PUT DELETABLE | **MISSING** | property_default | `plugins/apollo-email/src/API/EmailController.php:225` |
| `apollo-email` | `/wp-json/apollo/v1/email/test` | CREATABLE | **MISSING** | property_default | `plugins/apollo-email/src/API/EmailController.php:92` |
| `apollo-email` | `/wp-json/apollo/v1/email/track/click/(?P<token>[a-zA-Z0-9]+)` | READABLE | **MISSING** | property_default | `plugins/apollo-email/src/API/EmailController.php:325` |
| `apollo-email` | `/wp-json/apollo/v1/email/track/open/(?P<token>[a-zA-Z0-9]+)` | READABLE | **MISSING** | property_default | `plugins/apollo-email/src/API/EmailController.php:308` |
| `apollo-email` | `/wp-json/apollo/v1/newsletter/subscribe` | POST | **MISSING** | literal | `plugins/apollo-email/src/Newsletter.php:771` |
| `apollo-email` | `/wp-json/apollo/v1/newsletter/subscribers` | GET | **MISSING** | literal | `plugins/apollo-email/src/Newsletter.php:781` |
| `apollo-events` | `? /eventos/(?P<id>\d+)/fragmento` | GET | **MISSING** | **unresolved** | `plugins/apollo-events/includes/render-single.php:1055` |
| `apollo-events` | `/wp-json/apollo/v1/cena-rio/agenda` | GET | **MISSING** | literal | `plugins/apollo-events/src/Cena_Rio_Submissions.php:53` |
| `apollo-events` | `/wp-json/apollo/v1/cena-rio/cancelar/(?P<id>\d+)` | POST | **MISSING** | literal | `plugins/apollo-events/src/Cena_Rio_Submissions.php:131` |
| `apollo-events` | `/wp-json/apollo/v1/cena-rio/confirmar/(?P<id>\d+)` | POST | **MISSING** | literal | `plugins/apollo-events/src/Cena_Rio_Submissions.php:114` |
| `apollo-events` | `/wp-json/apollo/v1/cena-rio/enviar` | POST | **MISSING** | literal | `plugins/apollo-events/src/Cena_Rio_Submissions.php:65` |
| `apollo-events` | `/wp-json/apollo/v1/eventos` | READABLE CREATABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-events/src/API/EventsController.php:54` |
| `apollo-events` | `/wp-json/apollo/v1/eventos/(?P<id>\d+)` | READABLE EDITABLE DELETABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-events/src/API/EventsController.php:74` |
| `apollo-events` | `/wp-json/apollo/v1/eventos/(?P<id>\d+)/banner` | CREATABLE DELETABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-events/src/API/EventsController.php:291` |
| `apollo-events` | `/wp-json/apollo/v1/eventos/(?P<id>\d+)/clonar` | CREATABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-events/src/API/EventsController.php:328` |
| `apollo-events` | `/wp-json/apollo/v1/eventos/(?P<id>\d+)/djs` | READABLE CREATABLE DELETABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-events/src/API/EventsController.php:197` |
| `apollo-events` | `/wp-json/apollo/v1/eventos/(?P<id>\d+)/estatisticas` | READABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-events/src/API/EventsController.php:340` |
| `apollo-events` | `/wp-json/apollo/v1/eventos/(?P<id>\d+)/notificar-warmup` | CREATABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-events/src/API/EventsController.php:423` |
| `apollo-events` | `/wp-json/apollo/v1/eventos/(?P<id>\d+)/participantes` | READABLE CREATABLE DELETABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-events/src/API/EventsController.php:351` |
| `apollo-events` | `/wp-json/apollo/v1/eventos/(?P<id>\d+)/participantes/(?P<user_id>\d+)/check-in` | CREATABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-events/src/API/EventsController.php:398` |
| `apollo-events` | `/wp-json/apollo/v1/eventos/buscar` | READABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-events/src/API/EventsController.php:238` |
| `apollo-events` | `/wp-json/apollo/v1/eventos/calendario/(?P<year>\d{4})/(?P<month>\d{1,2})` | READABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-events/src/API/EventsController.php:266` |
| `apollo-events` | `/wp-json/apollo/v1/eventos/hoje` | READABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-events/src/API/EventsController.php:129` |
| `apollo-events` | `/wp-json/apollo/v1/eventos/importar-url` | CREATABLE | **MISSING** | class_const | `plugins/apollo-events/src/Import/UrlImportController.php:77` |
| `apollo-events` | `/wp-json/apollo/v1/eventos/importar-url/preview` | CREATABLE | **MISSING** | class_const | `plugins/apollo-events/src/Import/UrlImportController.php:66` |
| `apollo-events` | `/wp-json/apollo/v1/eventos/lote` | CREATABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-events/src/API/EventsController.php:502` |
| `apollo-events` | `/wp-json/apollo/v1/eventos/meus` | READABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-events/src/API/EventsController.php:447` |
| `apollo-events` | `/wp-json/apollo/v1/eventos/meus/rsvp` | READABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-events/src/API/EventsController.php:472` |
| `apollo-events` | `/wp-json/apollo/v1/eventos/passados` | READABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-events/src/API/EventsController.php:117` |
| `apollo-events` | `/wp-json/apollo/v1/eventos/por-data/(?P<date>[\d-]+)` | READABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-events/src/API/EventsController.php:140` |
| `apollo-events` | `/wp-json/apollo/v1/eventos/por-dj/(?P<dj_id>\d+)` | READABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-events/src/API/EventsController.php:179` |
| `apollo-events` | `/wp-json/apollo/v1/eventos/por-local/(?P<loc_id>\d+)` | READABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-events/src/API/EventsController.php:161` |
| `apollo-events` | `/wp-json/apollo/v1/eventos/proximos` | READABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-events/src/API/EventsController.php:105` |
| `apollo-events` | `/wp-json/apollo/v1/eventos/validar-imagem` | CREATABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-events/src/API/EventsController.php:308` |
| `apollo-events` | `/wp-json/apollo/v1/iframe/(?P<slug>[a-z0-9-]+)` | GET | **MISSING** | literal | `plugins/apollo-events/includes/iframe-proxy.php:88` |
| `apollo-fav` | _'/' . self::BASE . '/(?P<post_id>\d+)'_ | DELETABLE | **MISSING** | class_const | `plugins/apollo-fav/includes/class-rest-controller.php:93` |
| `apollo-fav` | _'/' . self::BASE . '/check/(?P<post_id>\d+)'_ | READABLE | **MISSING** | class_const | `plugins/apollo-fav/includes/class-rest-controller.php:147` |
| `apollo-fav` | _'/' . self::BASE . '/count/(?P<post_id>\d+)'_ | READABLE | **MISSING** | class_const | `plugins/apollo-fav/includes/class-rest-controller.php:129` |
| `apollo-fav` | _'/' . self::BASE . '/toggle/(?P<post_id>\d+)'_ | CREATABLE | **MISSING** | class_const | `plugins/apollo-fav/includes/class-rest-controller.php:111` |
| `apollo-fav` | `/wp-json/apollo/v1/favs` | READABLE CREATABLE | **MISSING** | class_const | `plugins/apollo-fav/includes/class-rest-controller.php:43` |
| `apollo-groups` | `/wp-json/apollo/v1/groups` | GET POST | **MISSING** | local_assign | `plugins/apollo-groups/src/Plugin.php:228` |
| `apollo-groups` | `/wp-json/apollo/v1/groups/(?P<id>\d+)` | GET PUT DELETE | **MISSING** | local_assign | `plugins/apollo-groups/src/Plugin.php:245` |
| `apollo-groups` | `/wp-json/apollo/v1/groups/(?P<id>\d+)/avatar` | POST DELETE | **MISSING** | local_assign | `plugins/apollo-groups/src/Plugin.php:519` |
| `apollo-groups` | `/wp-json/apollo/v1/groups/(?P<id>\d+)/bans` | GET | **MISSING** | local_assign | `plugins/apollo-groups/src/Plugin.php:387` |
| `apollo-groups` | `/wp-json/apollo/v1/groups/(?P<id>\d+)/cover` | POST DELETE | **MISSING** | local_assign | `plugins/apollo-groups/src/Plugin.php:537` |
| `apollo-groups` | `/wp-json/apollo/v1/groups/(?P<id>\d+)/feed` | GET | **MISSING** | local_assign | `plugins/apollo-groups/src/Plugin.php:498` |
| `apollo-groups` | `/wp-json/apollo/v1/groups/(?P<id>\d+)/invitations` | GET POST | **MISSING** | local_assign | `plugins/apollo-groups/src/Plugin.php:399` |
| `apollo-groups` | `/wp-json/apollo/v1/groups/(?P<id>\d+)/invitations/accept` | POST | **MISSING** | local_assign | `plugins/apollo-groups/src/Plugin.php:416` |
| `apollo-groups` | `/wp-json/apollo/v1/groups/(?P<id>\d+)/invitations/reject` | POST | **MISSING** | local_assign | `plugins/apollo-groups/src/Plugin.php:426` |
| `apollo-groups` | `/wp-json/apollo/v1/groups/(?P<id>\d+)/join` | POST | **MISSING** | local_assign | `plugins/apollo-groups/src/Plugin.php:279` |
| `apollo-groups` | `/wp-json/apollo/v1/groups/(?P<id>\d+)/leave` | POST | **MISSING** | local_assign | `plugins/apollo-groups/src/Plugin.php:289` |
| `apollo-groups` | `/wp-json/apollo/v1/groups/(?P<id>\d+)/members` | GET | **MISSING** | local_assign | `plugins/apollo-groups/src/Plugin.php:269` |
| `apollo-groups` | `/wp-json/apollo/v1/groups/(?P<id>\d+)/members/(?P<user_id>\d+)` | DELETE | **MISSING** | local_assign | `plugins/apollo-groups/src/Plugin.php:377` |
| `apollo-groups` | `/wp-json/apollo/v1/groups/(?P<id>\d+)/members/(?P<user_id>\d+)/ban` | POST DELETE | **MISSING** | local_assign | `plugins/apollo-groups/src/Plugin.php:360` |
| `apollo-groups` | `/wp-json/apollo/v1/groups/(?P<id>\d+)/members/(?P<user_id>\d+)/demote` | POST | **MISSING** | local_assign | `plugins/apollo-groups/src/Plugin.php:350` |
| `apollo-groups` | `/wp-json/apollo/v1/groups/(?P<id>\d+)/members/(?P<user_id>\d+)/promote` | POST | **MISSING** | local_assign | `plugins/apollo-groups/src/Plugin.php:340` |
| `apollo-groups` | `/wp-json/apollo/v1/groups/(?P<id>\d+)/requests` | GET POST | **MISSING** | local_assign | `plugins/apollo-groups/src/Plugin.php:449` |
| `apollo-groups` | `/wp-json/apollo/v1/groups/(?P<id>\d+)/requests/(?P<user_id>\d+)/accept` | POST | **MISSING** | local_assign | `plugins/apollo-groups/src/Plugin.php:466` |
| `apollo-groups` | `/wp-json/apollo/v1/groups/(?P<id>\d+)/requests/(?P<user_id>\d+)/reject` | POST | **MISSING** | local_assign | `plugins/apollo-groups/src/Plugin.php:476` |
| `apollo-groups` | `/wp-json/apollo/v1/groups/comunas` | GET | **MISSING** | local_assign | `plugins/apollo-groups/src/Plugin.php:299` |
| `apollo-groups` | `/wp-json/apollo/v1/groups/my` | GET | **MISSING** | local_assign | `plugins/apollo-groups/src/Plugin.php:328` |
| `apollo-groups` | `/wp-json/apollo/v1/groups/nucleos` | GET | **MISSING** | local_assign | `plugins/apollo-groups/src/Plugin.php:312` |
| `apollo-groups` | `/wp-json/apollo/v1/groups/search` | GET | **MISSING** | local_assign | `plugins/apollo-groups/src/Plugin.php:487` |
| `apollo-groups` | `/wp-json/apollo/v1/my/group-invitations` | GET | **MISSING** | local_assign | `plugins/apollo-groups/src/Plugin.php:437` |
| `apollo-hub` | `/wp-json/apollo/v1/hubs` | READABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-hub/src/API/HubController.php:41` |
| `apollo-hub` | `/wp-json/apollo/v1/hubs/(?P<username>[a-zA-Z0-9._-]+)` | READABLE EDITABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-hub/src/API/HubController.php:67` |
| `apollo-hub` | `/wp-json/apollo/v1/hubs/(?P<username>[a-zA-Z0-9._-]+)/blocks` | READABLE EDITABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-hub/src/API/HubController.php:144` |
| `apollo-hub` | `/wp-json/apollo/v1/hubs/(?P<username>[a-zA-Z0-9._-]+)/links` | READABLE EDITABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-hub/src/API/HubController.php:93` |
| `apollo-hub` | `/wp-json/apollo/v1/hubs/(?P<username>[a-zA-Z0-9._-]+)/share/(?P<post_id>\d+)` | READABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-hub/src/API/HubController.php:195` |
| `apollo-hub` | `/wp-json/apollo/v1/hubs/me` | READABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-hub/src/API/HubController.php:218` |
| `apollo-journal` | `/wp-json/apollo/v1/journal/news` | READABLE | **MISSING** | constant_define | `plugins/apollo-journal/src/API/PostsController.php:47` |
| `apollo-journal` | `/wp-json/apollo/v1/journal/notas` | READABLE | **MISSING** | constant_define | `plugins/apollo-journal/src/API/PostsController.php:61` |
| `apollo-journal` | `/wp-json/apollo/v1/journal/posts` | READABLE | **MISSING** | constant_define | `plugins/apollo-journal/src/API/PostsController.php:33` |
| `apollo-loc` | _'/' . $this->rest_base_ | READABLE CREATABLE | **MISSING** | **unresolved** | `plugins/apollo-loc/src/API/LocalsController.php:50` |
| `apollo-loc` | _'/' . $this->rest_base . '/(?P<id>[\d]+)'_ | READABLE EDITABLE DELETABLE | **MISSING** | **unresolved** | `plugins/apollo-loc/src/API/LocalsController.php:72` |
| `apollo-loc` | _'/' . $this->rest_base . '/proximos'_ | READABLE | **MISSING** | **unresolved** | `plugins/apollo-loc/src/API/LocalsController.php:105` |
| `apollo-loc` | `? /` | READABLE CREATABLE | **MISSING** | **unresolved** | `plugins/apollo-loc/src/API/Router.php:58` |
| `apollo-loc` | _$rb . '/(?P<id>[\d]+)'_ | READABLE EDITABLE DELETABLE | **MISSING** | **unresolved** | `plugins/apollo-loc/src/API/Router.php:90` |
| `apollo-loc` | _$rb . '/proximos'_ | READABLE | **MISSING** | **unresolved** | `plugins/apollo-loc/src/API/Router.php:41` |
| `apollo-login` | `? /activity/log` | CREATABLE | **MISSING** | **unresolved** | `plugins/apollo-login/src/API/ActivityLogController.php:78` |
| `apollo-login` | `? /activity/log` | READABLE | **MISSING** | **unresolved** | `plugins/apollo-login/src/API/ActivityLogController.php:107` |
| `apollo-login` | `? /app/auth` | CREATABLE | **MISSING** | **unresolved** | `plugins/apollo-login/src/API/AppAuthController.php:90` |
| `apollo-login` | `? /app/revoke` | CREATABLE | **MISSING** | **unresolved** | `plugins/apollo-login/src/API/AppAuthController.php:155` |
| `apollo-login` | `? /app/verify` | READABLE | **MISSING** | **unresolved** | `plugins/apollo-login/src/API/AppAuthController.php:124` |
| `apollo-login` | `? /auth/check-email` | READABLE | **MISSING** | **unresolved** | `plugins/apollo-login/src/API/AuthController.php:261` |
| `apollo-login` | `? /auth/check-username` | READABLE | **MISSING** | **unresolved** | `plugins/apollo-login/src/API/AuthController.php:243` |
| `apollo-login` | `? /auth/login` | CREATABLE | **MISSING** | **unresolved** | `plugins/apollo-login/src/API/AuthController.php:60` |
| `apollo-login` | `? /auth/logout` | CREATABLE | **MISSING** | **unresolved** | `plugins/apollo-login/src/API/AuthController.php:214` |
| `apollo-login` | `? /auth/register` | CREATABLE | **MISSING** | **unresolved** | `plugins/apollo-login/src/API/AuthController.php:88` |
| `apollo-login` | `? /auth/resend-verification` | CREATABLE | **MISSING** | **unresolved** | `plugins/apollo-login/src/API/AuthController.php:325` |
| `apollo-login` | `? /auth/reset-confirm` | CREATABLE | **MISSING** | **unresolved** | `plugins/apollo-login/src/API/AuthController.php:279` |
| `apollo-login` | `? /auth/reset-request` | CREATABLE | **MISSING** | **unresolved** | `plugins/apollo-login/src/API/AuthController.php:225` |
| `apollo-login` | `? /auth/verify-email` | CREATABLE | **MISSING** | **unresolved** | `plugins/apollo-login/src/API/AuthController.php:302` |
| `apollo-login` | `? /dj/config` | READABLE | **MISSING** | **unresolved** | `plugins/apollo-login/src/API/AppAuthController.php:186` |
| `apollo-login` | `? /dj/permissions` | READABLE | **MISSING** | **unresolved** | `plugins/apollo-login/src/API/AppAuthController.php:197` |
| `apollo-login` | `? /quiz/questions` | READABLE | **MISSING** | **unresolved** | `plugins/apollo-login/src/API/QuizController.php:48` |
| `apollo-login` | `? /quiz/submit` | CREATABLE | **MISSING** | **unresolved** | `plugins/apollo-login/src/API/QuizController.php:67` |
| `apollo-login` | `? /security/attempts` | READABLE | **MISSING** | **unresolved** | `plugins/apollo-login/src/API/SecurityController.php:55` |
| `apollo-login` | `? /security/rewrites` | READABLE | **MISSING** | **unresolved** | `plugins/apollo-login/src/API/SecurityController.php:44` |
| `apollo-login` | `? /simon/highscores` | READABLE | **MISSING** | **unresolved** | `plugins/apollo-login/src/API/QuizController.php:128` |
| `apollo-login` | `? /simon/submit` | CREATABLE | **MISSING** | **unresolved** | `plugins/apollo-login/src/API/QuizController.php:95` |
| `apollo-login` | `/wp-json/apollo/v1/auth/token` | POST | **MISSING** | local_assign_ternary_fallback | `plugins/apollo-login/src/Security/JWTAuth.php:121` |
| `apollo-login` | `/wp-json/apollo/v1/auth/token/refresh` | POST | **MISSING** | local_assign_ternary_fallback | `plugins/apollo-login/src/Security/JWTAuth.php:139` |
| `apollo-login` | `/wp-json/apollo/v1/auth/token/revoke` | POST | **MISSING** | local_assign_ternary_fallback | `plugins/apollo-login/src/Security/JWTAuth.php:152` |
| `apollo-login` | `/wp-json/apollo/v1/csp-report` | POST | **MISSING** | local_assign_ternary_fallback | `plugins/apollo-login/src/Security/SecurityHeaders.php:249` |
| `apollo-maps` | `/wp-json/apollo/v1/map/explorer` | READABLE | **MISSING** | constant_define | `plugins/apollo-maps/src/API/ExplorerController.php:28` |
| `apollo-membership` | `/wp-json/apollo/v1/achievements` | READABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-membership/src/API/AchievementsController.php:35` |
| `apollo-membership` | `/wp-json/apollo/v1/achievements/(?P<id>\d+)` | READABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-membership/src/API/AchievementsController.php:62` |
| `apollo-membership` | `/wp-json/apollo/v1/leaderboard` | READABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-membership/src/API/LeaderboardController.php:31` |
| `apollo-membership` | `/wp-json/apollo/v1/leaderboard/user/(?P<id>\d+)` | READABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-membership/src/API/LeaderboardController.php:48` |
| `apollo-membership` | `/wp-json/apollo/v1/membership-badge` | READABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-membership/src/API/LeaderboardController.php:82` |
| `apollo-membership` | `/wp-json/apollo/v1/membership-badge` | CREATABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-membership/src/API/LeaderboardController.php:99` |
| `apollo-membership` | `/wp-json/apollo/v1/membership/achievements/award` | CREATABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-membership/src/API/AchievementsController.php:106` |
| `apollo-membership` | `/wp-json/apollo/v1/membership/achievements/revoke` | CREATABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-membership/src/API/AchievementsController.php:128` |
| `apollo-membership` | `/wp-json/apollo/v1/membership/evidence/(?P<achievement_id>\d+)` | READABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-membership/src/API/AchievementsController.php:155` |
| `apollo-membership` | `/wp-json/apollo/v1/membership/ranks/award` | CREATABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-membership/src/API/RanksController.php:76` |
| `apollo-membership` | `/wp-json/apollo/v1/membership/triggers` | READABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-membership/src/API/TriggersController.php:29` |
| `apollo-membership` | `/wp-json/apollo/v1/membership/verify/(?P<hash>[a-zA-Z0-9]+)` | READABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-membership/src/API/AchievementsController.php:177` |
| `apollo-membership` | `/wp-json/apollo/v1/points` | READABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-membership/src/API/PointsController.php:33` |
| `apollo-membership` | `/wp-json/apollo/v1/points/award` | CREATABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-membership/src/API/PointsController.php:82` |
| `apollo-membership` | `/wp-json/apollo/v1/points/deduct` | CREATABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-membership/src/API/PointsController.php:109` |
| `apollo-membership` | `/wp-json/apollo/v1/points/history` | READABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-membership/src/API/PointsController.php:50` |
| `apollo-membership` | `/wp-json/apollo/v1/points/reset` | CREATABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-membership/src/API/PointsController.php:136` |
| `apollo-membership` | `/wp-json/apollo/v1/ranks` | READABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-membership/src/API/RanksController.php:32` |
| `apollo-membership` | `/wp-json/apollo/v1/ranks/(?P<id>\d+)` | READABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-membership/src/API/RanksController.php:42` |
| `apollo-membership` | `/wp-json/apollo/v1/report` | POST | **MISSING** | property_assign_constant_define | `plugins/apollo-membership/src/API/ReportController.php:53` |
| `apollo-membership` | `/wp-json/apollo/v1/user-achievements` | READABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-membership/src/API/AchievementsController.php:79` |
| `apollo-membership` | `/wp-json/apollo/v1/user-rank` | READABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-membership/src/API/RanksController.php:59` |
| `apollo-membership` | `/wp-json/apollo/v1/user-summary` | READABLE | **MISSING** | property_assign_constant_define | `plugins/apollo-membership/src/API/LeaderboardController.php:65` |
| `apollo-mod` | `/wp-json/apollo/v1/mod/log` | GET | **MISSING** | local_assign | `plugins/apollo-mod/src/Plugin.php:106` |
| `apollo-mod` | `/wp-json/apollo/v1/mod/queue` | GET | **MISSING** | local_assign | `plugins/apollo-mod/src/Plugin.php:54` |
| `apollo-mod` | `/wp-json/apollo/v1/mod/queue/(?P<id>\d+)/approve` | POST | **MISSING** | local_assign | `plugins/apollo-mod/src/Plugin.php:70` |
| `apollo-mod` | `/wp-json/apollo/v1/mod/queue/(?P<id>\d+)/flag` | POST | **MISSING** | local_assign | `plugins/apollo-mod/src/Plugin.php:94` |
| `apollo-mod` | `/wp-json/apollo/v1/mod/queue/(?P<id>\d+)/reject` | POST | **MISSING** | local_assign | `plugins/apollo-mod/src/Plugin.php:82` |
| `apollo-mod` | `/wp-json/apollo/v1/mod/report` | POST | **MISSING** | local_assign | `plugins/apollo-mod/src/Plugin.php:127` |
| `apollo-mod` | `/wp-json/apollo/v1/mod/stats` | GET | **MISSING** | local_assign | `plugins/apollo-mod/src/Plugin.php:116` |
| `apollo-notif` | `/wp-json/apollo/v1/notifications` | GET | **MISSING** | local_assign | `plugins/apollo-notif/src/Plugin.php:157` |
| `apollo-notif` | `/wp-json/apollo/v1/notifications/(?P<id>\d+)` | DELETE | **MISSING** | local_assign | `plugins/apollo-notif/src/Plugin.php:257` |
| `apollo-notif` | `/wp-json/apollo/v1/notifications/(?P<id>\d+)/displayed` | POST | **MISSING** | local_assign | `plugins/apollo-notif/src/Plugin.php:283` |
| `apollo-notif` | `/wp-json/apollo/v1/notifications/(?P<id>\d+)/read` | POST | **MISSING** | local_assign | `plugins/apollo-notif/src/Plugin.php:199` |
| `apollo-notif` | `/wp-json/apollo/v1/notifications/preferences` | GET PUT | **MISSING** | local_assign | `plugins/apollo-notif/src/Plugin.php:235` |
| `apollo-notif` | `/wp-json/apollo/v1/notifications/preferences/snooze` | POST | **MISSING** | local_assign | `plugins/apollo-notif/src/Plugin.php:296` |
| `apollo-notif` | `/wp-json/apollo/v1/notifications/preferences/snooze` | DELETE | **MISSING** | local_assign | `plugins/apollo-notif/src/Plugin.php:319` |
| `apollo-notif` | `/wp-json/apollo/v1/notifications/push/subscribe` | POST | **MISSING** | local_assign | `plugins/apollo-notif/src/Plugin.php:338` |
| `apollo-notif` | `/wp-json/apollo/v1/notifications/push/unsubscribe` | DELETE | **MISSING** | local_assign | `plugins/apollo-notif/src/Plugin.php:362` |
| `apollo-notif` | `/wp-json/apollo/v1/notifications/push/vapid-public-key` | GET | **MISSING** | local_assign | `plugins/apollo-notif/src/Plugin.php:381` |
| `apollo-notif` | `/wp-json/apollo/v1/notifications/read` | DELETE | **MISSING** | local_assign | `plugins/apollo-notif/src/Plugin.php:270` |
| `apollo-notif` | `/wp-json/apollo/v1/notifications/read-all` | POST | **MISSING** | local_assign | `plugins/apollo-notif/src/Plugin.php:211` |
| `apollo-notif` | `/wp-json/apollo/v1/notifications/unread-count` | GET | **MISSING** | local_assign | `plugins/apollo-notif/src/Plugin.php:223` |
| `apollo-pane-engine` | _'/pane/section/' . $safe_slug . '/(?P<id>\d+)'_ | GET | **MISSING** | literal | `plugins/apollo-pane-engine/includes/section-renderer.php:75` |
| `apollo-pane-engine` | `/wp-json/apollo/v1/pane/fragment` | GET | **MISSING** | literal | `plugins/apollo-pane-engine/includes/fragment-helper.php:166` |
| `apollo-pane-engine` | `/wp-json/apollo/v1/pane/plugins` | GET | **MISSING** | literal | `plugins/apollo-pane-engine/includes/fragment-helper.php:126` |
| `apollo-pane-engine` | `/wp-json/apollo/v1/pane/section/(?P<slug>[a-z0-9-]+)` | GET | **MISSING** | literal | `plugins/apollo-pane-engine/includes/section-renderer.php:17` |
| `apollo-pane-engine` | `/wp-json/apollo/v1/pane/section/chat-thread/(?P<thread_id>\d+)` | GET | **MISSING** | literal | `plugins/apollo-pane-engine/includes/section-renderer.php:36` |
| `apollo-radio` | _'/' . $this->rest_base . '/now'_ | GET | **MISSING** | property_default | `plugins/apollo-radio/src/API/RadioController.php:43` |
| `apollo-radio` | _'/' . $this->rest_base . '/playlist'_ | GET | **MISSING** | property_default | `plugins/apollo-radio/src/API/RadioController.php:52` |
| `apollo-radio` | _'/' . $this->rest_base . '/status'_ | GET | **MISSING** | property_default | `plugins/apollo-radio/src/API/RadioController.php:34` |
| `apollo-radio` | _'/' . $this->rest_base . '/stream'_ | GET | **MISSING** | property_default | `plugins/apollo-radio/src/API/RadioController.php:68` |
| `apollo-remind` | `/wp-json/apollo/v1/remind` | GET | **MISSING** | property_default | `plugins/apollo-remind/src/API/RemindersController.php:14` |
| `apollo-remind` | `/wp-json/apollo/v1/remind` | POST | **MISSING** | property_default | `plugins/apollo-remind/src/API/RemindersController.php:26` |
| `apollo-remind` | `/wp-json/apollo/v1/remind/(?P<id>\d+)` | GET | **MISSING** | property_default | `plugins/apollo-remind/src/API/RemindersController.php:33` |
| `apollo-remind` | `/wp-json/apollo/v1/remind/(?P<id>\d+)` | PUT | **MISSING** | property_default | `plugins/apollo-remind/src/API/RemindersController.php:40` |
| `apollo-remind` | `/wp-json/apollo/v1/remind/(?P<id>\d+)` | DELETE | **MISSING** | property_default | `plugins/apollo-remind/src/API/RemindersController.php:47` |
| `apollo-remind` | `/wp-json/apollo/v1/remind/calendar` | GET | **MISSING** | property_default | `plugins/apollo-remind/src/API/RemindersController.php:54` |
| `apollo-remind` | `/wp-json/apollo/v1/remind/channels` | GET | **MISSING** | property_default | `plugins/apollo-remind/src/API/RemindersController.php:79` |
| `apollo-remind` | `/wp-json/apollo/v1/remind/push/subscribe` | POST | **MISSING** | property_default | `plugins/apollo-remind/src/API/PushController.php:18` |
| `apollo-remind` | `/wp-json/apollo/v1/remind/push/unsubscribe` | DELETE | **MISSING** | property_default | `plugins/apollo-remind/src/API/PushController.php:25` |
| `apollo-remind` | `/wp-json/apollo/v1/remind/push/vapid-key` | GET | **MISSING** | property_default | `plugins/apollo-remind/src/API/PushController.php:32` |
| `apollo-remind` | `/wp-json/apollo/v1/remind/telegram/link` | POST | **MISSING** | property_default | `plugins/apollo-remind/src/API/RemindersController.php:65` |
| `apollo-remind` | `/wp-json/apollo/v1/remind/telegram/status` | GET | **MISSING** | property_default | `plugins/apollo-remind/src/API/RemindersController.php:72` |
| `apollo-remind` | `/wp-json/apollo/v1/remind/telegram/webhook` | POST | **MISSING** | property_default | `plugins/apollo-remind/src/API/TelegramWebhook.php:23` |
| `apollo-scheduler` | _'/' . $this->rest_base . '/agents/assign'_ | CREATABLE | **MISSING** | **unresolved** | `plugins/apollo-scheduler/src/API/ManagerController.php:38` |
| `apollo-scheduler` | _'/' . $this->rest_base . '/availability'_ | READABLE | **MISSING** | **unresolved** | `plugins/apollo-scheduler/src/API/AvailabilityController.php:24` |
| `apollo-scheduler` | _'/' . $this->rest_base . '/availability/block'_ | CREATABLE | **MISSING** | **unresolved** | `plugins/apollo-scheduler/src/API/ManagerController.php:56` |
| `apollo-scheduler` | _'/' . $this->rest_base . '/availability/grid'_ | READABLE | **MISSING** | **unresolved** | `plugins/apollo-scheduler/src/API/AvailabilityController.php:37` |
| `apollo-scheduler` | _'/' . $this->rest_base . '/book'_ | CREATABLE | **MISSING** | **unresolved** | `plugins/apollo-scheduler/src/API/BookingController.php:27` |
| `apollo-scheduler` | _'/' . $this->rest_base . '/ical/(?P<id>\d+)'_ | READABLE | **MISSING** | **unresolved** | `plugins/apollo-scheduler/src/API/ICalController.php:23` |
| `apollo-scheduler` | _'/' . $this->rest_base . '/nucleo/(?P<nucleo_id>\d+)/catalog'_ | READABLE | **MISSING** | **unresolved** | `plugins/apollo-scheduler/src/API/ManagerController.php:26` |
| `apollo-scheduler` | _'/' . $this->rest_base . '/reschedule/(?P<id>\d+)'_ | EDITABLE | **MISSING** | **unresolved** | `plugins/apollo-scheduler/src/API/BookingController.php:49` |
| `apollo-seo` | `/wp-json/apollo/v1/seo/home` | GET | **MISSING** | local_assign_ternary_fallback | `plugins/apollo-seo/src/Plugin.php:479` |
| `apollo-seo` | `/wp-json/apollo/v1/seo/post/(?P<id>\d+)` | GET | **MISSING** | local_assign_ternary_fallback | `plugins/apollo-seo/src/Plugin.php:447` |
| `apollo-seo` | `/wp-json/apollo/v1/seo/term/(?P<id>\d+)` | GET | **MISSING** | local_assign_ternary_fallback | `plugins/apollo-seo/src/Plugin.php:463` |
| `apollo-sheets` | _'/' . $this->rest_base_ | READABLE CREATABLE | **MISSING** | **unresolved** | `plugins/apollo-sheets/src/API/SheetsController.php:40` |
| `apollo-sheets` | _'/' . $this->rest_base . '/(?P<id>[\w]+)'_ | READABLE EDITABLE DELETABLE | **MISSING** | **unresolved** | `plugins/apollo-sheets/src/API/SheetsController.php:60` |
| `apollo-sheets` | _'/' . $this->rest_base . '/(?P<id>[\w]+)/copy'_ | CREATABLE | **MISSING** | **unresolved** | `plugins/apollo-sheets/src/API/SheetsController.php:100` |
| `apollo-sheets` | _'/' . $this->rest_base . '/(?P<id>[\w]+)/export'_ | READABLE | **MISSING** | **unresolved** | `plugins/apollo-sheets/src/API/SheetsController.php:135` |
| `apollo-sheets` | _'/' . $this->rest_base . '/(?P<id>[\w]+)/preview'_ | READABLE | **MISSING** | **unresolved** | `plugins/apollo-sheets/src/API/SheetsController.php:163` |
| `apollo-sheets` | _'/' . $this->rest_base . '/import'_ | CREATABLE | **MISSING** | **unresolved** | `plugins/apollo-sheets/src/API/SheetsController.php:121` |
| `apollo-sign` | _'/' . $this->rest_base_ | POST | **MISSING** | property_default | `plugins/apollo-sign/src/API/SignController.php:35` |
| `apollo-sign` | _'/' . $this->rest_base . '/(?P<hash>[a-f0-9]{64})'_ | GET | **MISSING** | property_default | `plugins/apollo-sign/src/API/VerifyController.php:46` |
| `apollo-sign` | _'/' . $this->rest_base . '/(?P<id>\d+)'_ | GET | **MISSING** | property_default | `plugins/apollo-sign/src/API/SignController.php:53` |
| `apollo-sign` | _'/' . $this->rest_base . '/(?P<id>\d+)/audit'_ | GET | **MISSING** | property_default | `plugins/apollo-sign/src/API/SignController.php:89` |
| `apollo-sign` | _'/' . $this->rest_base . '/(?P<id>\d+)/placement'_ | POST | **MISSING** | property_default | `plugins/apollo-sign/src/API/SignController.php:107` |
| `apollo-sign` | _'/' . $this->rest_base . '/(?P<id>\d+)/placement'_ | GET | **MISSING** | property_default | `plugins/apollo-sign/src/API/SignController.php:155` |
| `apollo-sign` | _'/' . $this->rest_base . '/(?P<id>\d+)/sign'_ | POST | **MISSING** | property_default | `plugins/apollo-sign/src/API/SignController.php:71` |
| `apollo-sign` | _'/' . $this->rest_base . '/doc/(?P<doc_id>[\d]+)'_ | READABLE | **MISSING** | property_default | `plugins/apollo-sign/src/API/RequestController.php:66` |
| `apollo-sign` | _'/' . $this->rest_base . '/request'_ | CREATABLE | **MISSING** | property_default | `plugins/apollo-sign/src/API/RequestController.php:39` |
| `apollo-sign` | _'/' . $this->rest_base . '/users'_ | READABLE | **MISSING** | property_default | `plugins/apollo-sign/src/API/RequestController.php:53` |
| `apollo-social` | `/wp-json/apollo/v1/activity/(?P<id>\d+)` | DELETE | **MISSING** | local_assign | `plugins/apollo-social/src/Plugin.php:123` |
| `apollo-social` | `/wp-json/apollo/v1/activity/(?P<id>\d+)` | PUT | **MISSING** | local_assign | `plugins/apollo-social/src/Plugin.php:166` |
| `apollo-social` | `/wp-json/apollo/v1/activity/(?P<id>\d+)/replies` | GET POST | **MISSING** | local_assign | `plugins/apollo-social/src/Plugin.php:147` |
| `apollo-social` | `/wp-json/apollo/v1/activity/(?P<id>\d+)/spam` | POST DELETE | **MISSING** | local_assign | `plugins/apollo-social/src/Plugin.php:179` |
| `apollo-social` | `/wp-json/apollo/v1/block/(?P<user_id>\d+)` | POST DELETE | **MISSING** | local_assign | `plugins/apollo-social/src/Plugin.php:213` |
| `apollo-social` | `/wp-json/apollo/v1/blocks` | GET | **MISSING** | local_assign | `plugins/apollo-social/src/Plugin.php:201` |
| `apollo-social` | `/wp-json/apollo/v1/depo/(?P<user_id>\d+)` | GET | **MISSING** | local_assign | `plugins/apollo-social/src/Plugin.php:136` |
| `apollo-social` | `/wp-json/apollo/v1/feed` | GET | **MISSING** | local_assign | `plugins/apollo-social/src/Plugin.php:85` |
| `apollo-social` | `/wp-json/apollo/v1/feed/post` | POST | **MISSING** | local_assign | `plugins/apollo-social/src/Plugin.php:111` |
| `apollo-social` | `/wp-json/apollo/v1/members` | GET | **MISSING** | local_assign | `plugins/apollo-social/src/Plugin.php:235` |
| `apollo-social` | `/wp-json/apollo/v1/members/suggestions` | GET | **MISSING** | local_assign | `plugins/apollo-social/src/Plugin.php:264` |
| `apollo-statistics` | _'/' . $this->rest_base . '/(?P<user_id>\d+)'_ | READABLE | **MISSING** | **unresolved** | `plugins/apollo-statistics/src/API/ProfileController.php:32` |
| `apollo-statistics` | _'/' . $this->rest_base . '/batch'_ | CREATABLE | **MISSING** | **unresolved** | `plugins/apollo-statistics/src/API/TrackController.php:88` |
| `apollo-statistics` | _'/' . $this->rest_base . '/click'_ | CREATABLE | **MISSING** | **unresolved** | `plugins/apollo-statistics/src/API/TrackController.php:48` |
| `apollo-statistics` | _'/' . $this->rest_base . '/dashboard'_ | READABLE | **MISSING** | **unresolved** | `plugins/apollo-statistics/src/API/StatsController.php:67` |
| `apollo-statistics` | _'/' . $this->rest_base . '/event'_ | CREATABLE | **MISSING** | **unresolved** | `plugins/apollo-statistics/src/API/TrackController.php:68` |
| `apollo-statistics` | _'/' . $this->rest_base . '/metric/(?P<slug>[a-z0-9_-]+)'_ | READABLE | **MISSING** | **unresolved** | `plugins/apollo-statistics/src/API/StatsController.php:42` |
| `apollo-statistics` | _'/' . $this->rest_base . '/metric/(?P<slug>[a-z0-9_-]+)/toggle'_ | EDITABLE | **MISSING** | **unresolved** | `plugins/apollo-statistics/src/API/StatsController.php:52` |
| `apollo-statistics` | _'/' . $this->rest_base . '/metrics'_ | READABLE | **MISSING** | **unresolved** | `plugins/apollo-statistics/src/API/StatsController.php:33` |
| `apollo-statistics` | _'/' . $this->rest_base . '/pageview'_ | CREATABLE | **MISSING** | **unresolved** | `plugins/apollo-statistics/src/API/TrackController.php:38` |
| `apollo-statistics` | _'/' . $this->rest_base . '/profile/(?P<user_id>\d+)'_ | READABLE | **MISSING** | **unresolved** | `plugins/apollo-statistics/src/API/StatsController.php:77` |
| `apollo-statistics` | _'/' . $this->rest_base . '/radio'_ | CREATABLE | **MISSING** | **unresolved** | `plugins/apollo-statistics/src/API/TrackController.php:78` |
| `apollo-statistics` | _'/' . $this->rest_base . '/session'_ | CREATABLE | **MISSING** | **unresolved** | `plugins/apollo-statistics/src/API/TrackController.php:58` |
| `apollo-statistics` | _'/' . $this->rest_base . '/visibility'_ | EDITABLE | **MISSING** | **unresolved** | `plugins/apollo-statistics/src/API/ProfileController.php:45` |
| `apollo-statistics` | `/wp-json/apollo/v1/stats/chart` | READABLE | **MISSING** | class_const | `plugins/apollo-statistics/includes/class-rest-controller.php:174` |
| `apollo-statistics` | `/wp-json/apollo/v1/stats/content` | READABLE | **MISSING** | class_const | `plugins/apollo-statistics/includes/class-rest-controller.php:78` |
| `apollo-statistics` | `/wp-json/apollo/v1/stats/events` | READABLE | **MISSING** | class_const | `plugins/apollo-statistics/includes/class-rest-controller.php:54` |
| `apollo-statistics` | `/wp-json/apollo/v1/stats/export` | READABLE | **MISSING** | class_const | `plugins/apollo-statistics/includes/class-rest-controller.php:106` |
| `apollo-statistics` | `/wp-json/apollo/v1/stats/health` | READABLE | **MISSING** | class_const | `plugins/apollo-statistics/includes/class-rest-controller.php:132` |
| `apollo-statistics` | `/wp-json/apollo/v1/stats/overview` | READABLE | **MISSING** | class_const | `plugins/apollo-statistics/includes/class-rest-controller.php:34` |
| `apollo-statistics` | `/wp-json/apollo/v1/stats/trend` | READABLE | **MISSING** | class_const | `plugins/apollo-statistics/includes/class-rest-controller.php:143` |
| `apollo-statistics` | `/wp-json/apollo/v1/stats/users` | READABLE | **MISSING** | class_const | `plugins/apollo-statistics/includes/class-rest-controller.php:66` |
| `apollo-telegram` | `/wp-json/apollo-telegram/v1/broadcast` | POST | **MISSING** | literal | `plugins/apollo-telegram/src/API/VerificationController.php:168` |
| `apollo-telegram` | `/wp-json/apollo-telegram/v1/check-phone-available` | POST | **MISSING** | literal | `plugins/apollo-telegram/src/API/VerificationController.php:124` |
| `apollo-telegram` | `/wp-json/apollo-telegram/v1/poll-tick` | POST | **MISSING** | literal | `plugins/apollo-telegram/src/API/VerificationController.php:107` |
| `apollo-telegram` | `/wp-json/apollo-telegram/v1/request-support-verification` | POST | **MISSING** | literal | `plugins/apollo-telegram/src/API/VerificationController.php:26` |
| `apollo-telegram` | `/wp-json/apollo-telegram/v1/telegram` | GET POST | **MISSING** | literal | `plugins/apollo-telegram/src/API/VerificationController.php:146` |
| `apollo-telegram` | `/wp-json/apollo-telegram/v1/verification-status` | POST | **MISSING** | literal | `plugins/apollo-telegram/src/API/VerificationController.php:80` |
| `apollo-telegram` | `/wp-json/apollo-telegram/v1/verify-support-code` | POST | **MISSING** | literal | `plugins/apollo-telegram/src/API/VerificationController.php:48` |
| `apollo-telegram` | _$this->route_ | — | **MISSING** | property_default | `plugins/apollo-telegram/src/API/BaseEndpoint.php:51` |
| `apollo-templates` | `/wp-json/apollo/v1/canvas/blocks` | GET | **MISSING** | literal | `plugins/apollo-templates/includes/class-plugin.php:191` |
| `apollo-templates` | `/wp-json/apollo/v1/canvas/save` | POST | **MISSING** | literal | `plugins/apollo-templates/includes/class-plugin.php:180` |
| `apollo-templates` | `/wp-json/apollo/v1/radar/stats` | GET | **MISSING** | literal | `plugins/apollo-templates/examples/user-radar-examples.php:208` |
| `apollo-templates` | `/wp-json/apollo/v1/radar/top` | GET | **MISSING** | literal | `plugins/apollo-templates/examples/user-radar-examples.php:218` |
| `apollo-templates` | `/wp-json/apollo/v1/templates` | GET | **MISSING** | literal | `plugins/apollo-templates/includes/class-plugin.php:158` |
| `apollo-templates` | `/wp-json/apollo/v1/templates/calendars` | GET | **MISSING** | literal | `plugins/apollo-templates/includes/class-plugin.php:169` |
| `apollo-users` | `/wp-json/apollo/v1/profile/(?P<username>[a-zA-Z0-9_-]+)` | READABLE | **MISSING** | property_default | `plugins/apollo-users/src/API/UsersController.php:315` |
| `apollo-users` | `/wp-json/apollo/v1/profile/(?P<username>[a-zA-Z0-9_-]+)/view` | CREATABLE | **MISSING** | property_default | `plugins/apollo-users/src/API/UsersController.php:333` |
| `apollo-users` | `/wp-json/apollo/v1/profile/avatar` | CREATABLE | **MISSING** | property_default | `plugins/apollo-users/src/API/ProfileController.php:45` |
| `apollo-users` | `/wp-json/apollo/v1/profile/avatar` | DELETABLE | **MISSING** | property_default | `plugins/apollo-users/src/API/ProfileController.php:56` |
| `apollo-users` | `/wp-json/apollo/v1/profile/cover` | CREATABLE | **MISSING** | property_default | `plugins/apollo-users/src/API/ProfileController.php:67` |
| `apollo-users` | `/wp-json/apollo/v1/profile/cover` | DELETABLE | **MISSING** | property_default | `plugins/apollo-users/src/API/ProfileController.php:78` |
| `apollo-users` | `/wp-json/apollo/v1/profile/views` | READABLE | **MISSING** | property_default | `plugins/apollo-users/src/API/ProfileController.php:89` |
| `apollo-users` | `/wp-json/apollo/v1/users` | READABLE | **MISSING** | property_default | `plugins/apollo-users/src/API/UsersController.php:156` |
| `apollo-users` | `/wp-json/apollo/v1/users/(?P<id>\d+)` | READABLE | **MISSING** | property_default | `plugins/apollo-users/src/API/UsersController.php:189` |
| `apollo-users` | `/wp-json/apollo/v1/users/(?P<id>\d+)` | EDITABLE | **MISSING** | property_default | `plugins/apollo-users/src/API/UsersController.php:207` |
| `apollo-users` | `/wp-json/apollo/v1/users/(?P<id>\d+)/fields` | READABLE | **MISSING** | property_default | `plugins/apollo-users/src/API/UsersController.php:279` |
| `apollo-users` | `/wp-json/apollo/v1/users/(?P<id>\d+)/fields` | EDITABLE | **MISSING** | property_default | `plugins/apollo-users/src/API/UsersController.php:297` |
| `apollo-users` | `/wp-json/apollo/v1/users/(?P<id>\d+)/matchmaking` | READABLE | **MISSING** | property_default | `plugins/apollo-users/src/API/UsersController.php:261` |
| `apollo-users` | `/wp-json/apollo/v1/users/(?P<id>\d+)/preferences` | READABLE | **MISSING** | property_default | `plugins/apollo-users/src/API/UsersController.php:225` |
| `apollo-users` | `/wp-json/apollo/v1/users/(?P<id>\d+)/preferences` | EDITABLE | **MISSING** | property_default | `plugins/apollo-users/src/API/UsersController.php:243` |
| `apollo-users` | `/wp-json/apollo/v1/users/(?P<username>[a-zA-Z0-9_-]+)` | READABLE | **MISSING** | property_default | `plugins/apollo-users/src/API/UsersController.php:138` |
| `apollo-users` | `/wp-json/apollo/v1/users/me` | READABLE | **MISSING** | property_default | `plugins/apollo-users/src/API/UsersController.php:47` |
| `apollo-users` | `/wp-json/apollo/v1/users/me` | EDITABLE | **MISSING** | property_default | `plugins/apollo-users/src/API/UsersController.php:58` |
| `apollo-users` | `/wp-json/apollo/v1/users/me/matches` | READABLE | **MISSING** | property_default | `plugins/apollo-users/src/API/UsersController.php:69` |
| `apollo-users` | `/wp-json/apollo/v1/users/radar` | READABLE | **MISSING** | property_default | `plugins/apollo-users/src/API/UsersController.php:112` |
| `apollo-users` | `/wp-json/apollo/v1/users/search` | READABLE | **MISSING** | property_default | `plugins/apollo-users/src/API/UsersController.php:87` |
| `apollo-wow` | `/wp-json/apollo/v1/wows` | POST | **MISSING** | local_assign | `plugins/apollo-wow/src/Plugin.php:47` |
| `apollo-wow` | `/wp-json/apollo/v1/wows/(?P<post_id>\d+)` | GET DELETE | **MISSING** | local_assign | `plugins/apollo-wow/src/Plugin.php:57` |
| `apollo-wow` | `/wp-json/apollo/v1/wows/chart/(?P<post_id>\d+)` | GET | **MISSING** | local_assign | `plugins/apollo-wow/src/Plugin.php:84` |
| `apollo-wow` | `/wp-json/apollo/v1/wows/types` | GET | **MISSING** | local_assign | `plugins/apollo-wow/src/Plugin.php:74` |


---

_Generated 2026-09-15T05:29:41.847Z from source at `D:/dev/_apollo.rio.br`. Regenerate: `node D:/dev/_cos/verify/gen-dev-registry.js`._
