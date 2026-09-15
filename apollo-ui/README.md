# apollo-ui

Card style system (`type-1` … `type-6`) and the standard full-viewport lightbox
binding for Apollo CPTs. Choose the look per post type in **Appearance → Apollo
UI**; drop cards anywhere with one global shortcode.

> `.md` files under `wp-content/plugins/` are denied by `plugins/.htaccess`
> rule 1, so this document is not served to the web.

---

## What already existed — and what this plugin deliberately does not rebuild

Two things were found working before a line was written. Both are reused, not
replaced.

### 1. The card contract (`apollo-core`)

`apollo-core/includes/card-contract.php`, loaded at `apollo-core.php:179`, is a
real registry: `apollo_card_register()`, `apollo_card_render()`,
`apollo_card_get()`, `apollo_card_styles_once()`. Its docblock records the
2026-08-17 audit that motivated it — the accommodation card existing **four
times**, "Out Now" **five times**, `.accom-*` CSS not loaded on `/anuncios`.

**It had one consumer.** `apollo-djs/includes/card-track.php` describes itself as
*"THE FIRST CONSUMER of `apollo_card_register()`. The contract shipped with
zero."* Measured at token level: **1** `apollo_card_register()` call and **2**
real `apollo_card_render()` calls, against **34 card-named files in 12 plugins**.

apollo-ui does **not** add a second registry. It registers six named *styles*
into the existing contract.

### 2. The event lightbox (`apollo-events`)

`apollo-events/includes/render-single.php` is the SSOT for page, inline and
lightbox output:

| piece | where |
|---|---|
| shell | `apollo_event_lightbox_shell()` → `<div class="ev-lb" data-ev-lightbox>` |
| trigger | any element with `data-ev-open="{id}"` |
| content | `GET /wp-json/apollo/v1/eventos/{id}/fragmento` |
| close | `.ev-lb-x[data-ev-lb-close]` |
| enqueue | `apollo_event_lightbox_enqueue()` |

**Verified live on `/eventos` before building:** shell present, **16
`data-ev-open` triggers**, fragment endpoint returns **HTTP 200 with 8.6 KB** of
real markup for event 72. The behaviour you asked for — click a card, the page
opens full-viewport with a close button top-right — **already works for
`event`.**

`apollo-core` also already publishes a generic surface table:

```js
window.APOLLO_SURFACES = {"event":{"rest":"…/apollo/v1/eventos/"},
                          "dj"   :{"rest":"…/apollo/v1/djs/"}}
```

So apollo-ui's lightbox job is **generalisation, not implementation**. For any
surface with a `delegate`, it hands the click to that runtime. Two overlays for
one card is exactly the duplication the card contract exists to end.

---

## The card map — what exists today

**34 card-named files across 12 plugins.** Only one goes through the contract.

| plugin | files | notable |
|---|---|---|
| `apollo-adverts` | 7 | `rt-card.php`, `rt-card-market.php`, `card-accommodation.php`, `card-ticket.php` — four card shapes, own CSS + JS |
| `apollo-djs` | 6 | `dj-card.php` ×3 paths, `card-track.php` ← **the only contract consumer** |
| `apollo-events` | 5 | `event-card.php` ×2 paths, `event-card-manage.php`, `styles-cards.php` |
| `apollo-hub` | 3 | `card-event.php`, `card-track.php`, `card-crash.php` — re-implements event and track |
| `apollo-templates` | 3 | **`event/card-style-01.php`** ← the numbering idea already started here |
| `apollo-core` | 2 | the contract itself, plus a `.BROKEN-20260824` copy (403, inert) |
| `apollo-loc` | 2 | `loc-card.php`, `local-card.php` — two names, one concept |
| `apollo-users` | 2 | `user-card.php`, `profile-card.php.bak` (403, inert) |
| `apollo-admin` `apollo-elementor` `apollo-groups` `apollo-social` | 1 each | — |

### Duplication visible in that map

- **event card** re-implemented in `apollo-events` (×2), `apollo-templates`, `apollo-hub`
- **track card** in `apollo-djs`, `apollo-hub`, `apollo-templates`
- **dj card** three times inside `apollo-djs` alone
- **rt-card** four times inside `apollo-adverts`

`apollo-templates/templates/event/card-style-01.php` shows someone already
reached for numbered styles. apollo-ui finishes that idea instead of inventing a
different one.

---

## The six styles

A style is a **look**, not a content type. `type-1` means "poster" forever — a
surface that hard-codes `type-1` in a shortcode must not silently change shape
on upgrade. **Add `type-7`; never renumber.**

| key | name | media | ratio | layout | use |
|---|---|---|---|---|---|
| `type-1` | Poster | poster | `2 / 3` | stack, title overlaid | events, the flagship look |
| `type-2` | Wide | landscape | `16 / 9` | stack | listings, classifieds, locals |
| `type-3` | Row | square | `1 / 1` | row, thumb left | dense lists, rails, tracks |
| `type-4` | Avatar | circle | `1 / 1` | stack, centred | people — DJs, hosts, profiles |
| `type-5` | Tile | square | `1 / 1` | stack, overlaid | mosaics, grids |
| `type-6` | Text | none | `auto` | bare | documents, text-first |

All six share **one markup shape**. The difference is a class and a custom
property:

```html
<article class="aui-card aui-type-1 aui-media-poster aui-layout-stack"
         style="--aui-ratio: 2 / 3;" data-aui-type="type-1" data-aui-id="72">
  <a class="aui-link" href="/evento/alt-s002/"
     data-apollo-open="72" data-apollo-cpt="event">
    <figure class="aui-media"><img class="aui-img" …></figure>
    <div class="aui-body">
      <h3 class="aui-title">…</h3>
      <p class="aui-meta">…</p>
    </div>
  </a>
</article>
```

