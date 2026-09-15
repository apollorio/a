# Cross-plugin dependencies

## Global function providers

876 global `apollo_*` functions are defined across the ecosystem. Namespaced
functions are excluded — `Apollo\Admin\apollo_is_plugin_active` is not the
global `apollo_is_plugin_active`, and conflating them invents dependencies
that do not exist.

| unit | global functions defined |
|---|---|
| `apollo-core` | 142 |
| `apollo-adverts` | 133 |
| `apollo-membership` | 110 |
| `apollo-events` | 71 |
| `apollo-templates` | 59 |
| `apollo-chat` | 50 |
| `apollo-djs` | 47 |
| `apollo-groups` | 45 |
| `apollo-ui` | 34 |
| `apollo-pane-engine` | 25 |
| `mu-plugin` | 19 |
| `apollo-loc` | 16 |
| `apollo-telegram` | 16 |
| `apollo-fav` | 14 |
| `apollo-notif` | 13 |
| `apollo-hub` | 10 |
| `apollo-coauthor` | 9 |
| `apollo-email` | 9 |
| `apollo-wow` | 8 |
| `apollo-remind` | 7 |
| `apollo-soundcloud` | 7 |
| `apollo-social` | 6 |
| `apollo-statistics` | 6 |
| `apollo-mod` | 5 |
| `apollo-sheets` | 4 |
| `apollo-dashboard` | 3 |
| `apollo-elementor` | 3 |
| `apollo-lux-panels` | 3 |
| `apollo-admin` | 2 |


## Unguarded cross-plugin calls

**43 call sites** invoke a global function defined in exactly one *other*
unit, with no `function_exists()` guard reachable earlier in the file. If the
defining plugin is deactivated, each is a fatal `Error: Call to undefined
function`.

| caller | provider | sites | functions | examples |
|---|---|---|---|---|
| apollo-events | apollo-templates | 10 | 4 | `apollo_render_navbar()` `apollo_plus_close()` `apollo_plus_open()` … |
| apollo-admin | apollo-core | 8 | 3 | `apollo_cdn_core_js_url()` `apollo_render_blank_canvas_open()` `apollo_render_document_close()` |
| apollo-adverts | apollo-templates | 4 | 2 | `apollo_plus_close()` `apollo_render_navbar()` |
| apollo-groups | apollo-templates | 4 | 1 | `apollo_plus_close()` |
| apollo-adverts | apollo-core | 3 | 2 | `apollo_safety_url()` `apollo_safety_render()` |
| apollo-login | apollo-core | 2 | 1 | `apollo_membership_assign()` |
| apollo-users | apollo-core | 2 | 2 | `apollo_get_membership_badge()` `apollo_get_client_ip()` |
| apollo-users | apollo-templates | 2 | 1 | `apollo_render_navbar()` |
| apollo-chat | apollo-membership | 1 | 1 | `apollo_get_membership_badge_html()` |
| apollo-djs | apollo-templates | 1 | 1 | `apollo_render_navbar()` |
| apollo-docs | apollo-templates | 1 | 1 | `apollo_render_navbar()` |
| apollo-hub | apollo-templates | 1 | 1 | `apollo_plus_close()` |
| apollo-maps | apollo-core | 1 | 1 | `apollo_cdn_core_js_url()` |
| apollo-membership | apollo-core | 1 | 1 | `apollo_cdn_core_js_url()` |
| apollo-notif | apollo-templates | 1 | 1 | `apollo_render_navbar()` |
| apollo-users | apollo-membership | 1 | 1 | `apollo_membership_get_badge_info()` |


### Every unguarded site

