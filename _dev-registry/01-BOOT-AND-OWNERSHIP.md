# Boot order and ownership

## The load sequence

```
wp-settings.php
  |
  +-- mu-plugins/*.php          file-inclusion loop, ALPHABETICAL, unhookable
  |     apollo-brain.php        <- Apollo bootstrap
  |
  +-- muplugins_loaded
  +-- active_plugins            foreach + require, in stored order
  +-- plugins_loaded
  +-- init                      <- CPTs, taxonomies, shortcodes register here
  +-- template_redirect
```

### The rule that explains everything else

Callbacks registered on the **same hook at the same priority fire in
registration order.** `apollo-core` is required by `apollo-brain.php` before
the normal plugin loop runs, so when `apollo-core` calls
`add_action('init', ..., 5)` it lands ahead of every owner plugin that
registers the same hook later. `apollo-core` therefore **always wins the
race** and registers all CPTs first.

Owner plugins are not dead code — they are the intended long-term owners.
Each wraps its registration in an existence guard:

```php
if ( ! post_type_exists( 'event' ) ) {
    register_post_type( 'event', $args );
}
```

So in the live system the guard is **true**, the owner yields, and
`apollo-core` is the effective registrar. Live confirmation from
`/wp-json/apollo/v1/registry/status`: every CPT reports
`registered_by: apollo-core`, `fallback: true`.

That is why `03-CPT.md` shows one unguarded registration site
(`apollo-core`) and 13 guarded ones. It is a designed fallback
chain, not duplication.

## `apollo-brain.php` hook surface

The mu-plugin publishes these extension points:

| hook | kind | file:line |
|---|---|---|
| `apollo/brain/before_apollo_core` | do_action | `mu-plugin/apollo-brain.php:156` |
| `apollo/brain/after_apollo_core_file` | do_action | `mu-plugin/apollo-brain.php:160` |
| `apollo/brain/bootstrap_complete` | do_action | `mu-plugin/apollo-brain.php:170` |
| `apollo/brain/page_cache_maybe_init` | do_action | `mu-plugin/apollo-brain.php:242` |
| `apollo/brain/defer_protected_handles` | apply_filters | `mu-plugin/apollo-brain.php:320` |
| `apollo/brain/defer_protected_fragments` | apply_filters | `mu-plugin/apollo-brain.php:333` |
| `apollo/brain/core_ready` | do_action | `mu-plugin/apollo-brain.php:358` |
| `apollo/brain/loaded` | do_action | `mu-plugin/apollo-brain.php:599` |


## Ownership doctrine

| layer | owner | responsibility |
|---|---|---|
| L0 boot | `mu-plugin/apollo-brain.php` | load order, asset gating, extension points |
| L0 core | `apollo-core` | CPT / taxonomy / meta / REST fallback registration, config |
| domain | owner plugin | behaviour for its CPT; registration only if core did not |
| render | `apollo-templates` | chrome, shell, `apollo_plus_open()` / `apollo_plus_close()` |


## Units by role

| role | units |
|---|---|
| registers a CPT | `apollo-adverts` `apollo-core` `apollo-djs` `apollo-docs` `apollo-email` `apollo-events` `apollo-hub` `apollo-journal` `apollo-loc` `apollo-scheduler` `apollo-sheets` |
| registers a taxonomy | `apollo-adverts` `apollo-coauthor` `apollo-core` `apollo-djs` `apollo-docs` `apollo-events` `apollo-journal` `apollo-loc` |
| exposes REST | `apollo-admin` `apollo-adverts` `apollo-calendar` `apollo-chat` `apollo-coauthor` `apollo-comment` `apollo-core` `apollo-dashboard` `apollo-dj-sync` `apollo-djs` `apollo-docs` `apollo-email` `apollo-events` `apollo-fav` `apollo-groups` `apollo-hub` `apollo-journal` `apollo-loc` `apollo-login` `apollo-maps` `apollo-membership` `apollo-mod` `apollo-notif` `apollo-pane-engine` `apollo-radio` `apollo-remind` `apollo-scheduler` `apollo-seo` `apollo-sheets` `apollo-sign` `apollo-social` `apollo-statistics` `apollo-telegram` `apollo-templates` `apollo-users` `apollo-wow` |
| no CPT, no REST | `apollo-elementor` `apollo-elementor-pro` `apollo-gestor` `apollo-lux-panels` `apollo-seed-runner` `apollo-soundcloud` `apollo-ui` `mu-plugin` |


---

_Generated 2026-09-15T05:29:41.847Z from source at `D:/dev/_apollo.rio.br`. Regenerate: `node D:/dev/_cos/verify/gen-dev-registry.js`._
