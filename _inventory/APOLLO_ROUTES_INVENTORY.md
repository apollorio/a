# Apollo Routes Inventory — Pages + REST

**Generated:** 2026-07-19  
**Source tree:** `D:\dev\_apollo.rio.br\plugins`  
**Companion SSOT:** `apollo-registry.json` (same folder)  
**Namespace (default):** `apollo/v1` unless noted  

### How to read this file

| Section | Meaning |
|---------|---------|
| **Pages** | Front (or admin) URL routes that render HTML |
| **REST** | `register_rest_route` API paths |
| `file:line` / `file:lineX-lineY` | Code that **selects or loads** the page template / registers the route |

**Caveats**

- One `register_rest_route()` may register several HTTP methods → REST rows are **path-centric**.
- BlankCanvas plugins ultimately `include` via `apollo-core/src/Traits/BlankCanvasTrait.php:40-73` (`include` @ **72**).
- Registry / `routes.php` can diverge from live code (noted per plugin where known).
- Locked/planned plugins have registry pages/REST but **no code** yet.

### Totals (approx.)

| Metric | Count |
|--------|------:|
| Plugins with REST registrations | 36 |
| REST `register_rest_route` call sites | ~391 |
| Merged REST path rows | ~365 |
| `apollo-core/config/routes.php` owned paths | 187 |
| Virtual / CPT / admin **page** routes documented below | ~90+ |

---

## apollo-admin

### Pages
| route | type | renderer |
|-------|------|----------|
| `/admin/panel` | virtual | `src/Frontend/Router.php:63-84` → `templates/frontend/panel.php` @80 |
| `/admin/pending` | virtual | same → `pending.php` |
| `/admin/memberships` | virtual | same → `memberships.php` |

### REST
| method(s) | path | file:line |
|-----------|------|-----------|
| GET, DELETE | `/errorlog` | `src/ErrorLogViewer.php:60,90` |
| GET, POST | `/toolbar` | `src/ToolbarCustomizer.php:136,149` |
| GET, POST | `/preferences` | `src/UserPreferences.php:158,171` |
| DELETE | `/preferences/(?P<key>[a-z0-9_-]+)` | `src/UserPreferences.php:184` |
| GET | `/admin/memberships` | `src/Frontend/Controller/MembershipController.php:30` |
| GET | `/admin/pending` | `src/Frontend/Controller/PendingController.php:31` |
| POST | `/admin/pending/(?P<id>\d+)/approve` | `src/Frontend/Controller/PendingController.php:43` |
| POST | `/admin/pending/(?P<id>\d+)/reject` | `src/Frontend/Controller/PendingController.php:65` |
| GET | `/settings` | `src/Rest/SettingsController.php:45` |
| GET, POST | `/settings/(?P<slug>[a-z0-9_-]+)` | `src/Rest/SettingsController.php:56` |
| GET | `/settings/(?P<slug>[a-z0-9_-]+)/schema` | `src/Rest/SettingsController.php:91` |
| GET | `/admin/registry` | `src/Rest/SettingsController.php:110` |
| POST | `/admin/registry/refresh` | `src/Rest/SettingsController.php:121` |
| GET | `/settings/export` | `src/Rest/SettingsController.php:133` |
| POST | `/settings/import` | `src/Rest/SettingsController.php:143` |

---

## apollo-adverts

### Pages
| route | type | renderer |
|-------|------|----------|
| `/marketplace` | virtual | `src/Plugin.php:131-138` → `archive-classified.php` @137 |
| `/criar-anuncio`, `/novo-anuncio` | virtual | `src/Plugin.php:145-147` → BlankCanvas `form.php` |
| `/anuncios` | CPT archive | theme / `archive-classified.php` |
| `/anuncio/{slug}` | CPT single | `templates/single-classified.php` |

### REST
| method(s) | path | file:line |
|-----------|------|-----------|
| POST | `/events/request` | `includes/event-selector.php:821` |
| GET | `/classifieds/(?P<id>[\d]+)/related` | `src/RelatedAds.php:102` |
| GET, POST | `/classifieds` | `src/API/ClassifiedsController.php:33` |
| GET, POST, PUT, PATCH, DELETE | `/classifieds/(?P<id>[\d]+)` | `src/API/ClassifiedsController.php:53` |
| GET | `/classifieds/my` | `src/API/ClassifiedsController.php:82` |
| GET | `/classifieds/search` | `src/API/SearchController.php:29` |

---

## apollo-calendar *(not in registry plugins; code exists)*

### Pages
| route | type | renderer |
|-------|------|----------|
| `/agenda` | virtual | `src/Frontend/CalendarPage.php:35-64` → BlankCanvas `templates/agenda.php` @61 |

### REST
| method(s) | path | file:line |
|-----------|------|-----------|
| GET | `/calendar` | `src/API/CalendarController.php:41` |
| POST | `/calendar/appointments` | `src/API/CalendarController.php:69` |
| POST, PUT, PATCH, DELETE | `/calendar/appointments/(?P<id>\d+)` | `src/API/CalendarController.php:80` |
| GET | `/calendar/holidays` | `src/API/CalendarController.php:115` |
| GET | `/calendar/ical` | `src/API/CalendarController.php:143` |

---

## apollo-cena *(LOCKED_NEXT_VERSION — no plugin folder)*

### Pages (registry only)
| route | type | renderer |
|-------|------|----------|
| `/cena` | virtual | — (`cena.php`) |
| `/cena/calendario` | virtual | — (`cena-calendar.php`) |
| `/cena/membros` | virtual | — (`cena-members.php`) |

### REST (registry / routes.php only)
| method(s) | path | file:line |
|-----------|------|-----------|
| GET | `/cena/calendar` | — |
| POST | `/cena/calendar/save-date` | — |
| PUT, DELETE | `/cena/calendar/{id}` | — |
| GET | `/cena/members` | — |
| POST | `/cena/access/request` | — |

---

## apollo-chat

### Pages
| route | type | renderer |
|-------|------|----------|
| `/mensagens` | virtual | `src/Plugin.php:82-95` → `templates/chat.php` |
| `/mensagens/{id}` | virtual | same (+ `apollo_thread_id`) |

