# Taxonomies

**18 declared** in `apollo-core/config/taxonomies.php`.

| slug | owner | object types | hierarchical | rest_base | as-built site |
|---|---|---|---|---|---|
| `sound` | `apollo-core` | — | true | — | `plugins/apollo-djs/src/Registry.php:152`<br>`plugins/apollo-events/src/Registry.php:163` |
| `season` | `apollo-core` | — | true | — | `plugins/apollo-events/src/Registry.php:181` |
| `event_category` | `apollo-core` | — | true | — | `plugins/apollo-events/src/Registry.php:109` |
| `event_type` | `apollo-core` | — | true | — | `plugins/apollo-events/src/Registry.php:127` |
| `event_tag` | `apollo-core` | — | false | — | `plugins/apollo-events/src/Registry.php:145` |
| `local_type` | `apollo-core` | — | true | — | `plugins/apollo-loc/src/CPT/TaxonomyRegistrar.php:34` |
| `local_area` | `apollo-core` | — | true | — | `plugins/apollo-loc/src/CPT/TaxonomyRegistrar.php:57` |
| `classified_domain` | `apollo-core` | — | false | — | _core fallback only_ |
| `classified_intent` | `apollo-core` | — | false | — | _core fallback only_ |
| `doc_folder` | `apollo-core` | — | true | — | `plugins/apollo-docs/src/Core/Registrar.php:70` |
| `doc_type` | `apollo-core` | — | false | — | `plugins/apollo-docs/src/Core/Registrar.php:91` |
| `supplier_category` | `apollo-core` | — | true | — | _core fallback only_ |
| `supplier_service` | `apollo-core` | — | false | — | _core fallback only_ |
| `music` | `apollo-journal` | — | true | — | _core fallback only_ |
| `culture` | `apollo-journal` | — | true | — | _core fallback only_ |
| `rio` | `apollo-journal` | — | true | — | _core fallback only_ |
| `formato` | `apollo-journal` | — | false | — | _core fallback only_ |
| `coauthor` | `apollo-core` | — | false | — | `plugins/apollo-coauthor/src/Components/Taxonomy.php:91` |


## Registration sites as built

| unit | file:line | taxonomy | object type expr | guarded |
|---|---|---|---|---|
| `apollo-adverts` | `plugins/apollo-adverts/includes/cpt.php:75` | _unresolved: APOLLO_TAX_CLASSIFIED_DOMAIN_ | `APOLLO_CPT_CLASSIFIED` | yes |
| `apollo-adverts` | `plugins/apollo-adverts/includes/cpt.php:102` | _unresolved: APOLLO_TAX_CLASSIFIED_INTENT_ | `APOLLO_CPT_CLASSIFIED` | yes |
| `apollo-coauthor` | `plugins/apollo-coauthor/src/Components/Taxonomy.php:91` | `coauthor` | `$post_types` | yes |
| `apollo-core` | `plugins/apollo-core/src/Core/TaxonomyRegistry.php:164` | _unresolved: $slug_ | `$def['object_types']` | **no** |
| `apollo-djs` | `plugins/apollo-djs/src/Registry.php:152` | `sound` | `array(APOLLO_DJ_CPT, 'event')` | yes |
| `apollo-docs` | `plugins/apollo-docs/src/Core/Registrar.php:70` | `doc_folder` | `array( 'doc' )` | yes |
| `apollo-docs` | `plugins/apollo-docs/src/Core/Registrar.php:91` | `doc_type` | `array( 'doc' )` | yes |
| `apollo-events` | `plugins/apollo-events/src/Registry.php:109` | `event_category` | `APOLLO_EVENT_CPT` | yes |
| `apollo-events` | `plugins/apollo-events/src/Registry.php:127` | `event_type` | `APOLLO_EVENT_CPT` | yes |
| `apollo-events` | `plugins/apollo-events/src/Registry.php:145` | `event_tag` | `APOLLO_EVENT_CPT` | yes |
| `apollo-events` | `plugins/apollo-events/src/Registry.php:163` | `sound` | `array(APOLLO_EVENT_CPT, 'dj')` | yes |
| `apollo-events` | `plugins/apollo-events/src/Registry.php:181` | `season` | `array(APOLLO_EVENT_CPT, 'classified')` | yes |
| `apollo-journal` | `plugins/apollo-journal/src/Plugin.php:310` | _unresolved: $slug_ | `array('post', 'journal_news')` | yes |
| `apollo-loc` | `plugins/apollo-loc/src/CPT/TaxonomyRegistrar.php:34` | `local_type` | `APOLLO_LOCAL_CPT` | yes |
| `apollo-loc` | `plugins/apollo-loc/src/CPT/TaxonomyRegistrar.php:57` | `local_area` | `APOLLO_LOCAL_CPT` | yes |


---

_Generated 2026-09-15T05:29:41.847Z from source at `D:/dev/_apollo.rio.br`. Regenerate: `node D:/dev/_cos/verify/gen-dev-registry.js`._
