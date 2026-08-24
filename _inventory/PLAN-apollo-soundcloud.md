# PLAN — apollo-soundcloud

> One SoundCloud player for the whole ecosystem. Written 2026-08-17 after
> mapping every existing implementation and verifying the API access situation.

---

## 0 · The blocking fact, first

**SoundCloud closed open API registration.** Access is application-only through
a form on `developers.soundcloud.com`, reviewed manually, selective, and it can
take weeks. There is no way to guarantee credentials.

**This is not a problem — it changes which path is primary.** Two SoundCloud
surfaces need *no credentials at all*:

| Surface | Credentials | Gives you |
|---|---|---|
| **Widget API** (`w.soundcloud.com/player/api.js`) | **none** | `play` `pause` `seekTo` `getDuration` `getPosition` + `READY/PLAY/PAUSE/FINISH/PLAY_PROGRESS` events |
| **oEmbed** (`soundcloud.com/oembed?url=…`) | **none** | title, author, artwork, embed HTML |
| HTTP API (`api.soundcloud.com`) | application-only | direct stream URLs, search, full metadata |

> **Therefore: the "embed connected to our player via scripts" is the PRIMARY
> architecture, not the fallback.** It is the only path that works today, for
> everyone, without waiting on an approval that may never come.

The HTTP API becomes an **optional upgrade**: if credentials ever arrive, a
single provider swaps in behind the same interface and nothing else changes.
That is the whole reason for the adapter layer in §3.

**On the brainstorm's repo table** — Navidrome, Koel, Ampache and the rest are
excellent self-hosted *music servers*. They host **your own audio**; none of
them plays a SoundCloud track. They are the right answer to a different
question ("host our own catalogue"), and if that becomes the goal, Navidrome +
the same adapter interface is a clean second provider. They do not substitute
for SoundCloud playback.

---

## 1 · What exists today — 7 implementations, 0 shared

| # | Where | What it does |
|---|---|---|
| 1 | `apollo-djs/assets/js/dj-single.js:41-49` | full `SC.Widget` + vinyl state binding |
| 2 | `apollo-djs/assets/js/dj-card-single.js:138` | sets `iframe.src` directly, **no widget** |
| 3 | `apollo-djs/styles/…/single/scripts.php:154` | sets `iframe.src` directly, **no widget** |
| 4 | `apollo-users/templates/single-profile.php:3815-3859` | full `SC.Widget`, **inline in a template** |
| 5 | `apollo-users/assets/js/profile.js:202-236` | **another** `SC.Widget`, same plugin |
| 6 | `apollo-hub/assets/js/home/radio.js:29-74` | lazy-loads `api.js`, own `SC.Widget` |
| 7 | `apollo-events/styles/apollo-v2/single-dj.php:278` | hardcoded iframe, no control |

Plus:

- `api.js` loaded from **three** places (`single-dj.php:162`,
  `single-profile.php:103`, lazily in `radio.js`) — a page carrying two of them
  loads the SDK twice.
- **Two hardcoded track IDs in production**:
  `apollo-dashboard/…/panel-feed.php:211` → track `1293057640`, and
  `apollo-hub/…/radio.php:28` → track `293`. Neither comes from data.
- A widget-URL builder duplicated in `apollo-users/includes/functions.php:362`,
  `apollo-djs/templates/single-dj.php:76`, and two JS files.

Same shape as the four accommodation cards and the five Out Now sections:
**no single owner.** Seven players means seven different behaviours for pause,
seven different failure modes when the SDK is slow, and no possibility of "only
one thing plays at a time" — which a social network needs.

---

## 2 · What the plugin must do

- **Own player UI.** Apollo chrome, not SoundCloud's. The iframe is a hidden
  transport.
- **Two modes**, per the brief:
  - `preview` — **25% → 65%** of the track. Requires `getDuration()`, so it
    requires the Widget API; a bare iframe cannot do it.
  - `full` — 0% → 100%.
- **Plug and play.** One function call from any template, any plugin.
- **Degrade honestly.** If the SDK is blocked, fall back to the visible native
  embed rather than a dead button.
- **One active player.** Starting one stops the others, ecosystem-wide.

---

## 3 · Architecture

```
   any template, any plugin
            │
            ▼
   apollo_soundcloud_player( $url, [ 'mode' => 'preview' ] )
            │
            ├── resolve()      URL → {kind: track|playlist|user, embed_url}
            ├── meta()         oEmbed, cached 12h in a transient
            └── markup         Apollo player UI + hidden iframe
                                     │
                                     ▼
                          apollo-sc.js — ONE runtime
                                     │
                     ┌───────────────┴───────────────┐
                     ▼                               ▼
              SC.Widget (default)            native embed (fallback)
              play/pause/seek/duration       visible, SoundCloud chrome
```

**The provider seam.** `apollo_sc_provider()` returns `widget` today. If HTTP
API credentials ever arrive it returns `api` and gains direct stream URLs — the
player UI, the modes and every call site stay identical. That seam is why the
closed registration is a non-issue rather than a blocker.