### REST
| method(s) | path | file:line |
|-----------|------|-----------|
| GET | `/chat/threads` | `src/Plugin.php:107` |
| GET, DELETE | `/chat/threads/(?P<id>\d+)` | `src/Plugin.php:117,167` |
| POST | `/chat/send` | `src/Plugin.php:127` |
| GET | `/chat/unread` | `src/Plugin.php:137` |
| GET | `/chat/poll` | `src/Plugin.php:147` |
| GET | `/chat/more` | `src/Plugin.php:157` |
| POST | `/chat/typing` | `src/Plugin.php:180` |
| POST | `/chat/threads/(?P<id>\d+)/read` | `src/Plugin.php:191` |
| PUT, DELETE | `/chat/messages/(?P<id>\d+)` | `src/Plugin.php:202,213` |
| POST | `/chat/messages/(?P<id>\d+)/react` | `src/Plugin.php:224` |
| GET | `/chat/search` | `src/Plugin.php:235` |
| POST, DELETE | `/chat/block/(?P<user_id>\d+)` | `src/Plugin.php:246,255` |
| POST | `/chat/presence` | `src/Plugin.php:266` |
| GET | `/chat/threads/(?P<id>\d+)/older` | `src/Plugin.php:277` |
| GET, POST, DELETE | `/chat/threads/(?P<id>\d+)/members` | `src/Plugin.php:288` |
| POST | `/chat/threads/(?P<id>\d+)/mute` | `src/Plugin.php:299` |
| POST | `/chat/messages/(?P<id>\d+)/forward` | `src/Plugin.php:310` |
| POST, DELETE | `/chat/threads/(?P<id>\d+)/pin` | `src/Plugin.php:321` |
| GET | `/chat/threads/(?P<id>\d+)/pinned` | `src/Plugin.php:332` |
| POST | `/chat/thread-for-context` | `src/Plugin.php:343` |
| GET | `/chat/gif-search` | `src/Plugin.php:372` |
| POST | `/chat/messages/(?P<id>\d+)/report` | `src/Plugin.php:397` |
| GET | `/chat/threads/(?P<id>\d+)/export` | `src/Plugin.php:408` |
| GET | `/chat/reports` | `src/Plugin.php:419` |
| POST | `/chat/reports/(?P<id>\d+)/action` | `src/Plugin.php:432` |
| POST | `/chat/upload` | `src/Plugin.php:445` |

---

## apollo-coauthor

### Pages
| — | — | no front virtual pages |

### REST
| method(s) | path | file:line |
|-----------|------|-----------|
| GET, POST, PUT, PATCH | `/coauthors/(?P<post_id>[\d]+)` | `src/API/CoauthorController.php:58` |
| GET | `/coauthors/search` | `src/API/CoauthorController.php:115` |
| POST | `/coauthors/bulk` | `src/Components/BulkEdit.php:169` |

---

## apollo-comment

### Pages
| — | — | no front virtual pages |

### REST
| method(s) | path | file:line |
|-----------|------|-----------|
| GET, POST | `/depoimentos` | `src/API/DepoimentoController.php:26` |
| GET, POST, PUT, PATCH, DELETE | `/depoimentos/(?P<id>[\d]+)` | `src/API/DepoimentoController.php:79` |

---

## apollo-core

### Pages
| route | type | renderer |
|-------|------|----------|
| `/`, landing split | dynamic | guests→`/casa`, logged→`/feed` (templates routers) |
| Shared BlankCanvas include | trait | `src/Traits/BlankCanvasTrait.php:40-73` (`include` @72) |

### REST
| method(s) | path | file:line |
|-----------|------|-----------|
| GET | `/health` | `src/API/HealthController.php:33` |
| GET | `/pane-mode/status` | `src/API/PaneModeController.php:31` |
| POST | `/pane-mode/enable` | `src/API/PaneModeController.php:42` |
| POST | `/pane-mode/disable` | `src/API/PaneModeController.php:53` |
| GET | `/registry` | `src/API/RegistryController.php:41` |
| GET | `/registry/cpts` | `src/API/RegistryController.php:54` |
| GET | `/registry/taxonomies` | `src/API/RegistryController.php:67` |
| GET | `/registry/status` | `src/API/RegistryController.php:80` |
| GET | `/registry/tables` | `src/API/RegistryController.php:93` |
| POST | `/registry/inventory-sync` | `src/API/RegistryController.php:106` |
| GET | `/search` | `src/API/SearchController.php:40` |
| GET | `/search/{type}` (events, users, classifieds, djs, locs, posts, pages) | `src/API/SearchController.php:54` |
| GET | `/shortcodes` | `src/API/ShortcodesController.php:70` |
| GET | `/shortcodes/(?P<tag>[a-z_]+)` | `src/API/ShortcodesController.php:81` |
| POST | `/shortcodes/render` | `src/API/ShortcodesController.php:100` |
| GET | `/sounds` | `src/API/SoundController.php:42` |
| GET | `/sounds/(?P<id>\d+)` | `src/API/SoundController.php:55` |
| GET, POST | `/sounds/user` | `src/API/SoundController.php:76,89` |
| GET | `/sounds/popular` | `src/API/SoundController.php:115` |

**Also:** `config/routes.php` — **187** owned path declarations (ownership map; not all match live Portuguese paths).

---

## apollo-dashboard

### Pages
| route | type | renderer |
|-------|------|----------|
| `/painel` | virtual | `includes/class-plugin.php:192-206` → `templates/dashboard.php` @205-206 |
| `/painel/eventos` | virtual | same (tab=`eventos`) |
| `/painel/favoritos` | virtual | same (tab=`favoritos`) |
| `/painel/grupos` | virtual | same (tab=`comunas`) |
| `/painel/configuracoes` | virtual | same (tab=`configuracoes`) |

### REST
| method(s) | path | file:line |
|-----------|------|-----------|
| GET | `/dashboard` | `includes/class-plugin.php:217` |
| GET | `/dashboard/widgets` | `includes/class-plugin.php:228` |
| GET, PUT | `/dashboard/settings` | `includes/class-plugin.php:239` |
| GET, PUT | `/dashboard/layout` | `includes/class-plugin.php:250` |

