# Ecosystem audit — frontend modularity & the "invisible lines"

**Date** 2026-08-07 · **Scope** apollo-core, apollo-events, apollo-djs, apollo-loc
and every plugin that renders to the frontend (29 with asset enqueues, 40 on disk).

---

## The question asked

> "if all rendered code to frontend are blocks, modular, easy to plug and play"

**No.** One plugin is. The pattern exists, is proven in production, and had never
been extracted — so every other plugin either reinvents a piece of it or goes
without.

### Contract coverage, before this session

| Contract | events | djs | loc | hub | social | users | adverts |
|---|---|---|---|---|---|---|---|
| Render SSOT (`render_single()` + parts) | ✅ | ✗ | ✗ | ✗ | ✗ | ✗ | ✗ |
| Card contract (`data-*-open` + real href) | ✅ 31 uses | ✗ | ✗ | ✗ | ✗ | ✗ | ✗ |
| Public enqueue API | ✅ 2 | ✗ | ✗ | ✗ | ✗ | ✗ | ✗ |
| Cache-safe asset versions | ✅ | ✗ | ✗ | ✗ | ✗ | ✗ | ✗ |
| REST fragment endpoint | ✅ | ✗ | ✗ | ✗ | ✗ | ✗ | ✗ |
| Template parts | 89 | 11 | 12 | 0 | 0 | 0 | 0 |

`data-dj-open` and `data-loc-open` do not exist anywhere in the codebase.
apollo-djs and apollo-loc register **zero** REST routes between them.

---

## Finding 1 — the frozen version constant *(fixed)*

**Severity: this was silently invalidating every CSS/JS deploy.**

26 of 29 asset-enqueuing plugins stamped assets with a hand-bumped constant:

```php
wp_enqueue_script( 'x', $url, [], APOLLO_X_VERSION, true );   // '1.7.0', for months
```

This folder deploys on save and sits behind Cloudflare, which caches by URL. So
the file changed, the URL did not, and the fix was **correct on disk and stale in
the browser** — for returning visitors and for the CDN edge.

Not theoretical. The event lightbox rendered as a blank white panel through two
full rounds of "verified on disk" fixes this session. A hard reload did not help;
only a unique query string did — the signature of an edge cache keyed on a URL
that never moves.

**Fixed:** `apollo-core/includes/asset-version.php` filters `script_loader_src`
and `style_loader_src`, appending each file's mtime to whatever version the
plugin declared. `?ver=1.7.0` → `?ver=1.7.0.1786146186`. Zero edits to the other
26 plugins.

Scope-gated to the plugins directory **and** to `apollo-*`, so it can never touch
the CDN (several of those tags carry SRI hashes), WordPress core, or a
third-party plugin. Idempotent, traversal-guarded. Harness **E19**.

## Finding 2 — the canvas bypasses WordPress's asset pipeline *(helper added, conversion pending)*

Blank Canvas templates deliberately skip `wp_head`/`wp_footer`, so plugins that
need an asset on a canvas screen **print the tag themselves**:

```php
printf( '<script src="%s"></script>',
    esc_url( APOLLO_STATS_URL . 'assets/js/tracker.js?v=' . APOLLO_STATS_VERSION ) );
```

Those tags never reach `WP_Scripts`, so Finding 1's filter cannot see them — and
since the canvas *is* the app, that is most of the surface that matters.
Confirmed live: after the filter shipped, `tracker.js?v=2.0.6`
(apollo-statistics/src/Plugin.php:283) was still the one unstamped apollo asset
on `/portal` and `/feed`.

**Added:** `apollo_asset_url( $dir, $url, $relative, $fallback )` in the same
file — same output shape as the filter, so the two paths cannot disagree.

**Pending — 25 hand-print sites to convert** (one line each, no logic change):

| Plugin | Sites |
|---|---|
| apollo-adverts | shortcodes/apollo-marketplace-forms.php:108,272 · templates/create-classified.php:36,37 · marketplace/classifieds-page.php:29 · single-classified.php:54,55 |
| apollo-chat | templates/chat.php:42,43,304 · template-parts/chat/styles.php:5 |
| apollo-events | includes/render-single.php:935,989,990 · template-parts/dashboard/scripts.php:102 · template-parts/single/scripts.php:49,50 *(already mtime-based — reference impl)* |
| apollo-statistics | src/Plugin.php:283 |
| apollo-djs | templates/single-dj.php:156 |
| apollo-calendar | templates/agenda.php:17 |
| apollo-core | src/Core/CDN.php:100,136 · src/Traits/BlankCanvasTrait.php:151 *(verify — CDN URLs must stay untouched)* |