The `href` is a **real link**. The lightbox is progressive enhancement layered
on top: if the script never loads, or a fetch fails, the click falls through to
normal navigation. A lightbox that swallows the click and then fails leaves the
user with nothing.

---

## Usage

### Shortcodes

```
[apollo_card id="72" type="type-1"]
[apollo_cards cpt="event" count="8" type="type-2" columns="4"]
```

Omit `type` and the style configured for that post type is used.

| attribute | default | notes |
|---|---|---|
| `id` | current post | `[apollo_card]` only |
| `cpt` | `event` | `[apollo_cards]` only; must be a registered post type |
| `count` | `8` | clamped 1–48 |
| `columns` | `4` | clamped 1–8 |
| `type` | per-CPT setting | `type-1` … `type-6` |
| `variant` | `default` / `grid` | passed through to the contract |
| `lightbox` | per-CPT setting | `true` / `false` to force |

Both tags are registered behind `shortcode_exists()`. `add_shortcode()` silently
overwrites, and this ecosystem has already lost four shortcodes that way —
apollo-ui yields to any existing owner.

### PHP

```php
echo apollo_ui_card( 'type-2', $post_id );                 // direct
echo apollo_card_render( 'type-2', $post_id );             // via the contract
```

### Adding a lightbox surface without touching this plugin

```php
add_filter( 'apollo_ui_surfaces', function ( array $s ): array {
    $s['local'] = array(
        'rest'     => rest_url( 'apollo/v1/locais/' ),
        'fragment' => 'fragmento',
        'delegate' => '',            // '' = apollo-ui opens its own overlay
    );
    return $s;
} );
```

Then tick the post type under **Appearance → Apollo UI → Abre em lightbox**.

---

## Assets: why `wp_enqueue_scripts` alone is not enough

**Apollo's blank-canvas templates never call `wp_head()`.** Verified against the
live site: `/eventos` returns no `wp-emoji`, no `wp-includes/js`, no
`dns-prefetch`, no `generator` — none of `wp_head()`'s output — and
`apollo-core/includes/document-head.php` contains no `wp_head()` call at all. It
prints a curated head instead.

apollo-ui v1.0.0 shipped with only `wp_enqueue_style()` and consequently emitted
**zero bytes** on `/casa`, `/eventos`, `/anuncios`, `/hub` — every screen that
matters. The fix is to emit on the hooks `document-head.php` actually publishes:

| hook | where | file:line |
|---|---|---|
| `apollo/canvas/head` | inside `<head>`, every blank canvas | `document-head.php:185` |
| `apollo/canvas/before_close` | before `</body>`, every blank canvas | `document-head.php:287` |
| `apollo/plus/before_close` | the Apollo+ shell | `apollo-plus-api.php:165` |

The house pattern is `apollo-templates/includes/mobile-runtime.php:48` — a
`static $done` ledger plus the same callback on several hooks. That is why
`mobile-premium.css` is one of only two stylesheets that load on `/eventos`.
`includes/enqueue.php` follows it, and additionally checks
`wp_style_is( …, 'done' )` so a classic page that really did run `wp_head()`
never receives a second `<link>`.

Inline script uses `apollo_csp_nonce_attr()` when present. The site issues real
per-request nonces — an un-nonced inline script would be dropped by the browser
with no PHP error to show for it.

## Boot and safety

```
init:20   apollo_ui_boot()                registers the six styles
init:25   apollo_ui_register_shortcodes() guarded by shortcode_exists()
apollo/canvas/head:20          apollo_ui_emit_head()
apollo/canvas/before_close:20  apollo_ui_emit_footer()
apollo/plus/before_close:20    apollo_ui_emit_footer()
wp_footer:5                    apollo_ui_maybe_print_event_shell()
```

`init:20` is deliberate — after `apollo-core`'s contract and after
`apollo-djs` registers `track` at `init:20`, so first-registration-wins can
never take a live card away from its owner.

Every cross-plugin call is `function_exists()`-guarded. With `apollo-core` or
`apollo-events` inactive, apollo-ui degrades to nothing rather than fataling —
the failure mode currently live in `apollo-events/styles/base/create-event.php`
and `dashboard-event.php`, which call `apollo_plus_close()` with no guard.

### Full-viewport detail that matters

The overlay panel uses `100dvh`, not `100vh`. On mobile Safari `100vh` is the
*uncollapsed* viewport, so a "full viewport" panel is taller than the screen and
a top-right close button sits under the URL bar — unreachable. That is the one
control the user must always be able to hit.

---

## Verification

Runtime harness (stubs WordPress and executes the render path — `php -l` proves
it parses, not that it runs):

```bash
php <scratchpad>/aui-harness.php     # 48 checks
```

Covers: settings merge, all six styles registering, six distinct named
renderers, every style rendering, lightbox attributes, governed-meta output, no
raw PHP leakage, missing-post safety, surface publication and delegation, the
per-CPT type resolver, both shortcodes, and contract registration.

**Governed meta only.** `apollo_ui_card_meta()` reads keys declared in
`apollo-core/config/meta.php`. The 224 ungoverned keys have no type and no
owner; a card is the wrong place to start trusting them.

---

## Status

The plugin is **inert until activated**. WordPress loads only what is in
`active_plugins`, so its presence on disk changes nothing. Activate at
**Plugins → Apollo UI**, then configure at **Appearance → Apollo UI**.
