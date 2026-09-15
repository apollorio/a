# Units

Every `apollo-*` plugin plus the mu-plugin. `ABSPATH` is the direct-access
guard in the entry file — a missing one means the entry file can be requested
over HTTP and executed outside WordPress.

| unit | ver | files | LOC | ABSPATH | cls | cpt | rest | sc | ajax | boot hooks in entry |
|---|---|---|---|---|---|---|---|---|---|---|
| `apollo-admin` | 1.1.0 | 123 | 16,113 | yes | 28 | 0 | 18 | 0 | 4 | plugins_loaded |
| `apollo-adverts` | 1.2.2 | 88 | 16,633 | yes | 16 | 1 | 9 | 7 | 7 | plugins_loaded init |
| `apollo-calendar` | 1.1.0 | 16 | 2,919 | yes | 12 | 0 | 5 | 0 | 0 | plugins_loaded |
| `apollo-chat` | 2.2.8 | 9 | 4,939 | yes | 3 | 0 | 30 | 0 | 0 | plugins_loaded |
| `apollo-coauthor` | 1.0.2 | 18 | 3,765 | yes | 14 | 0 | 3 | 2 | 0 | plugins_loaded |
| `apollo-comment` | 1.0.1 | 10 | 1,685 | yes | 6 | 0 | 2 | 2 | 0 | plugins_loaded |
| `apollo-core` | 6.6.0 | 77 | 23,856 | yes | 38 | 1 | 20 | 0 | 0 | init rest_api_init plugins_loaded |
| `apollo-dashboard` | 1.1.0 | 16 | 4,386 | yes | 3 | 0 | 4 | 2 | 0 | plugins_loaded |
| `apollo-dj-sync` | 1.0.1 | 5 | 795 | yes | 3 | 0 | 1 | 0 | 0 | rest_api_init |
| `apollo-djs` | 1.1.7 | 87 | 11,389 | yes | 10 | 2 | 5 | 4 | 1 | plugins_loaded |
| `apollo-docs` | 1.0.1 | 13 | 2,750 | yes | 9 | 1 | 15 | 0 | 1 | plugins_loaded |
| `apollo-elementor` | 1.0.1 | 49 | 3,967 | yes | 27 | 0 | 0 | 0 | 0 | plugins_loaded |
| `apollo-elementor-pro` | 1.0.1 | 23 | 1,738 | yes | 21 | 0 | 0 | 0 | 0 | plugins_loaded |
| `apollo-email` | 1.0.1 | 43 | 10,080 | yes | 19 | 1 | 16 | 2 | 0 | plugins_loaded init |
| `apollo-events` | 1.7.16 | 150 | 31,367 | yes | 31 | 1 | 29 | 4 | 1 | plugins_loaded |
| `apollo-fav` | 1.0.1 | 11 | 4,100 | yes | 8 | 0 | 5 | 5 | 6 | plugins_loaded rest_api_init |
| `apollo-gestor` | 1.0.1 | 46 | 6,934 | yes | 24 | 0 | 0 | 0 | 1 | plugins_loaded |
| `apollo-groups` | 1.2.0 | 55 | 7,271 | yes | 4 | 0 | 24 | 5 | 0 | plugins_loaded |
| `apollo-hub` | 1.0.2 | 35 | 5,417 | yes | 11 | 2 | 6 | 2 | 0 | plugins_loaded |
| `apollo-journal` | 1.0.1 | 16 | 4,543 | yes | 7 | 2 | 3 | 5 | 0 | plugins_loaded |
| `apollo-loc` | 1.0.5 | 66 | 9,023 | yes | 39 | 1 | 6 | 4 | 0 | plugins_loaded |
| `apollo-login` | 1.0.47 | 54 | 15,603 | yes | 23 | 0 | 26 | 6 | 20 | plugins_loaded template_redirect |
| `apollo-lux-panels` | 1.2.0 | 6 | 1,758 | yes | 6 | 0 | 0 | 0 | 0 | plugins_loaded |
| `apollo-maps` | 1.0.1 | 9 | 850 | yes | 5 | 0 | 1 | 1 | 0 | plugins_loaded |
| `apollo-membership` | 1.0.3 | 42 | 8,015 | yes | 12 | 0 | 23 | 9 | 6 | plugins_loaded |
| `apollo-mod` | 1.0.1 | 7 | 990 | yes | 4 | 0 | 7 | 0 | 1 | plugins_loaded |
| `apollo-notif` | 1.0.1 | 8 | 3,731 | yes | 4 | 0 | 13 | 2 | 0 | plugins_loaded |
| `apollo-pane-engine` | 2.0.1 | 7 | 1,719 | yes | 0 | 0 | 5 | 0 | 0 | — |
| `apollo-radio` | 1.0.1 | 9 | 990 | yes | 5 | 0 | 4 | 1 | 0 | plugins_loaded |
| `apollo-remind` | 1.0.1 | 16 | 2,135 | yes | 11 | 0 | 13 | 0 | 0 | plugins_loaded |
| `apollo-scheduler` | 1.0.1 | 34 | 2,929 | yes | 26 | 1 | 8 | 2 | 0 | plugins_loaded |
| `apollo-seed-runner` | 1.0.0 | 1 | 59 | yes | 0 | 0 | 0 | 0 | 0 | — |
| `apollo-seo` | 1.0.6 | 10 | 4,736 | yes | 8 | 0 | 3 | 0 | 0 | plugins_loaded |
| `apollo-sheets` | 1.0.1 | 27 | 8,565 | yes | 19 | 1 | 6 | 2 | 13 | plugins_loaded |
| `apollo-sign` | 1.2.1 | 39 | 7,965 | yes | 10 | 0 | 10 | 1 | 1 | plugins_loaded |
| `apollo-social` | 1.0.1 | 24 | 3,873 | yes | 7 | 0 | 11 | 2 | 0 | plugins_loaded |
| `apollo-soundcloud` | 1.0.3 | 4 | 598 | yes | 0 | 0 | 0 | 1 | 0 | — |
| `apollo-statistics` | 2.0.8 | 47 | 10,573 | yes | 42 | 0 | 21 | 2 | 5 | plugins_loaded |
| `apollo-telegram` | 1.1.5 | 60 | 11,092 | yes | 49 | 0 | 8 | 1 | 0 | rest_api_init init plugins_loaded template_redirect |
| `apollo-templates` | 1.6.24 | 130 | 28,321 | yes | 9 | 0 | 6 | 6 | 11 | plugins_loaded template_redirect |
| `apollo-ui` | 1.0.0 | 7 | 1,144 | yes | 0 | 0 | 0 | 1 | 0 | init |
| `apollo-users` | 1.1.0 | 30 | 13,313 | yes | 11 | 0 | 21 | 6 | 13 | plugins_loaded init template_redirect |
| `apollo-wow` | 1.0.1 | 6 | 626 | yes | 3 | 0 | 4 | 2 | 0 | plugins_loaded |
| **mu-plugin** | 1.2.0 | 16 | 4,540 | yes | 3 | 0 | 0 | 0 | 0 | plugins_loaded template_redirect muplugins_loaded |


## Units missing an `ABSPATH` guard in the entry file

None. Every entry file guards direct access.

## `Requires Plugins:` headers

None declared. Dependency order is implicit — see `11-DEPENDENCIES.md`.

---

_Generated 2026-09-15T05:29:41.847Z from source at `D:/dev/_apollo.rio.br`. Regenerate: `node D:/dev/_cos/verify/gen-dev-registry.js`._