---

## apollo-dj-sync

### Pages
| — | — | none |

### REST
| method(s) | path | file:line |
|-----------|------|-----------|
| POST | `/dj/session` | `src/API/DJPermissionsController.php:38` |

---

## apollo-djs

### Pages
| route | type | renderer |
|-------|------|----------|
| `/djs` | CPT archive | `src/TemplateLoader.php:41-47` → `styles/base/archive-dj.php` |
| `/dj/{slug}` | CPT single | `src/TemplateLoader.php:29-35` → `styles/base/single-dj.php` |

### REST
| method(s) | path | file:line |
|-----------|------|-----------|
| GET, POST | `/djs` | `src/API/DJsController.php:36` |
| GET, POST, PUT, PATCH, DELETE | `/djs/(?P<id>\d+)` | `src/API/DJsController.php:89` |
| GET | `/djs/(?P<id>\d+)/eventos` | `src/API/DJsController.php:112` |
| GET | `/djs/por-som/(?P<sound>[a-zA-Z0-9_-]+)` | `src/API/DJsController.php:123` |
| GET | `/djs/buscar` | `src/API/DJsController.php:134` |

---

## apollo-docs

### Pages
| route | type | renderer |
|-------|------|----------|
| `/documentos` (front) | virtual | `src/Plugin.php:75-118` → BlankCanvas `frontend-documents.php` @118 |
| wp-admin documentos | admin | `src/Admin/Controller.php` (~85) |

### REST
| method(s) | path | file:line |
|-----------|------|-----------|
| GET, POST | `/docs` | `src/API/DocsController.php:22,34` |
| GET, PUT, DELETE | `/docs/(?P<id>\d+)` | `src/API/DocsController.php:45,56,67` |
| GET | `/docs/(?P<id>\d+)/download` | `src/API/DocsController.php:78` |
| GET | `/docs/(?P<id>\d+)/versions` | `src/API/DocsController.php:89` |
| POST | `/docs/(?P<id>\d+)/lock` | `src/API/DocsController.php:100` |
| POST | `/docs/(?P<id>\d+)/finalize` | `src/API/DocsController.php:111` |
| POST | `/docs/(?P<id>\d+)/upload` | `src/API/DocsController.php:122` |
| POST | `/docs/upload` | `src/API/DocsController.php:133` |
| GET, POST | `/docs/folders` | `src/API/FoldersController.php:18,29` |
| PUT, DELETE | `/docs/folders/(?P<id>\d+)` | `src/API/FoldersController.php:40,51` |

---

## apollo-email

### Pages
| — | — | none (shortcodes / admin) |

### REST
| method(s) | path | file:line |
|-----------|------|-----------|
| POST | `/newsletter/subscribe` | `src/Newsletter.php:771` |
| GET | `/newsletter/subscribers` | `src/Newsletter.php:781` |
| POST | `/email/send` | `src/API/EmailController.php:59` |
| POST | `/email/test` | `src/API/EmailController.php:92` |
| GET | `/email/stats` | `src/API/EmailController.php:111` |
| GET | `/email/queue` | `src/API/EmailController.php:122` |
| POST | `/email/queue/(?P<id>\d+)/cancel` | `src/API/EmailController.php:149` |
| POST | `/email/queue/(?P<id>\d+)/retry` | `src/API/EmailController.php:159` |
| POST | `/email/queue/purge` | `src/API/EmailController.php:169` |
| GET, POST | `/email/templates` | `src/API/EmailController.php:187` |
| GET, PUT, DELETE | `/email/templates/(?P<id>\d+)` | `src/API/EmailController.php:225` |
| GET | `/email/log` | `src/API/EmailController.php:248` |
| POST | `/email/log/purge` | `src/API/EmailController.php:290` |
| GET | `/email/track/open/(?P<token>[a-zA-Z0-9]+)` | `src/API/EmailController.php:308` |
| GET | `/email/track/click/(?P<token>[a-zA-Z0-9]+)` | `src/API/EmailController.php:325` |
| GET, PUT | `/email/preferences` | `src/API/EmailController.php:347` |

---

## apollo-events

### Pages
| route | type | renderer |
|-------|------|----------|
| `/eventos` | CPT archive | `src/TemplateLoader.php:32-56,85-92` → `styles/base/archive-event.php` |
| `/portal`, `/portal/eventos` | virtual | `src/Plugin.php:184-198` → BlankCanvas `archive-event.php` |
| `/evento/{slug}` | CPT single | `src/TemplateLoader.php:32-79` → `single-event-runtime.php` / `single-event.php` |
| `/novo-evento`, `/criar-evento`, `/add-evento` | virtual | `src/Plugin.php:205-210` → `create-event.php` |
| `/meus-eventos` | virtual | `src/Plugin.php:213-218` → `dashboard-event.php` |

Rewrites: `src/Plugin.php:104-116`. Query var: `apollo_event_page`.