| caller | provider | function | file:line |
|---|---|---|---|
| `apollo-admin` | `apollo-core` | `apollo_cdn_core_js_url()` | `plugins/apollo-admin/templates/frontend/memberships.php:48` |
| `apollo-admin` | `apollo-core` | `apollo_cdn_core_js_url()` | `plugins/apollo-admin/templates/frontend/memberships.php:48` |
| `apollo-admin` | `apollo-core` | `apollo_cdn_core_js_url()` | `plugins/apollo-admin/templates/frontend/panel.php:72` |
| `apollo-admin` | `apollo-core` | `apollo_cdn_core_js_url()` | `plugins/apollo-admin/templates/frontend/panel.php:72` |
| `apollo-admin` | `apollo-core` | `apollo_cdn_core_js_url()` | `plugins/apollo-admin/templates/frontend/pending.php:74` |
| `apollo-admin` | `apollo-core` | `apollo_cdn_core_js_url()` | `plugins/apollo-admin/templates/frontend/pending.php:74` |
| `apollo-admin` | `apollo-core` | `apollo_render_blank_canvas_open()` | `plugins/apollo-admin/templates/modera/parts/head.php:37` |
| `apollo-admin` | `apollo-core` | `apollo_render_document_close()` | `plugins/apollo-admin/templates/modera/parts/scripts.php:43` |
| `apollo-adverts` | `apollo-templates` | `apollo_plus_close()` | `plugins/apollo-adverts/src/Plugin.php:349` |
| `apollo-adverts` | `apollo-templates` | `apollo_plus_close()` | `plugins/apollo-adverts/templates/archive-classified.php:79` |
| `apollo-adverts` | `apollo-templates` | `apollo_plus_close()` | `plugins/apollo-adverts/templates/my-listings.php:50` |
| `apollo-adverts` | `apollo-templates` | `apollo_render_navbar()` | `plugins/apollo-adverts/templates/marketplace/classifieds-page.php:157` |
| `apollo-adverts` | `apollo-core` | `apollo_safety_render()` | `plugins/apollo-adverts/templates/safety/gate.php:76` |
| `apollo-adverts` | `apollo-core` | `apollo_safety_url()` | `plugins/apollo-adverts/includes/integrations.php:296` |
| `apollo-adverts` | `apollo-core` | `apollo_safety_url()` | `plugins/apollo-adverts/includes/safety-gate.php:217` |
| `apollo-chat` | `apollo-membership` | `apollo_get_membership_badge_html()` | `plugins/apollo-chat/includes/functions.php:1720` |
| `apollo-djs` | `apollo-templates` | `apollo_render_navbar()` | `plugins/apollo-djs/styles/base/_legacy/single-dj.monolith.php:158` |
| `apollo-docs` | `apollo-templates` | `apollo_render_navbar()` | `plugins/apollo-docs/templates/frontend-documents.php:74` |
| `apollo-events` | `apollo-templates` | `apollo_listing_header_months()` | `plugins/apollo-events/styles/base/template-parts/archive/portal/header.php:83` |
| `apollo-events` | `apollo-templates` | `apollo_plus_close()` | `plugins/apollo-events/styles/base/archive-event.php:336` |
| `apollo-events` | `apollo-templates` | `apollo_plus_close()` | `plugins/apollo-events/styles/base/create-event.php:398` |
| `apollo-events` | `apollo-templates` | `apollo_plus_close()` | `plugins/apollo-events/styles/base/dashboard-event.php:143` |
| `apollo-events` | `apollo-templates` | `apollo_plus_close()` | `plugins/apollo-events/styles/base/dashboard-meus.php:55` |
| `apollo-events` | `apollo-templates` | `apollo_plus_close()` | `plugins/apollo-events/styles/base/url-import.php:96` |
| `apollo-events` | `apollo-templates` | `apollo_plus_open()` | `plugins/apollo-events/styles/base/create-event.php:360` |
| `apollo-events` | `apollo-templates` | `apollo_plus_open()` | `plugins/apollo-events/styles/base/dashboard-event.php:105` |
| `apollo-events` | `apollo-templates` | `apollo_render_navbar()` | `plugins/apollo-events/styles/apollo-v2/single-dj.php:403` |
| `apollo-events` | `apollo-templates` | `apollo_render_navbar()` | `plugins/apollo-events/styles/apollo-v2/single-loc.php:477` |
| `apollo-groups` | `apollo-templates` | `apollo_plus_close()` | `plugins/apollo-groups/templates/comunas-gestao.php:58` |
| `apollo-groups` | `apollo-templates` | `apollo_plus_close()` | `plugins/apollo-groups/templates/comunas.php:37` |
| `apollo-groups` | `apollo-templates` | `apollo_plus_close()` | `plugins/apollo-groups/templates/create-group.php:36` |
| `apollo-groups` | `apollo-templates` | `apollo_plus_close()` | `plugins/apollo-groups/templates/nucleos-meus.php:69` |
| `apollo-hub` | `apollo-templates` | `apollo_plus_close()` | `plugins/apollo-hub/templates/directory.php:72` |
| `apollo-login` | `apollo-core` | `apollo_membership_assign()` | `plugins/apollo-login/src/Auth/RegisterHandler.php:496` |
| `apollo-login` | `apollo-core` | `apollo_membership_assign()` | `plugins/apollo-login/src/Auth/RegisterHandler.php:928` |
| `apollo-maps` | `apollo-core` | `apollo_cdn_core_js_url()` | `plugins/apollo-maps/src/Plugin.php:79` |
| `apollo-membership` | `apollo-core` | `apollo_cdn_core_js_url()` | `plugins/apollo-membership/src/Plugin.php:197` |
| `apollo-notif` | `apollo-templates` | `apollo_render_navbar()` | `plugins/apollo-notif/templates/notifications.php:483` |
| `apollo-users` | `apollo-core` | `apollo_get_client_ip()` | `plugins/apollo-users/src/API/UsersController.php:710` |
| `apollo-users` | `apollo-core` | `apollo_get_membership_badge()` | `plugins/apollo-users/includes/functions.php:174` |
| `apollo-users` | `apollo-membership` | `apollo_membership_get_badge_info()` | `plugins/apollo-users/src/Components/DepoimentoHandler.php:96` |
| `apollo-users` | `apollo-templates` | `apollo_render_navbar()` | `plugins/apollo-users/templates/edit-profile.php:1207` |
| `apollo-users` | `apollo-templates` | `apollo_render_navbar()` | `plugins/apollo-users/templates/single-profile.php:2726` |


---

_Generated 2026-09-15T05:29:41.847Z from source at `D:/dev/_apollo.rio.br`. Regenerate: `node D:/dev/_cos/verify/gen-dev-registry.js`._
