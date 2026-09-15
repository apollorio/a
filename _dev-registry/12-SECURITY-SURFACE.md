# Unauthenticated surface

Everything an anonymous request can reach. This file is descriptive — it
reports what the code does, and does not assert that any entry is a
vulnerability. Public reads are routinely correct, and some public writes
are unavoidable — authentication and webhooks cannot require a session.

## Summary

| surface | count |
|---|---|
| REST endpoints total | 441 (from 391 registration calls) |
| endpoints with `__return_true` | 112 |
| — of those accepting writes | **15** |
| endpoints with no `permission_callback` | 0 |
| AJAX actions with `nopriv` | 14 |
| entry files missing `ABSPATH` guard | 0 |


## Public REST routes accepting writes

Highest-value review target. Each must authenticate or authorise **inside**
the handler, because the route layer does not.

**Every entry below is a plausible design, and none is asserted to be a bug.**
Authentication cannot require authentication: `/auth/login`, `/auth/register`,
`/auth/reset-request`, `/auth/token`, the anti-bot challenges `/quiz/submit`
and `/simon/submit`, the `/csp-report` collector, `/newsletter/subscribe` and
the Telegram webhook all must accept an anonymous POST. What each one still
owes is an in-handler check — a nonce, a shared secret, a rate limit.

Note what is **not** in this list: creating an event, a DJ or a classified.
Those routes pair a public `READABLE` entry with a `CREATABLE` entry that has
a real permission callback (`is_dj_creator`, and similar). Counting per call
instead of per endpoint made them look unprotected; they are not.

| unit | resolved route | methods | file:line |
|---|---|---|---|
| `apollo-email` | `/wp-json/apollo/v1/newsletter/subscribe` | POST | `plugins/apollo-email/src/Newsletter.php:771` |
| `apollo-login` | `? /auth/login` | CREATABLE | `plugins/apollo-login/src/API/AuthController.php:60` |
| `apollo-login` | `? /auth/register` | CREATABLE | `plugins/apollo-login/src/API/AuthController.php:88` |
| `apollo-login` | `? /auth/reset-request` | CREATABLE | `plugins/apollo-login/src/API/AuthController.php:225` |
| `apollo-login` | `? /auth/reset-confirm` | CREATABLE | `plugins/apollo-login/src/API/AuthController.php:279` |
| `apollo-login` | `? /auth/verify-email` | CREATABLE | `plugins/apollo-login/src/API/AuthController.php:302` |
| `apollo-login` | `? /auth/resend-verification` | CREATABLE | `plugins/apollo-login/src/API/AuthController.php:325` |
| `apollo-login` | `? /quiz/submit` | CREATABLE | `plugins/apollo-login/src/API/QuizController.php:67` |
| `apollo-login` | `? /simon/submit` | CREATABLE | `plugins/apollo-login/src/API/QuizController.php:95` |
| `apollo-login` | `/wp-json/apollo/v1/auth/token` | POST | `plugins/apollo-login/src/Security/JWTAuth.php:121` |
| `apollo-login` | `/wp-json/apollo/v1/auth/token/refresh` | POST | `plugins/apollo-login/src/Security/JWTAuth.php:139` |
| `apollo-login` | `/wp-json/apollo/v1/csp-report` | POST | `plugins/apollo-login/src/Security/SecurityHeaders.php:249` |
| `apollo-membership` | `/wp-json/apollo/v1/report` | POST | `plugins/apollo-membership/src/API/ReportController.php:53` |
| `apollo-remind` | `/wp-json/apollo/v1/remind/telegram/webhook` | POST | `plugins/apollo-remind/src/API/TelegramWebhook.php:23` |
| `apollo-users` | `/wp-json/apollo/v1/profile/(?P<username>[a-zA-Z0-9_-]+)/view` | CREATABLE | `plugins/apollo-users/src/API/UsersController.php:333` |


## All public REST routes

