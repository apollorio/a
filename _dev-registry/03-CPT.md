# Custom post types

**15 declared** in `apollo-core/config/cpts.php`, evaluated by PHP.
Live `/wp-json/apollo/v1/registry/status` reports all of them as
`registered_by: apollo-core`, `fallback: true` — see `01-BOOT-AND-OWNERSHIP.md`.

## Registration sites as built

| unit | file:line | post type | via | guarded |
|---|---|---|---|---|
| `apollo-adverts` | `plugins/apollo-adverts/includes/cpt.php:42` | `classified` | constant_define | yes |
| `apollo-core` | `plugins/apollo-core/src/Core/CPTRegistry.php:180` | _unresolved: $slug_ | **unresolved** | **no** |
| `apollo-djs` | `plugins/apollo-djs/src/Registry.php:56` | `dj` | constant_define | yes |
| `apollo-djs` | `plugins/apollo-djs/src/Registry.php:122` | `track` | literal | yes |
| `apollo-docs` | `plugins/apollo-docs/src/Core/Registrar.php:31` | `doc` | literal | yes |
| `apollo-email` | `plugins/apollo-email/src/Core/CPT.php:32` | `email_aprio` | literal | yes |
| `apollo-events` | `plugins/apollo-events/src/Registry.php:69` | `event` | constant_define | yes |
| `apollo-hub` | `plugins/apollo-hub/src/Activation.php:45` | `hub` | constant_define | yes |
| `apollo-hub` | `plugins/apollo-hub/src/Registry.php:58` | `hub` | constant_define | yes |
| `apollo-journal` | `plugins/apollo-journal/src/Plugin.php:117` | `journal_news` | literal | yes |
| `apollo-journal` | `plugins/apollo-journal/src/Plugin.php:168` | `journal_nota` | literal | yes |
| `apollo-loc` | `plugins/apollo-loc/src/CPT/CPTRegistrar.php:29` | `local` | constant_define | yes |
| `apollo-scheduler` | `plugins/apollo-scheduler/src/Registry.php:70` | _unresolved: $slug_ | **unresolved** | yes |
| `apollo-sheets` | `plugins/apollo-sheets/src/Plugin.php:113` | `apollo_sheet` | constant_define | yes |


The single unguarded site is `apollo-core`'s `CPTRegistry`, which is the
fallback registrar by design and passes the slug dynamically.

## Registered but NOT declared in `cpts.php`

These post types exist in code with no entry in the central config, so they
have **no core fallback** and no governed meta. If the owner plugin is
deactivated they disappear entirely, and nothing in `apollo-core` knows
about them.

| slug | unit | file:line |
|---|---|---|
| `journal_news` | `apollo-journal` | `plugins/apollo-journal/src/Plugin.php:117` |
| `journal_nota` | `apollo-journal` | `plugins/apollo-journal/src/Plugin.php:168` |


## Declared CPTs in full

### `event`

| field | value |
|---|---|
| owner | `apollo-events` |
| rewrite slug | `/evento/` |
| archive | `/eventos/` |
| rest_base | `events` |
| public | true |
| has_archive | true |
| map_meta_cap | true |
| supports | `title`, `editor`, `thumbnail`, `author` |
| menu_icon | `dashicons-calendar-alt` |
| menu label | Eventos |
| governed meta keys | 18 |
| as-built registration | `plugins/apollo-events/src/Registry.php:69` (guarded) |


### `dj`

| field | value |
|---|---|
| owner | `apollo-djs` |
| rewrite slug | `/dj/` |
| archive | `/djs/` |
| rest_base | `djs` |
| public | true |
| has_archive | true |
| map_meta_cap | _(unset -> core coerces to `false`)_ |
| supports | `title`, `editor`, `thumbnail`, `author` |
| menu_icon | `dashicons-format-audio` |
| menu label | DJs |
| governed meta keys | 11 |
| as-built registration | `plugins/apollo-djs/src/Registry.php:56` (guarded) |


### `track`

| field | value |
|---|---|
| owner | `apollo-djs` |
| rewrite slug | `/track/` |
| archive | `/tracks/` |
| rest_base | `tracks` |
| public | true |
| has_archive | true |
| map_meta_cap | _(unset -> core coerces to `false`)_ |
| supports | `title`, `editor`, `thumbnail`, `author` |
| menu_icon | `dashicons-album` |
| menu label | Out Now |
| governed meta keys | 0 |
| as-built registration | `plugins/apollo-djs/src/Registry.php:122` (guarded) |


### `hostel`

| field | value |
|---|---|
| owner | `apollo-adverts` |
| rewrite slug | `/hostel/` |
| archive | `/hostels/` |
| rest_base | `hostels` |
| public | true |
| has_archive | true |
| map_meta_cap | _(unset -> core coerces to `false`)_ |
| supports | `title`, `editor`, `thumbnail` |
| menu_icon | `dashicons-building` |
| menu label | Hostels |
| governed meta keys | 0 |
| as-built registration | _core fallback only — no literal owner call found_ |


### `local`

| field | value |
|---|---|
| owner | `apollo-loc` |
| rewrite slug | `/local/` |
| archive | `/locais/` |
| rest_base | `local` |
| public | true |
| has_archive | true |
| map_meta_cap | _(unset -> core coerces to `false`)_ |
| supports | `title`, `editor`, `thumbnail` |
| menu_icon | `dashicons-location` |
| menu label | Locais |
| governed meta keys | 13 |
| as-built registration | `plugins/apollo-loc/src/CPT/CPTRegistrar.php:29` (guarded) |