### REST
| method(s) | path | file:line |
|-----------|------|-----------|
| GET | `/cena-rio/agenda` | `src/Cena_Rio_Submissions.php:53` |
| POST | `/cena-rio/enviar` | `src/Cena_Rio_Submissions.php:65` |
| POST | `/cena-rio/confirmar/(?P<id>\d+)` | `src/Cena_Rio_Submissions.php:114` |
| POST | `/cena-rio/cancelar/(?P<id>\d+)` | `src/Cena_Rio_Submissions.php:131` |
| GET, POST | `/eventos` | `src/API/EventsController.php:50` |
| GET, POST, PUT, PATCH, DELETE | `/eventos/(?P<id>\d+)` | `src/API/EventsController.php:70` |
| GET | `/eventos/proximos` | `src/API/EventsController.php:101` |
| GET | `/eventos/passados` | `src/API/EventsController.php:113` |
| GET | `/eventos/hoje` | `src/API/EventsController.php:125` |
| GET | `/eventos/por-data/(?P<date>[\d-]+)` | `src/API/EventsController.php:136` |
| GET | `/eventos/por-local/(?P<loc_id>\d+)` | `src/API/EventsController.php:157` |
| GET | `/eventos/por-dj/(?P<dj_id>\d+)` | `src/API/EventsController.php:175` |
| GET, POST, DELETE | `/eventos/(?P<id>\d+)/djs` | `src/API/EventsController.php:193` |
| GET | `/eventos/buscar` | `src/API/EventsController.php:234` |
| GET | `/eventos/calendario/(?P<year>\d{4})/(?P<month>\d{1,2})` | `src/API/EventsController.php:262` |
| POST, DELETE | `/eventos/(?P<id>\d+)/banner` | `src/API/EventsController.php:287` |
| POST | `/eventos/(?P<id>\d+)/clonar` | `src/API/EventsController.php:305` |
| GET | `/eventos/(?P<id>\d+)/estatisticas` | `src/API/EventsController.php:316` |
| GET, POST, DELETE | `/eventos/(?P<id>\d+)/participantes` | `src/API/EventsController.php:327` |
| POST | `/eventos/(?P<id>\d+)/participantes/(?P<user_id>\d+)/check-in` | `src/API/EventsController.php:362` |
| POST | `/eventos/(?P<id>\d+)/notificar-warmup` | `src/API/EventsController.php:387` |
| GET | `/eventos/meus` | `src/API/EventsController.php:406` |
| GET | `/eventos/meus/rsvp` | `src/API/EventsController.php:431` |
| POST | `/eventos/lote` | `src/API/EventsController.php:461` |

> Note: `routes.php` still lists English `/events/*` for some ownership rows; **live** paths are Portuguese `/eventos/*`.

---

## apollo-fav

### Pages
| — | — | none |

### REST
| method(s) | path | file:line |
|-----------|------|-----------|
| GET, POST | `/favs` | `includes/class-rest-controller.php:43` |
| DELETE | `/favs/(?P<post_id>\d+)` | `includes/class-rest-controller.php:93` |
| POST | `/favs/toggle/(?P<post_id>\d+)` | `includes/class-rest-controller.php:111` |
| GET | `/favs/count/(?P<post_id>\d+)` | `includes/class-rest-controller.php:129` |
| GET | `/favs/check/(?P<post_id>\d+)` | `includes/class-rest-controller.php:147` |

---

## apollo-gestor

### Pages
| route | type | renderer |
|-------|------|----------|
| wp-admin `apollo-gestor` | admin | `src/Admin/Controller.php` (~187) → `templates/gestor.php` |

### REST
| — | — | none |

---

## apollo-groups

### Pages
| route | type | renderer |
|-------|------|----------|
| `/grupos` | virtual | `src/Plugin.php:86-110` → `templates/groups.php` |
| `/comunas` | virtual | same → `comunas.php` |
| `/nucleos` | virtual | same → `nucleos.php` |
| `/grupo/{slug}` | virtual | same → `single-group.php` |
| `/criar-grupo` | virtual | same → `create-group.php` |

### REST
| method(s) | path | file:line |
|-----------|------|-----------|
| GET, POST | `/groups` | `src/Plugin.php:131` |
| GET, PUT, DELETE | `/groups/(?P<id>\d+)` | `src/Plugin.php:148` |
| GET | `/groups/(?P<id>\d+)/members` | `src/Plugin.php:172` |
| POST | `/groups/(?P<id>\d+)/join` | `src/Plugin.php:182` |
| POST | `/groups/(?P<id>\d+)/leave` | `src/Plugin.php:192` |
| GET | `/groups/comunas` | `src/Plugin.php:202` |
| GET | `/groups/nucleos` | `src/Plugin.php:215` |
| GET | `/groups/my` | `src/Plugin.php:231` |
| POST | `/groups/(?P<id>\d+)/members/(?P<user_id>\d+)/promote` | `src/Plugin.php:243` |
| POST | `/groups/(?P<id>\d+)/members/(?P<user_id>\d+)/demote` | `src/Plugin.php:253` |
| POST, DELETE | `/groups/(?P<id>\d+)/members/(?P<user_id>\d+)/ban` | `src/Plugin.php:263` |
| DELETE | `/groups/(?P<id>\d+)/members/(?P<user_id>\d+)` | `src/Plugin.php:280` |
| GET | `/groups/(?P<id>\d+)/bans` | `src/Plugin.php:290` |
| GET, POST | `/groups/(?P<id>\d+)/invitations` | `src/Plugin.php:302` |
| POST | `/groups/(?P<id>\d+)/invitations/accept` | `src/Plugin.php:319` |
| POST | `/groups/(?P<id>\d+)/invitations/reject` | `src/Plugin.php:329` |
| GET | `/my/group-invitations` | `src/Plugin.php:340` |
| GET, POST | `/groups/(?P<id>\d+)/requests` | `src/Plugin.php:352` |
| POST | `/groups/(?P<id>\d+)/requests/(?P<user_id>\d+)/accept` | `src/Plugin.php:369` |
| POST | `/groups/(?P<id>\d+)/requests/(?P<user_id>\d+)/reject` | `src/Plugin.php:379` |
| GET | `/groups/search` | `src/Plugin.php:390` |
| GET | `/groups/(?P<id>\d+)/feed` | `src/Plugin.php:401` |
| POST, DELETE | `/groups/(?P<id>\d+)/avatar` | `src/Plugin.php:422` |
| POST, DELETE | `/groups/(?P<id>\d+)/cover` | `src/Plugin.php:440` |

---

## apollo-hub

### Pages
| route | type | renderer |
|-------|------|----------|
| `/hub/{username}` | CPT single | `src/TemplateLoader.php:33-39` → `templates/single-hub.php` |
| `/editar-hub` | virtual | `src/Plugin.php:80-91` → BlankCanvas `edit-hub.php` |
| `/home` (code) | virtual | `src/HomePage.php:54-63` → `templates/home/home-page.php` @62 *(conflicts with templates `/home`→`/casa`)* |