| unit | resolved route | methods | file:line |
|---|---|---|---|
| `apollo-adverts` | _'/' . $this->rest_base_ | READABLE | `plugins/apollo-adverts/src/API/ClassifiedsController.php:33` |
| `apollo-adverts` | _'/' . $this->rest_base . '/(?P<id>[\d]+)'_ | READABLE | `plugins/apollo-adverts/src/API/ClassifiedsController.php:53` |
| `apollo-adverts` | _'/' . $this->rest_base_ | READABLE | `plugins/apollo-adverts/src/API/SearchController.php:29` |
| `apollo-adverts` | `/wp-json/apollo/v1/classifieds/(?P<id>[\d]+)/related` | READABLE | `plugins/apollo-adverts/src/RelatedAds.php:102` |
| `apollo-calendar` | `? /calendar/holidays` | READABLE | `plugins/apollo-calendar/src/API/CalendarController.php:115` |
| `apollo-comment` | _'/' . $this->rest_base_ | READABLE | `plugins/apollo-comment/src/API/DepoimentoController.php:26` |
| `apollo-comment` | _'/' . $this->rest_base . '/(?P<id>[\d]+)'_ | READABLE | `plugins/apollo-comment/src/API/DepoimentoController.php:79` |
| `apollo-core` | `? /health` | READABLE | `plugins/apollo-core/src/API/HealthController.php:33` |
| `apollo-core` | `? /pane-mode/status` | READABLE | `plugins/apollo-core/src/API/PaneModeController.php:31` |
| `apollo-core` | `/wp-json/apollo/v1/registry/cpts` | READABLE | `plugins/apollo-core/src/API/RegistryController.php:54` |
| `apollo-core` | `/wp-json/apollo/v1/registry/taxonomies` | READABLE | `plugins/apollo-core/src/API/RegistryController.php:67` |
| `apollo-core` | `/wp-json/apollo/v1/registry/status` | READABLE | `plugins/apollo-core/src/API/RegistryController.php:80` |
| `apollo-core` | `/wp-json/apollo/v1/search` | READABLE | `plugins/apollo-core/src/API/SearchController.php:40` |
| `apollo-core` | _'/search/' . $type_ | READABLE | `plugins/apollo-core/src/API/SearchController.php:54` |
| `apollo-core` | _'/' . $this->rest_base_ | READABLE | `plugins/apollo-core/src/API/ShortcodesController.php:70` |
| `apollo-core` | _'/' . $this->rest_base . '/(?P<tag>[a-z_]+)'_ | READABLE | `plugins/apollo-core/src/API/ShortcodesController.php:81` |
| `apollo-core` | `/wp-json/apollo/v1/sounds` | READABLE | `plugins/apollo-core/src/API/SoundController.php:42` |
| `apollo-core` | `/wp-json/apollo/v1/sounds/(?P<id>\d+)` | READABLE | `plugins/apollo-core/src/API/SoundController.php:55` |
| `apollo-core` | `/wp-json/apollo/v1/sounds/popular` | READABLE | `plugins/apollo-core/src/API/SoundController.php:115` |
| `apollo-djs` | `/wp-json/apollo/v1/djs` | READABLE | `plugins/apollo-djs/src/API/DJsController.php:36` |
| `apollo-djs` | `/wp-json/apollo/v1/djs/(?P<id>\d+)` | READABLE | `plugins/apollo-djs/src/API/DJsController.php:92` |
| `apollo-djs` | `/wp-json/apollo/v1/djs/(?P<id>\d+)/eventos` | READABLE | `plugins/apollo-djs/src/API/DJsController.php:115` |
| `apollo-djs` | `/wp-json/apollo/v1/djs/por-som/(?P<sound>[a-zA-Z0-9_-]+)` | READABLE | `plugins/apollo-djs/src/API/DJsController.php:126` |
| `apollo-djs` | `/wp-json/apollo/v1/djs/buscar` | READABLE | `plugins/apollo-djs/src/API/DJsController.php:137` |
| `apollo-email` | `/wp-json/apollo/v1/email/track/open/(?P<token>[a-zA-Z0-9]+)` | READABLE | `plugins/apollo-email/src/API/EmailController.php:308` |
| `apollo-email` | `/wp-json/apollo/v1/email/track/click/(?P<token>[a-zA-Z0-9]+)` | READABLE | `plugins/apollo-email/src/API/EmailController.php:325` |
| `apollo-email` | `/wp-json/apollo/v1/newsletter/subscribe` | POST | `plugins/apollo-email/src/Newsletter.php:771` |
| `apollo-events` | `/wp-json/apollo/v1/iframe/(?P<slug>[a-z0-9-]+)` | GET | `plugins/apollo-events/includes/iframe-proxy.php:88` |
| `apollo-events` | `? /eventos/(?P<id>\d+)/fragmento` | GET | `plugins/apollo-events/includes/render-single.php:1055` |
| `apollo-events` | `/wp-json/apollo/v1/eventos` | READABLE | `plugins/apollo-events/src/API/EventsController.php:54` |
| `apollo-events` | `/wp-json/apollo/v1/eventos/(?P<id>\d+)` | READABLE | `plugins/apollo-events/src/API/EventsController.php:74` |
| `apollo-events` | `/wp-json/apollo/v1/eventos/proximos` | READABLE | `plugins/apollo-events/src/API/EventsController.php:105` |
| `apollo-events` | `/wp-json/apollo/v1/eventos/passados` | READABLE | `plugins/apollo-events/src/API/EventsController.php:117` |
| `apollo-events` | `/wp-json/apollo/v1/eventos/hoje` | READABLE | `plugins/apollo-events/src/API/EventsController.php:129` |
| `apollo-events` | `/wp-json/apollo/v1/eventos/por-data/(?P<date>[\d-]+)` | READABLE | `plugins/apollo-events/src/API/EventsController.php:140` |
| `apollo-events` | `/wp-json/apollo/v1/eventos/por-local/(?P<loc_id>\d+)` | READABLE | `plugins/apollo-events/src/API/EventsController.php:161` |
| `apollo-events` | `/wp-json/apollo/v1/eventos/por-dj/(?P<dj_id>\d+)` | READABLE | `plugins/apollo-events/src/API/EventsController.php:179` |
| `apollo-events` | `/wp-json/apollo/v1/eventos/(?P<id>\d+)/djs` | READABLE | `plugins/apollo-events/src/API/EventsController.php:197` |
| `apollo-events` | `/wp-json/apollo/v1/eventos/buscar` | READABLE | `plugins/apollo-events/src/API/EventsController.php:238` |
| `apollo-events` | `/wp-json/apollo/v1/eventos/calendario/(?P<year>\d{4})/(?P<month>\d{1,2})` | READABLE | `plugins/apollo-events/src/API/EventsController.php:266` |
| `apollo-fav` | _'/' . self::BASE . '/count/(?P<post_id>\d+)'_ | READABLE | `plugins/apollo-fav/includes/class-rest-controller.php:129` |
| `apollo-hub` | `/wp-json/apollo/v1/hubs` | READABLE | `plugins/apollo-hub/src/API/HubController.php:41` |
| `apollo-hub` | `/wp-json/apollo/v1/hubs/(?P<username>[a-zA-Z0-9._-]+)` | READABLE | `plugins/apollo-hub/src/API/HubController.php:67` |
| `apollo-hub` | `/wp-json/apollo/v1/hubs/(?P<username>[a-zA-Z0-9._-]+)/links` | READABLE | `plugins/apollo-hub/src/API/HubController.php:93` |
| `apollo-hub` | `/wp-json/apollo/v1/hubs/(?P<username>[a-zA-Z0-9._-]+)/blocks` | READABLE | `plugins/apollo-hub/src/API/HubController.php:144` |
| `apollo-hub` | `/wp-json/apollo/v1/hubs/(?P<username>[a-zA-Z0-9._-]+)/share/(?P<post_id>\d+)` | READABLE | `plugins/apollo-hub/src/API/HubController.php:195` |
| `apollo-journal` | `/wp-json/apollo/v1/journal/posts` | READABLE | `plugins/apollo-journal/src/API/PostsController.php:33` |
| `apollo-journal` | `/wp-json/apollo/v1/journal/news` | READABLE | `plugins/apollo-journal/src/API/PostsController.php:47` |
| `apollo-journal` | `/wp-json/apollo/v1/journal/notas` | READABLE | `plugins/apollo-journal/src/API/PostsController.php:61` |
| `apollo-loc` | _'/' . $this->rest_base_ | READABLE | `plugins/apollo-loc/src/API/LocalsController.php:50` |
| `apollo-loc` | _'/' . $this->rest_base . '/(?P<id>[\d]+)'_ | READABLE | `plugins/apollo-loc/src/API/LocalsController.php:72` |
| `apollo-loc` | _'/' . $this->rest_base . '/proximos'_ | READABLE | `plugins/apollo-loc/src/API/LocalsController.php:105` |
| `apollo-loc` | _$rb . '/proximos'_ | READABLE | `plugins/apollo-loc/src/API/Router.php:41` |
| `apollo-loc` | `? /` | READABLE | `plugins/apollo-loc/src/API/Router.php:58` |
| `apollo-loc` | _$rb . '/(?P<id>[\d]+)'_ | READABLE | `plugins/apollo-loc/src/API/Router.php:90` |
| `apollo-login` | `? /dj/config` | READABLE | `plugins/apollo-login/src/API/AppAuthController.php:186` |
| `apollo-login` | `? /auth/login` | CREATABLE | `plugins/apollo-login/src/API/AuthController.php:60` |
| `apollo-login` | `? /auth/register` | CREATABLE | `plugins/apollo-login/src/API/AuthController.php:88` |
| `apollo-login` | `? /auth/reset-request` | CREATABLE | `plugins/apollo-login/src/API/AuthController.php:225` |
| `apollo-login` | `? /auth/check-username` | READABLE | `plugins/apollo-login/src/API/AuthController.php:243` |
| `apollo-login` | `? /auth/check-email` | READABLE | `plugins/apollo-login/src/API/AuthController.php:261` |
| `apollo-login` | `? /auth/reset-confirm` | CREATABLE | `plugins/apollo-login/src/API/AuthController.php:279` |
| `apollo-login` | `? /auth/verify-email` | CREATABLE | `plugins/apollo-login/src/API/AuthController.php:302` |
| `apollo-login` | `? /auth/resend-verification` | CREATABLE | `plugins/apollo-login/src/API/AuthController.php:325` |
| `apollo-login` | `? /quiz/questions` | READABLE | `plugins/apollo-login/src/API/QuizController.php:48` |
| `apollo-login` | `? /quiz/submit` | CREATABLE | `plugins/apollo-login/src/API/QuizController.php:67` |
| `apollo-login` | `? /simon/submit` | CREATABLE | `plugins/apollo-login/src/API/QuizController.php:95` |
| `apollo-login` | `? /simon/highscores` | READABLE | `plugins/apollo-login/src/API/QuizController.php:128` |
| `apollo-login` | `/wp-json/apollo/v1/auth/token` | POST | `plugins/apollo-login/src/Security/JWTAuth.php:121` |
| `apollo-login` | `/wp-json/apollo/v1/auth/token/refresh` | POST | `plugins/apollo-login/src/Security/JWTAuth.php:139` |
| `apollo-login` | `/wp-json/apollo/v1/csp-report` | POST | `plugins/apollo-login/src/Security/SecurityHeaders.php:249` |
| `apollo-maps` | `/wp-json/apollo/v1/map/explorer` | READABLE | `plugins/apollo-maps/src/API/ExplorerController.php:28` |
| `apollo-membership` | `/wp-json/apollo/v1/achievements` | READABLE | `plugins/apollo-membership/src/API/AchievementsController.php:35` |
| `apollo-membership` | `/wp-json/apollo/v1/achievements/(?P<id>\d+)` | READABLE | `plugins/apollo-membership/src/API/AchievementsController.php:62` |
| `apollo-membership` | `/wp-json/apollo/v1/membership/evidence/(?P<achievement_id>\d+)` | READABLE | `plugins/apollo-membership/src/API/AchievementsController.php:155` |
| `apollo-membership` | `/wp-json/apollo/v1/membership/verify/(?P<hash>[a-zA-Z0-9]+)` | READABLE | `plugins/apollo-membership/src/API/AchievementsController.php:177` |
| `apollo-membership` | `/wp-json/apollo/v1/leaderboard` | READABLE | `plugins/apollo-membership/src/API/LeaderboardController.php:31` |
| `apollo-membership` | `/wp-json/apollo/v1/leaderboard/user/(?P<id>\d+)` | READABLE | `plugins/apollo-membership/src/API/LeaderboardController.php:48` |
| `apollo-membership` | `/wp-json/apollo/v1/membership-badge` | READABLE | `plugins/apollo-membership/src/API/LeaderboardController.php:82` |
| `apollo-membership` | `/wp-json/apollo/v1/ranks` | READABLE | `plugins/apollo-membership/src/API/RanksController.php:32` |
| `apollo-membership` | `/wp-json/apollo/v1/ranks/(?P<id>\d+)` | READABLE | `plugins/apollo-membership/src/API/RanksController.php:42` |
| `apollo-membership` | `/wp-json/apollo/v1/report` | POST | `plugins/apollo-membership/src/API/ReportController.php:53` |
| `apollo-membership` | `/wp-json/apollo/v1/membership/triggers` | READABLE | `plugins/apollo-membership/src/API/TriggersController.php:29` |
| `apollo-notif` | `/wp-json/apollo/v1/notifications/push/vapid-public-key` | GET | `plugins/apollo-notif/src/Plugin.php:381` |
| `apollo-radio` | _'/' . $this->rest_base . '/status'_ | GET | `plugins/apollo-radio/src/API/RadioController.php:34` |
| `apollo-radio` | _'/' . $this->rest_base . '/now'_ | GET | `plugins/apollo-radio/src/API/RadioController.php:43` |
| `apollo-radio` | _'/' . $this->rest_base . '/playlist'_ | GET | `plugins/apollo-radio/src/API/RadioController.php:52` |
| `apollo-radio` | _'/' . $this->rest_base . '/stream'_ | GET | `plugins/apollo-radio/src/API/RadioController.php:68` |
| `apollo-remind` | `/wp-json/apollo/v1/remind/push/vapid-key` | GET | `plugins/apollo-remind/src/API/PushController.php:32` |
| `apollo-remind` | `/wp-json/apollo/v1/remind/telegram/webhook` | POST | `plugins/apollo-remind/src/API/TelegramWebhook.php:23` |
| `apollo-scheduler` | _'/' . $this->rest_base . '/availability'_ | READABLE | `plugins/apollo-scheduler/src/API/AvailabilityController.php:24` |
| `apollo-scheduler` | _'/' . $this->rest_base . '/availability/grid'_ | READABLE | `plugins/apollo-scheduler/src/API/AvailabilityController.php:37` |
| `apollo-seo` | `/wp-json/apollo/v1/seo/post/(?P<id>\d+)` | GET | `plugins/apollo-seo/src/Plugin.php:447` |
| `apollo-seo` | `/wp-json/apollo/v1/seo/term/(?P<id>\d+)` | GET | `plugins/apollo-seo/src/Plugin.php:463` |
| `apollo-seo` | `/wp-json/apollo/v1/seo/home` | GET | `plugins/apollo-seo/src/Plugin.php:479` |
| `apollo-sign` | _'/' . $this->rest_base . '/(?P<hash>[a-f0-9]{64})'_ | GET | `plugins/apollo-sign/src/API/VerifyController.php:46` |
| `apollo-social` | `/wp-json/apollo/v1/depo/(?P<user_id>\d+)` | GET | `plugins/apollo-social/src/Plugin.php:136` |
| `apollo-social` | `/wp-json/apollo/v1/activity/(?P<id>\d+)/replies` | GET | `plugins/apollo-social/src/Plugin.php:147` |
| `apollo-social` | `/wp-json/apollo/v1/members` | GET | `plugins/apollo-social/src/Plugin.php:235` |
| `apollo-templates` | `/wp-json/apollo/v1/radar/stats` | GET | `plugins/apollo-templates/examples/user-radar-examples.php:208` |
| `apollo-templates` | `/wp-json/apollo/v1/radar/top` | GET | `plugins/apollo-templates/examples/user-radar-examples.php:218` |
| `apollo-templates` | `/wp-json/apollo/v1/templates` | GET | `plugins/apollo-templates/includes/class-plugin.php:158` |
| `apollo-templates` | `/wp-json/apollo/v1/templates/calendars` | GET | `plugins/apollo-templates/includes/class-plugin.php:169` |
| `apollo-templates` | `/wp-json/apollo/v1/canvas/blocks` | GET | `plugins/apollo-templates/includes/class-plugin.php:191` |
| `apollo-users` | `/wp-json/apollo/v1/users/radar` | READABLE | `plugins/apollo-users/src/API/UsersController.php:112` |
| `apollo-users` | `/wp-json/apollo/v1/users/(?P<username>[a-zA-Z0-9_-]+)` | READABLE | `plugins/apollo-users/src/API/UsersController.php:138` |
| `apollo-users` | `/wp-json/apollo/v1/users/(?P<id>\d+)` | READABLE | `plugins/apollo-users/src/API/UsersController.php:189` |
| `apollo-users` | `/wp-json/apollo/v1/profile/(?P<username>[a-zA-Z0-9_-]+)` | READABLE | `plugins/apollo-users/src/API/UsersController.php:315` |
| `apollo-users` | `/wp-json/apollo/v1/profile/(?P<username>[a-zA-Z0-9_-]+)/view` | CREATABLE | `plugins/apollo-users/src/API/UsersController.php:333` |
| `apollo-wow` | `/wp-json/apollo/v1/wows/(?P<post_id>\d+)` | GET | `plugins/apollo-wow/src/Plugin.php:57` |
| `apollo-wow` | `/wp-json/apollo/v1/wows/types` | GET | `plugins/apollo-wow/src/Plugin.php:74` |
| `apollo-wow` | `/wp-json/apollo/v1/wows/chart/(?P<post_id>\d+)` | GET | `plugins/apollo-wow/src/Plugin.php:84` |


