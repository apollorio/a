# Single Event — modular render system

The public single event page, an inline embed and the lightbox are the **same
markup from the same PHP**. There is one renderer, one stylesheet, one runtime.

Design source: `_dev web/screen/single cpt/event/single-event/event-single-page.html`

## Three surfaces, one code path

| Surface | Entry point | Notes |
|---|---|---|
| Page | `/evento/{slug}` → `styles/base/single-event.php` | Document shell only; body comes from the renderer |
| Inline | `[apollo_event_single id="123"]` or `apollo_event_render_single()` | Rendered server-side into any template |
| Lightbox | `data-ev-open="123"` on any element | Fetches `GET /apollo/v1/eventos/{id}/fragmento` |

```
apollo_event_single_context( $id )   →  one array with EVERY datum
apollo_event_render_part( $p, $ev )  →  one part, context extracted into scope
apollo_event_render_single( $id, … ) →  hero + <main> + footer + chat
```

## Rendering a subset

Any subset of parts can be rendered anywhere — that is what makes the page
replicable piecemeal:

```php
echo apollo_event_render_single( $id, array(
    'mode'  => 'inline',
    'parts' => array( 'hero', 'facts', 'access' ),
) );
```

Part order is fixed by `APOLLO_EVENT_SINGLE_PARTS`; `hero` renders before
`<main>`, `footer-visual` and `chat` after it.

## Adding the lightbox to a template

```php
<a href="<?php echo esc_url( get_permalink( $id ) ); ?>"
   data-ev-open="<?php echo (int) $id; ?>">Ver evento</a>

<?php apollo_event_lightbox_boot(); // once, near </body> ?>
```

`href` stays the real permalink, so ctrl/middle-click, "open in new tab" and
crawlers keep working. While the lightbox is open the URL is pushed with
`history.pushState`, so the address bar stays shareable.

## Conventions inside the parts

* **`data-ev="name"`** is the JS contract. The runtime never looks anything up
  by id — ids exist only for anchors and a11y, and are prefixed with the
  instance uid via `apollo_ev_id( 'name', $uid )` so two instances can coexist.
* **`data-ev-action="name"`** is the click contract (`back`, `share`,
  `open-chat`, `close-chat`, `send-msg`, `close-gallery`). No inline `onclick`.
* Everything is echoed through `esc_html` / `esc_attr` / `esc_url` / `wp_kses`.
* Every part starts with an `ABSPATH` guard and returns early when it has no
  data, so an event with no loc/lineup/gallery simply skips those sections.

## core.js contract

`core.js` owns the global `:root` token map. `apollo-single-event.css` defines
**only** `--ev-*` page tokens, each derived from a core token, so light/dark
inversion is automatic. Nothing here redefines `--ff-*`, `--rgb-*`, `--white-*`,
`--black-*`, `--accent`, `--r*`, `--s-*`, `--ease*`, `--shadow-*` or `--sb-*`.

GSAP and Lenis are booted by core.js. The runtime boots on `apollo:ready` —
**never** on `DOMContentLoaded`.

## Runtime API

```js
ApolloEventSingle.mountAll(scope)   // mount every [data-ev-root] found
ApolloEventSingle.mount(root)       // mount one, idempotent
ApolloEventSingle.unmount(root)     // full teardown, zero leaks
ApolloEventLightbox.open(id)        // open programmatically
ApolloEventLightbox.prefetch(id)    // warm the fragment cache on hover
```

Each instance tracks its own rAFs, timers, IntersectionObservers,
ResizeObservers, ScrollTriggers, Leaflet map and audio players, and binds every
listener through a single `AbortController` — so `unmount()` is complete.

## Data (meta keys)

Read via the helpers in `includes/functions.php`, never directly in a part:

| Concern | Source |
|---|---|
| Dates / times | `_event_start_date`, `_event_end_date`, `_event_start_time`, `_event_end_time` |
| Media | `_event_banner`, `_event_gallery`, `_event_video_url`, `_event_audio_url`, `_event_bg_color` |
| Loc | `_event_loc_id` → `apollo_event_get_loc()` → `_local_address`, `_local_city`, `_local_lat`, `_local_lng`, `_loc_gallery` |
| Line-up | `_event_dj_ids`, `_event_dj_slots` → `apollo_event_get_djs()` |
| Access | `_event_access_buttons` (new repeater) with legacy fallback → `apollo_event_build_access_payload()` |
| Visibility | `_event_privacy`, post status → `apollo_event_single_can_view()` |

Venue slider photos: loc gallery first, event banner + `_event_gallery` as
fallback.
