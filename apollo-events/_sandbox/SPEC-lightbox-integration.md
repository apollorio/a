# Event Lightbox — integration contract

> How any plugin, widget or template opens the full single-event page in place.
> Status: **P1–P3 shipped 2026-08-07.** P4 (mobile pass) open — see the bottom.

---

## The whole API

```php
add_action( 'wp_enqueue_scripts', function () {
    if ( function_exists( 'apollo_event_lightbox_enqueue' ) ) {
        apollo_event_lightbox_enqueue();
    }
} );
```

Then anywhere in the document:

```php
<a href="<?php echo esc_url( get_permalink( $id ) ); ?>"
   data-ev-open="<?php echo (int) $id; ?>">…</a>
```

That is the entire contract. Nothing else is required — no shell markup, no
script tags, no CSS, no path to `apollo-events`. The shell is printed for you on
`wp_footer`.

Programmatic open, once the runtime has loaded:

```js
ApolloEventLightbox.open( id );   // returns a promise, resolves after mount
ApolloEventLightbox.close();
```

---

## The card contract, and why `href` is not decorative

| Attribute | Required | Why |
| --- | --- | --- |
| `data-ev-open="{id}"` | yes | what the delegated listener binds to |
| `href="{real permalink}"` | **yes** | fallback + browser affordances |

`href` carries three behaviours the lightbox deliberately does not reimplement:

1. **Failure fallback.** If the fragment request fails — offline, 404, the event
   became private — the runtime stops intercepting and lets the browser follow
   the link to the real page. A card with no `href` fails to a dead click.
2. **Middle-click / ⌘-click / ctrl-click / "Open in new tab".** The listener
   checks the modifier keys and yields. That only works if there is a URL.
3. **Crawlers and screen readers**, which do not run the click handler.

A card with `data-ev-open` and no `href` is a broken card. Harness assertion
**E2** enforces this for the three portal builders.

---

## What loads

`apollo_event_lightbox_enqueue()` is idempotent — ten widgets on one page cost
one enqueue. It registers:

| Handle | Deps | Notes |
| --- | --- | --- |
| `leaflet` (css + js) | — | `cdn.jsdelivr.net`, **not** `unpkg.com` (CSP) |
| `apollo-single-event` (css) | `leaflet` | base sheet |
| `apollo-single-event` (js) | `leaflet` | `ApolloEventSingle.mount/unmount` |
| `apollo-event-lightbox` (js) | `apollo-single-event` | `ApolloEventLightbox.open/close` |
| inline style on `apollo-single-event` | — | full-viewport overrides |

Plus two localized objects: `APOLLO_EVENT_LB` (REST root + nonce) and
`APOLLO_EVENT_REVEAL` (the reveal selector contract, below).

The inline style matters more than it looks. The base sheet sizes `.ev-lb` as a
`min(100vw, 560px)` centred card. The full-screen page behaviour lives in
`portal/styles-lightbox.php` — despite the filename it is not portal-specific,
every selector in it is `.ev-lb.ev-lb …`. Without it an integrating plugin gets
a phone-width column on a 1440px screen.

---

## The three boot paths, and the one ledger

| Path | Used by | Prints assets |
| --- | --- | --- |
| `apollo_event_lightbox_enqueue()` | any plugin (**preferred**) | via `wp_enqueue_*` |
| `apollo_event_lightbox_boot()` | blank-canvas templates that never run `wp_head`/`wp_footer` — `/eventos`, `/portal` | inline, near `</body>` |
| `wp_footer` auto-boot, prio 20 | armed by `…_enqueue()` | shell only |

All three consult **one** ledger, `apollo_event_lightbox_state()`, with four
flags: `shell` · `assets` · `css` · `armed`.

This is not ceremony. Each path used to own a private `static $printed`, so they
could not see one another. `/eventos` calls `apollo_event_lightbox_boot()`
directly; an unguarded footer hook would print a **second** `[data-ev-lightbox]`,
and the runtime resolves its shell with a single
`document.querySelector('[data-ev-lightbox]')` — it would bind the empty one and
every card would appear to do nothing. The footer hook stands down unless
`armed && ! shell`. Harness assertion **E12**.

The CSS cell claims the ledger *itself* rather than letting the caller do it,
because the portal cascade `require`s it directly and never passes through
`apollo_event_lightbox_styles()`.

---

## Reveal selector contract

One owner: `APOLLO_EVENT_REVEAL_ANIM` / `APOLLO_EVENT_REVEAL_FLOOR` in
`includes/render-single.php`. Published to the browser as
`window.APOLLO_EVENT_REVEAL = { anim, floor }`.

| Consumer | Reads |
| --- | --- |
| `apollo-single-event.js` `initAnim()` | `.anim` — what GSAP drives |
| `portal/bootstrap.php` `unstrand()` | `.floor` — the anti-strand net |
| `portal/styles-lightbox.php` | generated in PHP from `…_FLOOR` |

`FLOOR` = `.ev-reveal` + `ANIM` + `.ev-dj` + `[data-reveal-word]` — the last two
have their own dedicated scenes but still must never strand invisible.