### REST
| method(s) | path | file:line |
|-----------|------|-----------|
| GET | `/hubs` | `src/API/HubController.php:41` |
| GET, POST, PUT, PATCH | `/hubs/(?P<username>[a-zA-Z0-9._-]+)` | `src/API/HubController.php:67` |
| GET, POST, PUT, PATCH | `/hubs/(?P<username>[a-zA-Z0-9._-]+)/links` | `src/API/HubController.php:93` |
| GET, POST, PUT, PATCH | `/hubs/(?P<username>[a-zA-Z0-9._-]+)/blocks` | `src/API/HubController.php:144` |
| GET | `/hubs/(?P<username>[a-zA-Z0-9._-]+)/share/(?P<post_id>\d+)` | `src/API/HubController.php:195` |
| GET | `/hubs/me` | `src/API/HubController.php:218` |

---

## apollo-journal

### Pages
| route | type | renderer |
|-------|------|----------|
| `/jornal` | public page | `src/Plugin.php:412-433` → `templates/page-jornal.php` |

### REST
| method(s) | path | file:line |
|-----------|------|-----------|
| GET | `/journal/posts` | `src/API/PostsController.php:33` |
| GET | `/journal/news` | `src/API/PostsController.php:47` |
| GET | `/journal/notas` | `src/API/PostsController.php:61` |

---

## apollo-loc

### Pages
| route | type | renderer |
|-------|------|----------|
| `/local` | CPT archive | `src/TemplateLoader.php:46-52` → `styles/base/archive-local.php` |
| `/local/{slug}` | CPT single | `src/TemplateLoader.php:34-40` → `styles/base/single-local.php` |
| `/mapa` | WP page | page created in Activation; content via shortcode / `page-mapa.php` |

### REST
| method(s) | path | file:line |
|-----------|------|-----------|
| GET, POST | `/local` | `src/API/LocalsController.php:50` (+ duplicate `src/API/Router.php:58`) |
| GET, POST, PUT, PATCH, DELETE | `/local/(?P<id>[\d]+)` | `src/API/LocalsController.php:70` (+ Router.php:87) |
| GET | `/local/proximos` | `src/API/LocalsController.php:103` (+ Router.php:41) |

---

## apollo-login

### Pages
| route | type | renderer |
|-------|------|----------|
| `/acesso` (+ aliases) | virtual | `apollo-login.php:228-307` → `templates/login.php` @297 |
| `/registre` | virtual | same → `templates/register.php` |
| `/sair` | redirect | `apollo-login.php:188-221,274-276` |
| `/reset` | virtual | same → `templates/reset.php` |
| `/verificar-email` | virtual | same → `templates/verify-email.php` |

Map: `includes/functions.php:91-107`. Rewrites: `src/Core/Plugin.php:249-286`.

### REST
| method(s) | path | file:line |
|-----------|------|-----------|
| POST, GET | `/activity/log` | `src/API/ActivityLogController.php:78,107` |
| POST | `/app/auth` | `src/API/AppAuthController.php:90` |
| GET | `/app/verify` | `src/API/AppAuthController.php:124` |
| POST | `/app/revoke` | `src/API/AppAuthController.php:155` |
| GET | `/dj/config` | `src/API/AppAuthController.php:186` |
| GET | `/dj/permissions` | `src/API/AppAuthController.php:197` |
| POST | `/auth/login` | `src/API/AuthController.php:59` |
| POST | `/auth/register` | `src/API/AuthController.php:87` |
| POST | `/auth/logout` | `src/API/AuthController.php:213` |
| POST | `/auth/reset-request` | `src/API/AuthController.php:224` |
| GET | `/auth/check-username` | `src/API/AuthController.php:242` |
| GET | `/auth/check-email` | `src/API/AuthController.php:260` |
| POST | `/auth/reset-confirm` | `src/API/AuthController.php:278` |
| POST | `/auth/verify-email` | `src/API/AuthController.php:306` |
| POST | `/auth/resend-verification` | `src/API/AuthController.php:324` |
| GET | `/quiz/questions` | `src/API/QuizController.php:48` |
| POST | `/quiz/submit` | `src/API/QuizController.php:67` |
| POST | `/simon/submit` | `src/API/QuizController.php:95` |
| GET | `/simon/highscores` | `src/API/QuizController.php:128` |
| GET | `/security/rewrites` | `src/API/SecurityController.php:44` |
| GET | `/security/attempts` | `src/API/SecurityController.php:55` |
| POST | `/auth/token` | `src/Security/JWTAuth.php:121` |
| POST | `/auth/token/refresh` | `src/Security/JWTAuth.php:139` |
| POST | `/auth/token/revoke` | `src/Security/JWTAuth.php:152` |
| POST | `/csp-report` | `src/Security/SecurityHeaders.php:244` |

---

## apollo-maps

### Pages
| — | — | shortcode `[apollo_maps]` only |

### REST
| method(s) | path | file:line |
|-----------|------|-----------|
| GET | `/map/explorer` | `src/API/ExplorerController.php:28` |

---

## apollo-membership

### Pages
| route | type | renderer |
|-------|------|----------|
| `/conquistas`, `/conquista/{slug}`, `/minhas-conquistas`, `/pontos`, `/niveis`, `/nivel/{slug}`, `/placar`, `/evidencia/{hash}` | registry virtual | **no rewrite/handler** — shortcodes only |

### REST
| method(s) | path | file:line |
|-----------|------|-----------|
| GET | `/achievements` | `src/API/AchievementsController.php:35` |
| GET | `/achievements/(?P<id>\d+)` | `src/API/AchievementsController.php:62` |
| GET | `/user-achievements` | `src/API/AchievementsController.php:79` |
| POST | `/membership/achievements/award` | `src/API/AchievementsController.php:106` |
| POST | `/membership/achievements/revoke` | `src/API/AchievementsController.php:128` |
| GET | `/membership/evidence/(?P<achievement_id>\d+)` | `src/API/AchievementsController.php:155` |
| GET | `/membership/verify/(?P<hash>[a-zA-Z0-9]+)` | `src/API/AchievementsController.php:177` |
| GET | `/leaderboard` | `src/API/LeaderboardController.php:31` |
| GET | `/leaderboard/user/(?P<id>\d+)` | `src/API/LeaderboardController.php:48` |
| GET | `/user-summary` | `src/API/LeaderboardController.php:65` |
| GET, POST | `/membership-badge` | `src/API/LeaderboardController.php:82,99` |
| GET | `/points` | `src/API/PointsController.php:33` |
| GET | `/points/history` | `src/API/PointsController.php:50` |
| POST | `/points/award` | `src/API/PointsController.php:82` |
| POST | `/points/deduct` | `src/API/PointsController.php:109` |
| POST | `/points/reset` | `src/API/PointsController.php:136` |
| GET | `/ranks` | `src/API/RanksController.php:32` |
| GET | `/ranks/(?P<id>\d+)` | `src/API/RanksController.php:42` |
| GET | `/user-rank` | `src/API/RanksController.php:59` |
| POST | `/membership/ranks/award` | `src/API/RanksController.php:76` |
| POST | `/report` | `src/API/ReportController.php:53` |
| GET | `/membership/triggers` | `src/API/TriggersController.php:29` |