## Finding 3 — no shared surface contract *(fixed)*

**Added:** `apollo-core/includes/surface-contract.php`. A plugin opts in with one
call and gets the fragment endpoint, the card contract and the lightbox for free:

```php
apollo_surface_register( 'dj', array(
    'post_type' => 'dj',
    'rest_base' => 'djs',
    'renderer'  => 'apollo_dj_render_single',
    'can_view'  => 'apollo_dj_can_view',
) );
```

Core then provides `GET apollo/v1/{rest_base}/{id}/fragmento`,
`apollo_surface_open_attrs()` (emits `data-ap-open="dj:123"` **and** the real
permalink together, so a card cannot ship without its fallback), and
`apollo_surface_render()`.

Key safety properties, each harness-gated (**E17**, **E18**):

- **Dormant-safe.** A surface whose renderer is not callable registers no route
  and returns `''` from `open_attrs()`. Declaring intent before you can render
  cannot break a request; the surface switches on when the callable appears.
- **Never clobbers.** Route registration checks `rest_get_server()->get_routes()`
  first — apollo-events already owns `apollo/v1/eventos/{id}/fragmento` and
  replacing that handler would take `/eventos` and `/portal` down together.
- **Fails closed.** A surface without `can_view` falls back to published-only,
  so an unguarded surface cannot leak a draft through the public endpoint.
- **Registers nothing.** No CPT, meta key, taxonomy or table — apollo-core's
  existing registries remain the only place those happen.

## Finding 4 — one lightbox, every surface *(fixed)*

The runtime now understands two spellings:

- `data-ev-open="123"` — legacy, event-only, unchanged, still what every card on
  `/eventos` and `/portal` uses
- `data-ap-open="dj:123"` — generic, emitted by `apollo_surface_open_attrs()`

Endpoints come from `window.APOLLO_SURFACES`, published by PHP from the registry,
so **adding a surface is a PHP-only change** — the JS never learns a path. The
fragment cache is type-scoped (`event:5` ≠ `dj:5`). An unknown type is not
claimed at all, so a dormant surface degrades to a plain anchor rather than a
dead click. `window.ApolloLightbox` added as the type-neutral alias.

---

## Live status

`/portal`, verified after propagation:

```
APOLLO_SURFACES = { event: { rest: ".../apollo/v1/eventos/" } }   ← dj/loc correctly dormant
ApolloEventSingle · ApolloEventLightbox · ApolloLightbox          ← all present
[data-ev-lightbox] × 1                                            ← no double shell
lightbox: opens · mounts · enter ✔ reverse ✔ replay ✔
```

Harness: **31/31**.

---

## Class B — reported, not built

**apollo-djs and apollo-loc need a context extraction before their surfaces can
go live.** Both ship the parts (`templates/parts/dj/*`, `templates/parts/loc-*`)
but the ~120 lines that build the per-item variables are inline at the top of
`single-dj.php` / `single-local.php`, reachable only by loading that template as
a page. The parts are modular; the data is not.

Three steps each, mirroring apollo-events:

1. `apollo_{dj,loc}_single_context( int $id ): array` — cut the inline block out,
   have the template `extract()` it. Rendered output must not change; that is the
   whole test.
2. `apollo_{dj,loc}_render_single( int $id, array $args = [] ): string` — ob_start,
   include the parts in order, return the buffer.
3. Point `'renderer'` in `includes/surface.php` at it. Nothing else — endpoint,
   card contract and lightbox all light up together.

Not written this session because there is no PHP binary in the sandbox and this
folder deploys on save: an untested context extraction would go straight to
production on `/dj/{slug}`, a page that works today.

**Other Class B, unchanged:** apollo-hub, apollo-social, apollo-users and
apollo-adverts have no template-part split at all (monolithic templates). Same
remedy, larger job, no contract work needed first.

## Class C — decisions, not fixes

- **`.event-row` cards carry no href.** `eventRowHTML()` in the portal emits
  `<div role="button" data-ev-open>` with no anchor — no failure fallback, no
  middle-click, nothing for a crawler. `eveCardHTML` and `eveCardMiniHTML` are
  correct. The fix is `<a class="event-row" href={e.url}>` plus dropping
  `tabindex`/`role` (a link is natively focusable — also an a11y improvement),
  but `.event-row` is a DS component in `styles-ds.php` and the swap needs a
  visual pass. Recorded in harness **E2**, which now fails if anyone changes the
  situation without updating the note.
- **apollo-loc CPT slug is `local`, not `loc`.** Forbidden term per
  15-conventions, but renaming a live CPT is a migration, not an audit fix. The
  surface key is `loc`; the registration records the divergence.
