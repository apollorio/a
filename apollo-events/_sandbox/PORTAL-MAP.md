# `/eventos` — render map & modularisation state

Generated 2026-08-05. Grounded in `_inventory/registry/09-plugins/apollo-events.json`
(chapters `pages`, `rewrite_rules`, `$phase_002_eventos_apollo_plus_2026_07_28`,
`$lightbox_reveal_scroller_fix_2026_08_01`) — no invented slugs or paths.

---

## 1. Route → template

`/eventos` is **not** a WP Page and **not** the CPT archive. It is a virtual route.

```
GET /eventos
  └─ apollo-events/src/Plugin.php
       route_map()      '^eventos/?$'  → 'portal_archive'
       path_map()       'eventos'      → 'portal_archive'   (pre-flush-safe claim)
       parse_request_fallback()        → same, before rewrites exist
       handle_virtual_pages()  (template_redirect P?)
         └─ TemplateLoader::locate('archive-event.php')   ← THEME-FIRST, see §5
              └─ styles/base/archive-event.php            ← the mount point
```

Aliases, identical template: **`/portal`**, **`/portal/eventos`**.
Sibling routes that do **not** come here: `/eventos/meus` (KPI dashboard,
`dashboard-meus.php`), `/eventos/novo` + `/novo-evento` + `/criar-evento` +
`/add-evento` (create form), `/meus-eventos` + `/painel` + `/painel/eventos` +
`/dashboard` (legacy manage list).

## 2. Mount point — `styles/base/archive-event.php`

Owns **data only**, then hands off. In order:

1. `apollo_event_ensure_helpers()`
2. `WP_Query` — `post_type=event`, `post_status=publish`, `posts_per_page=200`,
   ordered by `_event_start_date`. Client-side filtering after that.
3. Builds `$portal_events[]` per event, shaped to the mockup's `APOLLO_EVENTS`
   contract: `id,title,url,cover,startDate,startTime,venue{name,address},
   lineup[{name}],genres[],tags[],season,tickets,ticketsUrl,highlight,status,about`
4. Emits a provenance HTML comment (which template/style pack actually rendered)
5. `apollo_plus_open(screen:'eventos')` → shell owns `<head>`/topbar/aside/`<main>`
6. Prints `#apollo-portal-root` (empty — all DOM is client-built)
7. `require portal/scripts.php`
8. `apollo_plus_close()`

Legacy self-hosted-document fallback only if `apollo-templates` is inactive.

## 3. Cells (already modular — 19 files)

**Styles** — `portal/styles.php` requires in cascade order, then emits the
shell-fit block **last** (single owner of `.pev-host` / `.pev-chrome`):

```
base → ds → chrome → browse → hero → rails → browse-mini → modals
     → responsive → masthead → lightbox        [+ shell-fit inline, last word]
```

**Runtime** — `portal/scripts.php` requires a hard dependency chain:

```
data.php       window.APOLLO_EVENTS   (real WP rows; XSS-hardened, §6)
helpers.php    APOLLO_PORTAL          (pure derivation, holds no data)
app.php        AppPortalEventos       (skeleton + render + wiring)
bootstrap.php  mount + scroll-engine sync + modal scroll lock
```

## 4. Lightbox — single-event page rendered inside `/eventos`

No iframe. A card click is yielded to `ApolloEventLightbox`, which fetches the
**same PHP renderer** that builds `/evento/{slug}`:

```
click [data-ev-open="{id}"]            (all 3 card builders emit it + real href)
  └─ assets/js/apollo-event-lightbox.js
       GET {rest}/apollo/v1/eventos/{id}/fragmento   ← same-origin, §6
         └─ inject payload.html into .ev-lb-scroll
              └─ ApolloEventSingle.mount(root)   (apollo-single-event.js)
                   ScrollTriggers bound to scroller = .ev-lb-scroll
```

Anchors keep the real permalink, so middle-click / ctrl-click / "open in new
tab" fall through untouched; `history.pushState` keeps the URL shareable.

## 5. Known divergence (registry-recorded, infra not code)

`TemplateLoader::locate()` resolves **theme-first**:

```
themes/{child}/apollo-events/{style}/archive-event.php   ← wins
themes/{parent}/apollo-events/{style}/archive-event.php
plugins/apollo-events/styles/{style}/archive-event.php
plugins/apollo-events/styles/base/archive-event.php      ← this file
```

If a theme ships an override, or the active-style option points at a pack other
than `base`, **edits here have no effect and fail silently**. That is why the
mount point prints a provenance comment — view source to answer "which file
actually rendered" instead of guessing.

## 6. Transport hardening (2026-08-05)

| Fix | File | Why |
| --- | --- | --- |
| Removed localhost census beacon | `portal/bootstrap.php` | `POST http://127.0.0.1:7704/…` fired on **every** `/eventos` load for every visitor. From HTTPS it is blocked twice — mixed content, then CORS preflight on the custom `X-Debug-Session-Id` header. `.catch()` hid it, so it survived while polluting the console of the one screen we keep debugging. |
| Same beacon → `console.debug` | `assets/js/apollo-events-create-wire.js` | Fired on every keystroke in the create-form comboboxes. Probe data kept, cross-origin request dropped. |
| `sameOrigin()` URL guard | `assets/js/apollo-event-lightbox.js` | `rest`/`wpApiSettings.root` are absolute URLs baked server-side. If that origin ≠ the visitor's origin (www vs apex, http before SSL rewrite, cached-under-another-host, CDN/preview host) the fragment fetch becomes cross-origin: the `X-WP-Nonce` header forces a preflight WP does not answer, **and** `credentials:'same-origin'` drops the auth cookie. Now rewritten to a same-origin path; no-op when origins already match. |
| 12s timeout + `TimeoutError` | same | There was none — a stalled socket left the loader spinning forever with no path back to the real page. Tagged so it is distinguishable from a user-initiated abort, which must stay silent. |
| Read as text, then `JSON.parse` | same | `r.json()` on an HTML body throws "Unexpected token &lt;", which names nothing. A WAF, loginizer, a maintenance page or a login wall all answer HTML with a 200. Now reports what actually came back. |
| One retry, transport-only | same | Retries `TimeoutError` / `TypeError` (network) once. HTTP status and malformed payloads are deterministic — retrying only delays the permalink fallback. |
| `cache:'no-store'` | same | Stops a stale proxy/bfcache copy of the fragment replaying after the event was edited. |
| `JSON_HEX_TAG\|AMP\|APOS\|QUOT` | `portal/data.php` | `wp_json_encode` does not escape `<`/`&`, so an event title containing `</script>` was stored XSS on the public page. Shared helper: `apollo_json_for_script()` in apollo-core. |

## 7. Verification gate

```
node apollo-events/_sandbox/build-portal-harness.mjs
```

Stitches the **real** cells (reads, never copies) into `_sandbox/portal-harness.html`
and asserts 22 invariants: JS parses, `skeleton()`/`heroFallbackHTML()` tag-balanced,
every `#id` the JS queries exists in the markup it builds, the hero fallback iframe is
last + inert + same-origin + slug-allowlisted, the full lightbox contract (E1–E10),
and **no selector declared by two style cells**.

Currently **22/22 green**, including after the §6 changes.
Extend the assertions when adding a cell — a green run is the gate, not a formality.

## 8. Remaining modularisation candidate

`app.php` — 1168 lines / 53.9k chars, the last monolith. Natural cell seams already
marked by its own section banners:

| Lines | Concern |
| --- | --- |
| 94–133 | `eveCardHTML()` — DS event-card contract |
| 134–196 | mini card (`.pev-browse` tax feed) |
| 197–227 | `.event-row` list row |
| 228–~300 | `mountInfinite()` — infinite-load + reveal engine |
| 453–~638 | masthead fit + chrome |
| 639–707 | hero highlight carousel |
| 708–~930 | hero fallback layer |
| 931–… | event open / lightbox hand-off |

**Not split yet, deliberately.** `app.php` is a *single IIFE* whose functions share
one closure (`root`, `state`, `heroTimer`, …). Splitting it across `<script>` blocks
breaks that scope unless the whole thing is first restructured onto an explicit
namespace object — a real refactor, not a file move. The harness asserts `app.php`
parses as one unit, so the split also needs assertion A extended per cell before it
can be trusted. Worth doing; wants its own pass with a browser available.