---

## apollo-mod

### Pages
| — | — | none |

### REST
| method(s) | path | file:line |
|-----------|------|-----------|
| GET | `/mod/queue` | `src/Plugin.php:54` |
| POST | `/mod/queue/(?P<id>\d+)/approve` | `src/Plugin.php:70` |
| POST | `/mod/queue/(?P<id>\d+)/reject` | `src/Plugin.php:82` |
| POST | `/mod/queue/(?P<id>\d+)/flag` | `src/Plugin.php:94` |
| GET | `/mod/log` | `src/Plugin.php:106` |
| GET | `/mod/stats` | `src/Plugin.php:116` |
| POST | `/mod/report` | `src/Plugin.php:127` |

---

## apollo-notif

### Pages
| route | type | renderer |
|-------|------|----------|
| `/notificacoes` | virtual | `src/Plugin.php:132-145` → `templates/notifications.php` |

### REST
| method(s) | path | file:line |
|-----------|------|-----------|
| GET | `/notifications` | `src/Plugin.php:153` |
| POST | `/notifications/(?P<id>\d+)/read` | `src/Plugin.php:195` |
| POST | `/notifications/read-all` | `src/Plugin.php:207` |
| GET | `/notifications/unread-count` | `src/Plugin.php:219` |
| GET, PUT | `/notifications/preferences` | `src/Plugin.php:231` |
| DELETE | `/notifications/(?P<id>\d+)` | `src/Plugin.php:253` |
| DELETE | `/notifications/read` | `src/Plugin.php:266` |
| POST | `/notifications/(?P<id>\d+)/displayed` | `src/Plugin.php:279` |
| POST, DELETE | `/notifications/preferences/snooze` | `src/Plugin.php:292,315` |
| POST | `/notifications/push/subscribe` | `src/Plugin.php:334` |
| DELETE | `/notifications/push/unsubscribe` | `src/Plugin.php:358` |
| GET | `/notifications/push/vapid-public-key` | `src/Plugin.php:377` |

---

## apollo-pane-engine

### Pages
| route | type | renderer |
|-------|------|----------|
| `/painel-lab` | virtual | `includes/functions.php:81-104` → `templates/page-casa.php` @101 |
| `/casa` (if `APOLLO_PANE_PRIMARY`) | virtual | same |

### REST
| method(s) | path | file:line |
|-----------|------|-----------|
| GET | `/pane/plugins` | `includes/fragment-helper.php:126` |
| GET | `/pane/fragment` | `includes/fragment-helper.php:166` |
| GET | `/pane/section/(?P<slug>[a-z0-9-]+)` | `includes/section-renderer.php:17` |
| GET | `/pane/section/chat-thread/(?P<thread_id>\d+)` | `includes/section-renderer.php:36` |
| GET | `/pane/section/{evento\|dj\|local}/(?P<id>\d+)` | `includes/section-renderer.php:75` |

---

## apollo-pwa *(PLANNED — no plugin code)*

### Pages
| route | type | renderer |
|-------|------|----------|
| `/offline` | virtual | — (`offline.php`) |

### REST
| GET | `/pwa/manifest`, `/pwa/sw` | — (routes.php only) |

---

## apollo-radio

### Pages
| — | — | widgets / shortcodes |

### REST
| method(s) | path | file:line |
|-----------|------|-----------|
| GET | `/radio/status` | `src/API/RadioController.php:34` |
| GET | `/radio/now` | `src/API/RadioController.php:43` |
| GET | `/radio/playlist` | `src/API/RadioController.php:52` |
| GET | `/radio/stream` | `src/API/RadioController.php:68` |

---

## apollo-remind

### Pages
| — | — | admin only |

### REST
| method(s) | path | file:line |
|-----------|------|-----------|
| POST | `/remind/push/subscribe` | `src/API/PushController.php:18` |
| DELETE | `/remind/push/unsubscribe` | `src/API/PushController.php:25` |
| GET | `/remind/push/vapid-key` | `src/API/PushController.php:32` |
| GET, POST | `/remind` | `src/API/RemindersController.php:14,26` |
| GET, PUT, DELETE | `/remind/(?P<id>\d+)` | `src/API/RemindersController.php:33,40,47` |
| GET | `/remind/calendar` | `src/API/RemindersController.php:54` |
| POST | `/remind/telegram/link` | `src/API/RemindersController.php:65` |
| GET | `/remind/telegram/status` | `src/API/RemindersController.php:72` |
| GET | `/remind/channels` | `src/API/RemindersController.php:79` |
| POST | `/remind/telegram/webhook` | `src/API/TelegramWebhook.php:23` |

---

## apollo-scheduler

### Pages
| route | type | renderer |
|-------|------|----------|
| `/book` | virtual | `Plugin.php:81-98` → `BookingWizard.php:28-35` → `templates/booking-wizard.php` @35 |
| `/nucleo/{slug}/schedule` | virtual | `ManagerCalendar.php:32-76` → `manager-calendar.php` @76 |
| `/agent/availability` | virtual | `ManagerCalendar.php:44-58` → `agent-availability.php` @58 |