## AJAX reachable while logged out

| action | unit | file:line |
|---|---|---|
| `apollo_auth_debug_log` | `apollo-login` | `plugins/apollo-login/src/Auth/RegisterHandler.php:48` |
| `apollo_auth_nonce` | `apollo-login` | `plugins/apollo-login/src/Auth/LoginHandler.php:34` |
| `apollo_fav_toggle` | `apollo-fav` | `plugins/apollo-fav/includes/class-cbx-bridge.php:47` |
| `apollo_forgot_password` | `apollo-login` | `plugins/apollo-login/src/Auth/PasswordReset.php:32` |
| `apollo_get_user_stats_widget` | `apollo-statistics` | `plugins/apollo-statistics/includes/class-user-stats-widget.php:37` |
| `apollo_get_user_top_sounds` | `apollo-fav` | `plugins/apollo-fav/includes/class-fav-ranking.php:50` |
| `apollo_login` | `apollo-login` | `plugins/apollo-login/src/Auth/LoginHandler.php:31` |
| `apollo_navbar_login` | `apollo-templates` | `plugins/apollo-templates/apollo-templates.php:534` |
| `apollo_panel_login` | `apollo-templates` | `plugins/apollo-templates/apollo-templates.php:568` |
| `apollo_register` | `apollo-login` | `plugins/apollo-login/src/Auth/RegisterHandler.php:33` |
| `apollo_resend_verification` | `apollo-login` | `plugins/apollo-login/src/Auth/EmailVerification.php:48` |
| `apollo_reset_confirm` | `apollo-login` | `plugins/apollo-login/src/Auth/PasswordReset.php:34` |
| `apollo_suggest_event` | `apollo-templates` | `plugins/apollo-templates/apollo-templates.php:637` |
| `apollo_validate_cpf` | `apollo-login` | `plugins/apollo-login/src/Auth/RegisterHandler.php:44` |


## Web exposure of this folder

Verified live at build time, not assumed. See `README.md` for the full
mechanism and for the one protection layer that is deliberately not applied.

---

_Generated 2026-09-15T05:29:41.847Z from source at `D:/dev/_apollo.rio.br`. Regenerate: `node D:/dev/_cos/verify/gen-dev-registry.js`._