### `classified`

| field | value |
|---|---|
| owner | `apollo-adverts` |
| rewrite slug | `/anuncio/` |
| archive | `/anuncios/` |
| rest_base | `classifieds` |
| public | true |
| has_archive | true |
| map_meta_cap | true |
| supports | `title`, `editor`, `thumbnail`, `author`, `excerpt` |
| menu_icon | `dashicons-megaphone` |
| menu label | Classificados |
| governed meta keys | 9 |
| as-built registration | `plugins/apollo-adverts/includes/cpt.php:42` (guarded) |


### `supplier`

| field | value |
|---|---|
| owner | `apollo-suppliers` |
| rewrite slug | `/fornecedor/` |
| archive | `/fornecedores/` |
| rest_base | `suppliers` |
| public | false |
| has_archive | false |
| map_meta_cap | _(unset -> core coerces to `false`)_ |
| supports | `title`, `editor`, `thumbnail` |
| menu_icon | `dashicons-store` |
| menu label | Fornecedores |
| governed meta keys | 9 |
| as-built registration | _core fallback only — no literal owner call found_ |


### `doc`

| field | value |
|---|---|
| owner | `apollo-docs` |
| rewrite slug | `/documento/` |
| archive | false |
| rest_base | `docs` |
| public | false |
| has_archive | false |
| map_meta_cap | _(unset -> core coerces to `false`)_ |
| supports | `title`, `editor`, `author` |
| menu_icon | `dashicons-media-document` |
| menu label | Documentos |
| governed meta keys | 8 |
| as-built registration | `plugins/apollo-docs/src/Core/Registrar.php:31` (guarded) |


### `email_aprio`

| field | value |
|---|---|
| owner | `apollo-email` |
| rewrite slug | — |
| archive | false |
| rest_base | `email-templates` |
| public | false |
| has_archive | false |
| map_meta_cap | _(unset -> core coerces to `false`)_ |
| supports | `title`, `editor` |
| menu_icon | `dashicons-email-alt` |
| menu label | Email Templates |
| governed meta keys | 3 |
| as-built registration | `plugins/apollo-email/src/Core/CPT.php:32` (guarded) |


### `hub`

| field | value |
|---|---|
| owner | `apollo-hub` |
| rewrite slug | `/hub/` |
| archive | false |
| rest_base | `hubs` |
| public | true |
| has_archive | false |
| map_meta_cap | _(unset -> core coerces to `false`)_ |
| supports | `title`, `author` |
| menu_icon | `dashicons-admin-links` |
| menu label | Hubs |
| governed meta keys | 7 |
| as-built registration | `plugins/apollo-hub/src/Activation.php:45` (guarded)<br>`plugins/apollo-hub/src/Registry.php:58` (guarded) |


### `apollo_sheet`

| field | value |
|---|---|
| owner | `apollo-sheets` |
| rewrite slug | — |
| archive | false |
| rest_base | `sheets` |
| public | false |
| has_archive | false |
| map_meta_cap | true |
| supports | `title`, `editor`, `excerpt`, `revisions`, `author` |
| menu_icon | `dashicons-editor-table` |
| menu label | Sheets |
| governed meta keys | 0 |
| as-built registration | `plugins/apollo-sheets/src/Plugin.php:113` (guarded) |


### `appointment`

| field | value |
|---|---|
| owner | `apollo-scheduler` |
| rewrite slug | `/agendamento/` |
| archive | `/agendamentos/` |
| rest_base | `appointments` |
| public | true |
| has_archive | true |
| map_meta_cap | _(unset -> core coerces to `false`)_ |
| supports | `title`, `editor`, `author` |
| menu_icon | `dashicons-calendar` |
| menu label | Agendamentos |
| governed meta keys | 0 |
| as-built registration | _core fallback only — no literal owner call found_ |


### `service`

| field | value |
|---|---|
| owner | `apollo-scheduler` |
| rewrite slug | `/servico/` |
| archive | `/servicos/` |
| rest_base | `services` |
| public | true |
| has_archive | true |
| map_meta_cap | _(unset -> core coerces to `false`)_ |
| supports | `title`, `editor`, `author` |
| menu_icon | `dashicons-hammer` |
| menu label | Serviços |
| governed meta keys | 0 |
| as-built registration | _core fallback only — no literal owner call found_ |


### `resource`

| field | value |
|---|---|
| owner | `apollo-scheduler` |
| rewrite slug | `/recurso/` |
| archive | `/recursos/` |
| rest_base | `resources` |
| public | true |
| has_archive | true |
| map_meta_cap | _(unset -> core coerces to `false`)_ |
| supports | `title`, `editor`, `author` |
| menu_icon | `dashicons-building` |
| menu label | Recursos |
| governed meta keys | 0 |
| as-built registration | _core fallback only — no literal owner call found_ |


### `apollo_agent_log`

| field | value |
|---|---|
| owner | `apollo-membership` |
| rewrite slug | — |
| archive | false |
| rest_base | `agent-log` |
| public | false |
| has_archive | false |
| map_meta_cap | _(unset -> core coerces to `false`)_ |
| supports | `title` |
| menu_icon | `dashicons-list-view` |
| menu label | Agent Actions |
| governed meta keys | 0 |
| as-built registration | _core fallback only — no literal owner call found_ |



---

_Generated 2026-09-15T05:29:41.847Z from source at `D:/dev/_apollo.rio.br`. Regenerate: `node D:/dev/_cos/verify/gen-dev-registry.js`._