### REST
| method(s) | path | file:line |
|-----------|------|-----------|
| GET | `/scheduler/availability` | `src/API/AvailabilityController.php:24` |
| GET | `/scheduler/availability/grid` | `src/API/AvailabilityController.php:37` |
| POST | `/scheduler/book` | `src/API/BookingController.php:27` |
| POST, PUT, PATCH | `/scheduler/reschedule/(?P<id>\d+)` | `src/API/BookingController.php:49` |
| GET | `/scheduler/ical/(?P<id>\d+)` | `src/API/ICalController.php:23` |
| GET | `/scheduler/nucleo/(?P<nucleo_id>\d+)/catalog` | `src/API/ManagerController.php:26` |
| POST | `/scheduler/agents/assign` | `src/API/ManagerController.php:38` |
| POST | `/scheduler/availability/block` | `src/API/ManagerController.php:56` |

---

## apollo-seo

### Pages
| route | type | renderer |
|-------|------|----------|
| sitemap XML routes | xml | `src/Sitemap.php:80-101` |

### REST
| method(s) | path | file:line |
|-----------|------|-----------|
| GET | `/seo/post/(?P<id>\d+)` | `src/Plugin.php:447` |
| GET | `/seo/term/(?P<id>\d+)` | `src/Plugin.php:463` |
| GET | `/seo/home` | `src/Plugin.php:479` |

---

## apollo-sheets

### Pages
| — | — | none |

### REST
| method(s) | path | file:line |
|-----------|------|-----------|
| GET, POST | `/sheets` | `src/API/SheetsController.php:40` |
| GET, POST, PUT, PATCH, DELETE | `/sheets/(?P<id>[\w]+)` | `src/API/SheetsController.php:60` |
| POST | `/sheets/(?P<id>[\w]+)/copy` | `src/API/SheetsController.php:100` |
| POST | `/sheets/import` | `src/API/SheetsController.php:121` |
| GET | `/sheets/(?P<id>[\w]+)/export` | `src/API/SheetsController.php:135` |
| GET | `/sheets/(?P<id>[\w]+)/preview` | `src/API/SheetsController.php:163` |

---

## apollo-sign

### Pages
| route | type | renderer |
|-------|------|----------|
| `/assinar/{hash}` | virtual | `src/Plugin.php:88-128` → BlankCanvas `templates/sign.php` @125-128 |

### REST
| method(s) | path | file:line |
|-----------|------|-----------|
| POST | `/signatures/request` | `src/API/RequestController.php:39` |
| GET | `/signatures/users` | `src/API/RequestController.php:53` |
| GET | `/signatures/doc/(?P<doc_id>[\d]+)` | `src/API/RequestController.php:66` |
| POST | `/signatures` | `src/API/SignController.php:35` |
| GET | `/signatures/(?P<id>\d+)` | `src/API/SignController.php:53` |
| POST | `/signatures/(?P<id>\d+)/sign` | `src/API/SignController.php:71` |
| GET | `/signatures/(?P<id>\d+)/audit` | `src/API/SignController.php:89` |
| POST, GET | `/signatures/(?P<id>\d+)/placement` | `src/API/SignController.php:107,155` |
| GET | `/signatures/verify/(?P<hash>[a-f0-9]{64})` | `src/API/VerifyController.php:46` |

---

## apollo-social

### Pages
| route | type | renderer |
|-------|------|----------|
| `/feed` | owned by **apollo-templates** | `apollo-templates.php:312-336` → `page-feed.php` |
| `/explore`, `/mural` | redirect 301 | templates → `/feed` |

### REST
| method(s) | path | file:line |
|-----------|------|-----------|
| GET | `/feed` | `src/Plugin.php:84` |
| POST | `/feed/post` | `src/Plugin.php:110` |
| DELETE, PUT | `/activity/(?P<id>\d+)` | `src/Plugin.php:122,165` |
| GET | `/depo/(?P<user_id>\d+)` | `src/Plugin.php:135` |
| GET, POST | `/activity/(?P<id>\d+)/replies` | `src/Plugin.php:146` |
| POST, DELETE | `/activity/(?P<id>\d+)/spam` | `src/Plugin.php:178` |
| GET | `/blocks` | `src/Plugin.php:200` |
| POST, DELETE | `/block/(?P<user_id>\d+)` | `src/Plugin.php:212` |
| GET | `/members` | `src/Plugin.php:234` |
| GET | `/members/suggestions` | `src/Plugin.php:263` |

---

## apollo-statistics

### Pages
| route | type | renderer |
|-------|------|----------|
| `/id/{username}/stats` | virtual | `src/Frontend/ProfileStats.php:47-90` → `templates/profile-stats.php` @90 |

### REST
| method(s) | path | file:line |
|-----------|------|-----------|
| GET | `/stats/overview` | `includes/class-rest-controller.php:34` |
| GET | `/stats/events` | `includes/class-rest-controller.php:54` |
| GET | `/stats/users` | `includes/class-rest-controller.php:66` |
| GET | `/stats/content` | `includes/class-rest-controller.php:78` |
| GET | `/stats/export` | `includes/class-rest-controller.php:106` |
| GET | `/stats/health` | `includes/class-rest-controller.php:132` |
| GET | `/stats/trend` | `includes/class-rest-controller.php:143` |
| GET | `/stats/chart` | `includes/class-rest-controller.php:174` |
| GET | `/profile-stats/(?P<user_id>\d+)` | `src/API/ProfileController.php:32` |
| POST, PUT, PATCH | `/profile-stats/visibility` | `src/API/ProfileController.php:45` |
| GET | `/stats/metrics` | `src/API/StatsController.php:33` |
| GET | `/stats/metric/(?P<slug>[a-z0-9_-]+)` | `src/API/StatsController.php:42` |
| POST, PUT, PATCH | `/stats/metric/(?P<slug>[a-z0-9_-]+)/toggle` | `src/API/StatsController.php:52` |
| GET | `/stats/dashboard` | `src/API/StatsController.php:67` |
| GET | `/stats/profile/(?P<user_id>\d+)` | `src/API/StatsController.php:77` |
| POST | `/track/pageview` | `src/API/TrackController.php:38` |
| POST | `/track/click` | `src/API/TrackController.php:48` |
| POST | `/track/session` | `src/API/TrackController.php:58` |
| POST | `/track/event` | `src/API/TrackController.php:68` |
| POST | `/track/radio` | `src/API/TrackController.php:78` |
| POST | `/track/batch` | `src/API/TrackController.php:88` |

---