Each consumer keeps a literal as an **offline fallback only** (harness, a
fragment loaded without the boot script). Adding a second literal to any of them
fails harness **E13**. Before 2026-08-07 all three were hand-copied, each with a
"keep in sync manually" comment — a defect with a note attached.

---

## Animation: enter *and* reverse

Scroll scenes use `toggleActions: 'play none none reverse'`. They no longer use
`once: true`.

**Two things make that safe, and removing either reintroduces a shipped bug:**

1. **`scroller` is resolved per instance** —
   `root.closest('.ev-lb-scroll') || undefined`. The document is Lenis-locked
   while the panel is open, so the window never moves; a trigger created against
   the default `scroller: window` inside the panel never resolves. With
   `once: true` that meant "no animation". Without `once`, it means the element
   sits at GSAP's inline `opacity: 0` **forever** — content present in the DOM,
   unreadable on screen. Registry: `$lightbox_reveal_scroller_fix_2026_08_01`.
2. **`unstrand()` is bounded to the panel viewport.** Its comment always claimed
   "inside the viewport-height of the panel"; the code never checked. It
   force-visibled every match under `opacity < .05`, and a below-the-fold reveal
   is legitimately at zero because its trigger has not fired. So 600 ms after
   open, everything below the fold was nailed to `opacity: 1 !important` — the
   scroll animation was not broken, it was deleted. That is the "no GSAP feel
   inside the lightbox" report. Rescue is now restricted to what is actually on
   screen, where "invisible" can only mean "stranded".

Harness assertion **E14** guards both legs.

---

## Verification

```
node apollo-events/_sandbox/build-portal-harness.mjs
```

26 assertions. Lightbox-specific: **E1–E14**. Extend it when you add a cell —
a green run is the gate, not a formality.

There is no PHP binary and no headless browser in the sandbox, so PHP is checked
structurally and the visual pass is manual, on the live site, **30 s after save**
(RealTimeSync propagation).

---

## BLOCKER — the panel renders blank on /portal (open, 2026-08-07)

**Do not treat the lightbox as working until this is closed.** It opens, the
fragment mounts, the runtime is live, GSAP is mid-tween — and the user sees a
white rectangle with a working ✕.

Measured on a clean load, no test contamination:

| Element | class | computed opacity |
| --- | --- | --- |
| `.ev-lb` | `ev-lb is-open` | **0** |
| `.ev-lb-panel` | — | **0** |
| `.ev-lb-body` | `ev-lb-body` | **0** |

Every one of those should be 1, and the rules that would set them are present
and loaded:

- `apollo-single-event.css` **is** in `document.styleSheets` and **does**
  contain `.ev-lb.is-open{opacity:1;pointer-events:auto;}` (verified by fetch)
- `<style id="apollo-pev-lightbox">` **is** in the document and **does** contain
  `.ev-lb.ev-lb.is-open .ev-lb-panel { … opacity: 1 }` (specificity 0,4,0)
- an inline `style.setProperty('opacity','1','important')` on `.ev-lb-body` was
  **still** computed as 0 in one run

Three levels reading 0 against correct, loaded, higher-specificity rules means
something is winning that is not in this plugin — the next step is
`CSS.getMatchedStylesForNode` / DevTools "Computed → opacity → matched rules" on
`.ev-lb`, which is the one thing this session could not do (walking
`document.styleSheets` from the page froze the renderer). Prime suspect is a
global `!important` from the CDN bundle (`core.js` / `reveal-up.js`), which is
also the only stylesheet not owned by this repo.

Two contributing defects **were** found and fixed on the way, both real:

1. `.ev-lb-body`'s `.is-entered` class (the 2026-08-06 "stairs" fix) could be
   stripped by a re-entered `inject()` and never restored. Now has a 500 ms
   floor. Guarded by **E16**.
2. The inline boot path stamped assets `?v=APOLLO_EVENT_VERSION`, a hand-bumped
   constant frozen at 1.7.0. This folder deploys on save, so the file changed
   and the URL did not — **Cloudflare's edge cache served stale JS/CSS across
   every deploy.** Confirmed live: a hard reload still returned the old
   `apollo-event-lightbox.js`, while the same file with a unique query string
   returned the new one. Now uses `apollo_event_asset_ver()` (filemtime), which
   is what the `wp_enqueue` path always used.

Because of (2), **anything verified before ~2026-08-07 20:55 UTC was verified
against stale assets.** Re-test everything once opcache turns over and the URLs
carry the mtime suffix.

## Open — P4, mobile pass

Not shipped. The panel is `100vw` and scrolls, so it *works* on a phone, but:

- `100dvh`, not `100vh`, throughout (iOS URL-bar collapse)
- `env(safe-area-inset-*)` on the ✕ and the scroller's bottom padding
- sheet-style drag-to-dismiss on `.ev-lb-panel`
- verify `.ev-lb-scroll` against the iOS soft keyboard (the chat part focuses an
  input inside the panel)

These touch shell geometry, which the operating brief flags as confirm-first.
Ask before applying.