### Preview window — how 25→65% actually works

A bare iframe cannot do this; the Widget API can:

1. `READY` → `getDuration()` (ms)
2. `start = duration * 0.25`, `end = duration * 0.65`
3. `seekTo(start)` → `play()`
4. `PLAY_PROGRESS` → when `position >= end`, `pause()` and reset to `start`

Percentages are **configurable per call** and default to 25/65. A track shorter
than a floor (default 20 s) plays whole — a 4-second ID clip has no meaningful
middle 40%.

---

## 4 · Public API

```php
// Render a player anywhere.
echo apollo_soundcloud_player( $url, array(
    'mode'      => 'preview',   // preview | full
    'variant'   => 'card',      // card | inline | vinyl | bar
    'start_pct' => 25,
    'end_pct'   => 65,
    'autoplay'  => false,
) );

apollo_soundcloud_resolve( $url );   // → kind, id, embed_url, canonical
apollo_soundcloud_meta( $url );      // → title, author, artwork  (oEmbed, cached)
apollo_soundcloud_is( $url );        // → bool
```

```js
ApolloSC.play( el )      // start/resume a player element
ApolloSC.pause( el )
ApolloSC.stopAll()
ApolloSC.mount( root )   // for markup injected after load (lightbox fragments)
```

**One event bus.** `apollo:sc:play` / `apollo:sc:pause` on `document`, so the
existing vinyl animation on `/dj/{id}` and the rail card's `is-playing` state
both listen instead of each binding their own widget.

---

## 5 · Phases

### S1 — plugin skeleton *(no consumers)*
- [ ] `apollo-soundcloud.php` — header, constants, dependency check on apollo-core
- [ ] `includes/resolve.php` — URL parsing, `is()`, embed-URL building. **One
      builder**, replacing four
- [ ] `includes/meta.php` — oEmbed + 12 h transient, fail-closed to `[]`
- [ ] `includes/render.php` — `apollo_soundcloud_player()`, markup + variants
- [ ] `assets/js/apollo-sc.js` — the single runtime
- [ ] `assets/css/apollo-sc.css` — player chrome, delivered via the card-style
      ledger pattern

### S2 — make it the only player
- [ ] Replace #1 `dj-single.js` — keep the vinyl animation, drive it from the
      `apollo:sc:play` event
- [ ] Replace #2 and #3 — the two that set `iframe.src` with no widget
- [ ] Replace #4 — **delete the inline `<script>` from `single-profile.php`**
- [ ] Replace #5 — `profile.js` uses the runtime
- [ ] Replace #6 — `radio.js` keeps its playlist logic, loses its widget code
- [ ] Replace #7 — the hardcoded apollo-v2 iframe
- [ ] Remove the two hardcoded track IDs; they must come from data
- [ ] `api.js` loaded **once**, by this plugin, lazily on first play

### S3 — wire to tracks
- [ ] `apollo_track_preview()` gains a `soundcloud` provider that returns
      `mode: 'widget'`, joining catbox/archive/youtube
- [ ] The `/casa` rail card plays SoundCloud previews through the same runtime
- [ ] `_track_url_soundcloud` becomes a valid preview source with no extra field

### S4 — optional API upgrade *(only if credentials arrive)*
- [ ] `apollo_sc_provider()` returns `api`
- [ ] Direct stream URLs → native `<audio>`, no iframe, real scrubbing
- [ ] Search and richer metadata
- [ ] **Zero call-site changes**

---

## 6 · Risk

| # | Step | Risk |
|---|---|---|
| 1 | S1 skeleton | **low** — new plugin, nothing consumes it |
| 2 | S3 tracks wiring | low — additive provider |
| 3 | S2 replacements | **medium — 7 live surfaces.** One at a time, each verified before the next |
| 4 | S4 | low, and may never happen |

**S2 is the careful one.** `/dj/{id}`, `/perfil`, `/hub` and the dashboard all
play audio today; a bad swap is silent (nothing plays) rather than loud. Do them
individually, and keep each old implementation until its replacement is
confirmed working on that page.

---

## 7 · Decisions

1. **Preview window** — 25→65% as briefed. Fixed site-wide, or per-call
   override? *(Plan: per-call, defaulting to 25/65.)*
2. **Short-track floor** — below what length does preview play the whole track?
   *(Plan: 20 s.)*
3. **Autoplay next** — when a preview ends in the `/casa` rail, advance to the
   next card or stop? *(Plan: stop.)*
4. **Apply for API access?** Free, slow, uncertain. Costs nothing to submit and
   the adapter is built for it either way.

---

**Sources for the API-access finding:**

- [SoundCloud Developers](https://developers.soundcloud.com/)
- [soundcloud/api issue #219 — "When will registration open again?"](https://github.com/soundcloud/api/issues/219)
- [SoundCloud API — How to Actually Get Access in 2026](https://publicapis.io/soundcloud-api)