## apollo-suppliers *(LOCKED — no code)*

### Pages
| `/fornecedores`, `/fornecedor/{slug}` | registry only | — |

### REST
| `/suppliers*` | routes.php only | — |

---

## apollo-telegram *(not in registry; code exists)*

### Pages
| route | type | renderer |
|-------|------|----------|
| `/telegram` | virtual | `apollo-telegram.php:364-414` → `views/telegram-phone-support.php` @408 |

### REST *(namespace `apollo-telegram/v1` — not `apollo/v1`)*
| method(s) | path | file:line |
|-----------|------|-----------|
| POST | `/request-support-verification` | `src/API/VerificationController.php:26` |
| POST | `/verify-support-code` | `src/API/VerificationController.php:48` |
| POST | `/verification-status` | `src/API/VerificationController.php:80` |
| POST | `/poll-tick` | `src/API/VerificationController.php:107` |
| POST | `/check-phone-available` | `src/API/VerificationController.php:124` |
| GET, POST | `/telegram` | `src/API/VerificationController.php:146` |
| POST | `/broadcast` | `src/API/VerificationController.php:168` |
| (+ BaseEndpoint message routes) | | `src/API/Endpoints/*` |

---

## apollo-templates

### Pages
| route | type | renderer |
|-------|------|----------|
| `/casa` | virtual | `apollo-templates.php:299-309` → `templates/page-home.php` @306 |
| `/feed` | virtual | `apollo-templates.php:312-336` → `templates/page-feed.php` @333 |
| `/sobre` | canvas | `apollo-templates.php:339-347` → `page-sobre.php` @345 |
| `/about-us` | redirect | `288-290` → `/sobre` |
| `/home` | redirect | `283-285` → `/casa` |
| `/explore`, `/mural` | redirect | `293-296` → `/feed` |
| `/test` | virtual | `350-358` → `page-test.php` |
| `/mapa` (WP page) | override | `361-367` → `page-mapa.php` |
| `/editar/{cpt}`, `/editar/{cpt}/{id}` | virtual | `src/FrontendRouter.php:143-198,272-311` (`include` @310) |

### REST
| method(s) | path | file:line |
|-----------|------|-----------|
| GET | `/radar/stats` | `examples/user-radar-examples.php:194` |
| GET | `/radar/top` | `examples/user-radar-examples.php:204` |
| GET | `/templates` | `includes/class-plugin.php:158` |
| GET | `/templates/calendars` | `includes/class-plugin.php:169` |
| POST | `/canvas/save` | `includes/class-plugin.php:180` |
| GET | `/canvas/blocks` | `includes/class-plugin.php:191` |

---

## apollo-users

### Pages
| route | type | renderer |
|-------|------|----------|
| `/id/{username}` | virtual | `apollo-users.php:203-232` → `templates/single-profile.php` @231 |
| `/radar` | virtual | `apollo-users.php:183-200` → `templates/user-radar.php` @198 |
| `/editar-perfil` | virtual | `apollo-users.php:240-256` → `templates/edit-profile.php` @255 |

### REST
| method(s) | path | file:line |
|-----------|------|-----------|
| POST, DELETE | `/profile/avatar` | `src/API/ProfileController.php:45,56` |
| POST, DELETE | `/profile/cover` | `src/API/ProfileController.php:67,78` |
| GET | `/profile/views` | `src/API/ProfileController.php:89` |
| GET, POST, PUT, PATCH | `/users/me` | `src/API/UsersController.php:47,58` |
| GET | `/users/me/matches` | `src/API/UsersController.php:69` |
| GET | `/users/search` | `src/API/UsersController.php:87` |
| GET | `/users/radar` | `src/API/UsersController.php:112` |
| GET | `/users/(?P<username>[a-zA-Z0-9_-]+)` | `src/API/UsersController.php:137` |
| GET | `/users` | `src/API/UsersController.php:155` |
| GET, POST, PUT, PATCH | `/users/(?P<id>\d+)` | `src/API/UsersController.php:188,206` |
| GET, POST, PUT, PATCH | `/users/(?P<id>\d+)/preferences` | `src/API/UsersController.php:224,242` |
| GET | `/users/(?P<id>\d+)/matchmaking` | `src/API/UsersController.php:260` |
| GET, POST, PUT, PATCH | `/users/(?P<id>\d+)/fields` | `src/API/UsersController.php:278,296` |
| GET | `/profile/(?P<username>[a-zA-Z0-9_-]+)` | `src/API/UsersController.php:314` |
| POST | `/profile/(?P<username>[a-zA-Z0-9_-]+)/view` | `src/API/UsersController.php:332` |

---

## apollo-wow

### Pages
| — | — | none |

### REST
| method(s) | path | file:line |
|-----------|------|-----------|
| POST | `/wows` | `src/Plugin.php:47` |
| GET, DELETE | `/wows/(?P<post_id>\d+)` | `src/Plugin.php:57` |
| GET | `/wows/types` | `src/Plugin.php:74` |
| GET | `/wows/chart/(?P<post_id>\d+)` | `src/Plugin.php:84` |

---

## Plugins with neither page routes nor REST in this scan

- `apollo-elementor`, `apollo-elementor-pro` — third-party wrappers  
- `apollo-runtime` — planned, empty  
- `apollo-classifieds` — deprecated (absorbed by adverts)  
- `apollo-shortcodes` — deprecated leftovers in routes.php  

---

## Known conflicts / drifts

1. **`/home`** — apollo-hub HomePage vs apollo-templates redirect to `/casa`  
2. **`/casa`** — apollo-templates vs apollo-pane-engine when `APOLLO_PANE_PRIMARY`  
3. **Events REST** — live `/eventos/*` vs some registry/`routes.php` `/events/*`  
4. **Locs REST** — live `/local/*` vs some maps `/locals/*`  
5. **Membership pages** — listed in registry, **not implemented** as rewrites  
6. **apollo-calendar / apollo-telegram** — live code, weak/absent registry membership  
7. **apollo-cena / suppliers / pwa** — registry/routes only, no plugin code  

---

*End of inventory. Regenerate after major route changes; prefer aligning this file with `apollo-registry.json` + `apollo-core/config/routes.php`.*
