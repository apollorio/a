# Global CPT lightbox — the plan

> Click a title anywhere → the full single page opens in place, over a **solid
> white** panel, with a close button. Same behaviour for every CPT.
> Status: **the machinery is already built and live for `event`. Nothing needs
> to be invented — three things need to be finished.**
>
> Companion docs: `AUDIT-2026-08-07-modularity.md` (why the contract exists),
> `apollo-events/_sandbox/SPEC-lightbox-integration.md` (the proven pattern),
> `registry/PLUGIN-DEPLOY-MAP.md` (blast radius & risk bands).

---

## 0 · What already exists — verified on disk, do not rebuild

The brainstorm proposes building an intercept, a fragment endpoint, a card
contract and a surface registry. **All four shipped on 2026-08-07.** Building
them again would fork a working system — the exact failure the surface contract
was created to prevent.

| Piece | Where it lives | State |
|---|---|---|
| Surface registry + auto REST fragment route | `apollo-core/includes/surface-contract.php` | **live** |
| Card contract emitter `apollo_surface_open_attrs()` | same file | **live** |
| One lightbox runtime, type-aware | `apollo-events/assets/js/apollo-event-lightbox.js` | **live** |
| Render SSOT (page = embed = fragment) | `apollo-events/includes/render-single.php` | **live**, event only |
| Full-viewport override CSS | `…/archive/portal/styles-lightbox.php` | **live** |
| mtime cache-busting on assets | `apollo-core/includes/asset-version.php` | **live** |
| `dj` surface declaration | `apollo-djs/includes/surface.php` | **declared, dormant** |
| `loc` surface declaration | `apollo-loc/includes/surface.php` | **declared, dormant** |

Three details from the brainstorm are already answered better than proposed:

- **Clean permalinks + intercept** — done, and it also honours ⌘/ctrl/middle
  click, falls back to the real page on fetch failure, and pushes state so the
  URL stays shareable. `href` is emitted *with* the data attribute by
  `apollo_surface_open_attrs()` so a card can't ship without its fallback.
- **`window.APOLLO_SURFACES`** already publishes the endpoint table from PHP —
  adding a surface is a **PHP-only change**. The JS never learns a path.
- **Fragment endpoint** is generic already: `GET apollo/v1/{rest_base}/{id}/fragmento`,
  registered once per usable surface, and it explicitly refuses to clobber
  apollo-events' existing `apollo/v1/eventos/{id}/fragmento`.

**The one honest correction to the brainstorm:** don't add
`apollo/events/render_loc_link` / `apollo/loc/single_fragment` hooks. The
surface registry replaces per-plugin hooks — one wire, not a hook per pair.
Adding them re-creates the N-copies problem.

---

## 1 · The real gaps

Everything below is verified, not assumed.

### ~~Gap A — `dj` and `loc` renderers do not exist~~ — WRONG, corrected 2026-08-17

**The DJ surface was already live.** `apollo-djs/includes/render-single.php`
defines `apollo_dj_render_single()`, `apollo_dj_single_context()` and
`apollo_dj_can_view()`, and `apollo-djs.php` requires it at line 98 — *before*
`surface.php` at line 99. So `apollo_surface_get('dj')` resolves,
`apollo/v1/djs/{id}/fragmento` is registered, and `open_attrs()` emits.

What misled this plan: `includes/surface.php` still carried a docblock headed
*"DECLARED, NOT YET LIVE"* plus a three-step recipe for building the renderer
that already existed. The 2026-08-07 audit said the same thing, and this plan
inherited it. **A stale "not yet built" note is worse than no note — it invites
a second implementation of something that ships.** Docblock corrected; harness
**E23** now fails on that exact contradiction so it cannot recur.

**The real gap was one level down:** the DJ `scripts` cell ships its runtime as
an inline IIFE, and `innerHTML` never executes `<script>`. A DJ fragment
injected into the lightbox rendered as correct but **dead** html — no dock, no
reveals, no shares. Fixed in apollo-events 1.7.2 by `hydrateScripts()`; see §2b.

`loc` remains genuinely dormant — `apollo_loc_render_single` is not defined.
The three-step recipe in §3 still applies **to `loc` only**.

### Gap B — the panel is not white in dark mode

Traced through the cascade:

```
.ev-lb-panel { background: var(--ev-bg) }      apollo-single-event.css:1042
--ev-bg: var(--white-1)                        apollo-single-event.css:43
html.dark-mode { --white-1: #0b0b0d }          (theme)
html.dark-mode { --rgb-diff: 255,255,255 }     apollo-auth-uni.css:121
--ev-ink: rgba(var(--rgb-diff),1)              apollo-single-event.css:45
```

So in dark mode the panel renders **near-black `#0b0b0d` with white text** —
the requirement is violated today. And the backdrop `.ev-lb-bd` is
`rgba(var(--rgb-diff),.72)` — alpha, so the page behind shows through during
the entry transition while the panel is still at `opacity: 0`.

> **The trap:** forcing only `background:#fff` gives **white text on white**,
> because `--ev-ink` inverts independently. The whole local `--ev-*` block has
> to be pinned together, or the fix produces a blank-looking panel — which is
> exactly the symptom the 2026-08-07 session chased for two rounds.

### Gap C — no REST fragment cache

Confirmed: no `rest_pre_dispatch` / `rest_post_dispatch` cache layer exists in
apollo-core. The brainstorm's caching proposal is genuinely new work — see §4,
with two corrections that matter.

### Gap D — carried over, still open

- **25 hand-printed asset tags** bypass `WP_Scripts`, so the mtime filter can't
  see them (`AUDIT-2026-08-07-modularity.md` Finding 2, table of sites).
- **`.event-row` cards carry no `href`** — `eventRowHTML()` emits
  `<div role="button" data-ev-open>`. No fallback, no middle-click, nothing for
  a crawler. Harness **E2** guards the note, not the fix.
- **`apollo-single-event.css` is loaded for every surface.** Fine for `dj`
  (visually adjacent); revisit before a 4th surface.

---

## 2 · The #FFF mandate — one owner, one block

**Rule as given:** the panel behind the fragment is always solid `#FFF`. Never
`rgba`, never a token that can flip, never `transparent`, never a blur that
lets the page behind read through. Close button always present.

**Owner:** `apollo-events/styles/base/template-parts/archive/portal/styles-lightbox.php`
— the declared portal-scoped override cell for the shared `.ev-lb` shell.
It already owns `.ev-lb-panel`'s geometry, so extending that same rule block is
*not* a second owner. Do **not** also edit `apollo-single-event.css:1042`; that
would put the property under two owners, which is the cardinal sin.

```css
/* styles-lightbox.php — inside the EXISTING .ev-lb.ev-lb .ev-lb-panel block */

/* Component-scoped token pin. Not :root — this is the blessed form.
   The panel is always a light surface, in both themes, so every --ev-*
   consumer inside the fragment stays readable. Pinning bg without ink
   is what produces white-on-white. */
--ev-bg:    #fff;
--ev-ink:   rgba(10,10,10,1);
--ev-mute:  rgba(10,10,10,.48);
--ev-faint: rgba(10,10,10,.28);
--ev-line:  rgba(10,10,10,.07);
--ev-line2: rgba(10,10,10,.12);
--ev-soft:  rgba(10,10,10,.035);

background: #fff;   /* literal, opaque, theme-proof */
```

```css
/* Backdrop: opaque too. The panel is full-bleed, so the backdrop is only
   ever visible during the entry/exit frames — which is precisely the moment
   the requirement is about. */
.ev-lb.ev-lb .ev-lb-bd {
  background: #fff;
  backdrop-filter: none;
  -webkit-backdrop-filter: none;
}
```

**Close button — already compliant, one check.** `.ev-lb-x` exists in
`apollo_event_lightbox_shell()` (`render-single.php:605`) with
`data-ev-lb-close`, an `aria-label`, Escape binding and focus trapping. It uses
`mix-blend-mode: difference` on a white glyph, which on a forced-white panel
resolves to **black** — correct and legible. The `@supports not` fallback stays.
No change needed; just re-verify after the background change lands.

**Known trade-off, stated plainly:** dark-mode users get a light reader panel.
That is the instruction, and it is defensible — the fragment is a document, not
chrome. If it ever needs revisiting, the pin is one block in one file.

**SHIPPED 2026-08-17** in `styles-lightbox.php`, guarded by harness **E20/E21**
(both validated against negative controls — confirmed to fail on the pre-fix
`var(--ev-bg)` and `rgba(…,.72)` values before being accepted).

A third defect surfaced while writing those assertions: §6 of the same cell had
an inline annotation **inside** an already-open comment. CSS comments do not
nest, so the block terminated early and a `.ev-wrap` width rule plus fourteen
lines of English prose were being handed to the CSS parser as source — the rule
escaping unscoped into the global cascade, reaching `/evento/{slug}` itself. It
read as a comment in every editor for months because syntax highlighters nest
and browsers do not. Fixed; harness **E24** now scans every `styles*.php` cell.

---

## 2b · Fragment hydration — what makes "any CPT" actually true

`innerHTML` never executes `<script>`. For events that was correct and
documented: the event fragment's only script is an inert `application/json`
config block, and behaviour comes from `ApolloEventSingle.mount()` afterwards.

That stopped being the whole story the moment a second surface existed.
apollo-djs composes its page from cells and ships its runtime as an inline IIFE
in the `scripts` cell — and that cell is **generated** by
`_sandbox/build-dj-cells.py` under a do-not-hand-edit banner.

**Rejected:** a per-surface `mount()` API. It would require hand-editing exactly
the generated cell that must not be hand-edited, once per surface, forever.

**Shipped:** `hydrateScripts()` in `apollo-event-lightbox.js` re-creates the
fragment's own `<script>` nodes so the browser executes them. Costs each plugin
nothing and works for every surface that will ever be registered.

| Guard | Why |
|---|---|
| order preserved, `src=` blocks the rest | re-creates what a parsed document gives free |
| `state.injectToken` generation counter | two fast opens can't interleave two runtimes |
| `EXECUTABLE` allow-list | `application/json` is data, not code |
| per-script containment + detached-node skip | one broken cell can't stop the page waking |

**Accepted limitation, recorded:** document-level listeners bound by a fragment
runtime outlive the panel — `unmountCurrent()` removes their targets, not the
listeners. Their lookups then resolve to `null` and the handlers no-op. Revisit
only if a surface binds something with side effects that survive its own DOM.

---

## 3 · Per-CPT rollout — the recipe, applied in order

Identical three steps per CPT. **Step 1 is a cut-and-paste whose entire test is
"the rendered page did not change."**

```php
// 1 · Extract the context. Move the inline block out of the template.
function apollo_dj_single_context( int $post_id ): array { /* … */ }
// single-dj.php then: extract( apollo_dj_single_context( $id ) );

// 2 · Render from it. Mirrors apollo_event_render_single().
function apollo_dj_render_single( int $post_id, array $args = array() ): string {
    ob_start();
    extract( apollo_dj_single_context( $post_id ) );
    // include the parts in order
    return (string) ob_get_clean();
}

// 3 · Nothing. 'renderer' in includes/surface.php already points here.
//     Endpoint, card contract and lightbox light up together.
```

Also add `apollo_{type}_can_view( int $id ): bool`. Without it the fallback is
published-only — safe, but it will hide legitimately-visible drafts from an
author previewing their own item.

### Order, and why

| # | Surface | CPT slug | rest_base | Why this position |
|---|---|---|---|---|
| 1 | `dj` | `dj` | `djs` | Declaration written, parts already split (11), lowest risk. |
| 2 | `loc` | **`local`** | `locs` | Same shape. Note the slug divergence — the surface key is `loc`, the CPT is `local`. Registration already handles it via `post_type_exists('local') ? 'local' : 'loc'`. |
| 3 | `classified` | `classified` | *(propose `classificados`)* | apollo-adverts, MEDIUM risk, monolithic template — needs a part split first. |
| 4 | `hub` | `hub` | *(propose `hubs`)* | Monolithic. Lower value; hub pages are already destinations. |

Rows 1–2 are **exact, already-registered names** — read them from
`apollo-djs/includes/surface.php` and `apollo-loc/includes/surface.php`, do not
retype them. Rows 3–4 are proposals; nothing is registered for them yet.

Everything past #2 needs a template-part split *before* the context extraction —
a larger job, no contract work required first.

### The card side, everywhere

```php
<a <?php echo apollo_surface_open_attrs( 'dj', $dj_id ); ?> class="…">
  <?php echo esc_html( get_the_title( $dj_id ) ); ?>
</a>
```

That emits `data-ap-open="dj:123" href="https://…/dj/slug/"` together, or `''`
while the surface is dormant. **Never hand-write the pair.** For the
event↔loc↔dj relations the ids are already in meta: `_event_loc_id` (int),
`_event_dj_ids` (array) — both registered in apollo-core, both in the registry.

---

## 4 · REST fragment caching

The brainstorm's sketch is sound in shape but has three defects that would bite
on this install. Corrected version:

1. **Never cache a logged-in response into a shared bucket.** The fragment is
   visibility-gated per user (`apollo_surface_can_view`), so one cache key
   serving both a guest and an author leaks drafts. Key on
   `is_user_logged_in()` — or simply **skip the cache entirely for logged-in
   requests**, which is the safer default and costs nothing (guests are the
   volume).
2. **`serialize()` on request params is unstable** — key order varies. Use
   `wp_json_encode()` of a `ksort`ed param array.
3. **`wp_cache_flush_group()` requires a persistent object cache that supports
   groups.** Without Redis it is a no-op or a full flush. Register the group
   with `wp_cache_add_global_groups()` and invalidate by explicit key, with a
   version-salt bump as the portable fallback.

Scope it to the fragment routes only — a blanket `rest_pre_dispatch` cache
across all 350 endpoints is a much bigger blast radius than this feature needs.

`Cache-Control: public, max-age=300` is right for the guest case and **wrong**
for the logged-in one; send `private, no-store` there. Do not hard-code
`X-Apollo-Cache: HIT` on every response — it must reflect the actual outcome or
it is worse than no header.

**Placement:** new file `apollo-core/includes/rest-fragment-cache.php`, required
from `apollo-core.php` alongside `surface-contract.php`. Bump the apollo-core
docblock **and** `APOLLO_CORE_VERSION` together (currently 6.2.9).

---

## 4b · Mockup parity — asked 2026-08-17, answered unevenly

**The question:** do the `dj` and `loc` mockups print the same final structure
and visuals as the shipped pages?

### `dj` — structurally yes, character-wise NO

The cells are sliced from `_sandbox/dj-single-page.mockup.html` by
`_sandbox/build-dj-cells.py`, and the structure still agrees: the `CELLS` line
ranges resolve to their expected anchors (427 `.pg`, 429–437 `.ev-top`, 439
`header.hero`) and the class vocabulary matches.

**But the mockup is character-corrupted and the shipped cells are not.**
152 mockup lines carry double-encoding damage; the 17 cells carry zero. The rot
happened *after* generation.

That inverts the usual instruction. **Do not force the cells to match the
mockup — the cells are the good copy.** Following the generator's own header
("edit the mockup, re-run the generator") would have pushed `CartÃ£o de artista`
onto the live `/dj/{slug}` page and into every DJ lightbox fragment.

Three locks shipped:

| Lock | Behaviour |
|---|---|
| generator fails closed | refuses to emit from a mockup it can prove is damaged; prints the damaged lines and the fix |
| `--repair-mockup` | inverse transform in Python, where the C1 bytes are addressable; writes **only** if it strictly reduces damage |
| harness **E25** | no shipped cell carries a signature, and the generator still has all three guards |

The transform was *not* a strict CP1252 round trip — first hypothesis, wrong.
Bytes `0x81 0x8D 0x8F 0x90 0x9D` are undefined in CP1252 and Python refuses
them, but the damaging tool passed them through raw: `═` (E2 95 90) became
`â` + `•` + U+0090. Confirmed by reproducing the damage and matching bytes.

> **Operator action:** `python3 apollo-djs/_sandbox/build-dj-cells.py --repair-mockup`,
> then re-run without the flag and review the diff. Until then the mockup is a
> damaged artefact, not the approved design.

### `loc` — not verifiable, and that is the finding

Two things are missing at once:

1. **No mockup in the repo.** `21-mockup-field-contract.json` audits
   `screen/single cpt/location/loc-single-page.html`, which lives outside the
   plugins folder and was never copied in. There is nothing here to compare to.
2. **No cell structure.** `styles/base/single-local.php` is a monolith with its
   context inline from line 31. `apollo_loc_render_single()`,
   `apollo_loc_single_context()` and `apollo_loc_can_view()` are all undefined —
   verified by grep across the plugin.

So the loc surface stays correctly dormant and its cards degrade to plain
anchors. Making it verifiable is steps 1–4 in the registry annotation; the
context extraction is the same cut-and-paste as `dj`, against a live page.

**Two warnings before anyone starts:**

- Check the loc mockup's **encoding first**. If it travelled with the DJ one it
  was likely damaged with it.
- The loc mockup still contains **three open philosophy violations** — a
  `#dockFollow` "Seguir" button with a heart icon, "Siga o local" copy, and a
  `.map-note` leaking raw lat/lng plus a non-existent CPT slug. Fix those in the
  mockup *before* any generator slices it, or a follow button becomes permanent
  in shipped cells — which `01-philosophy` forbids outright.

---

## 5 · Guards — what this plan must not do

Drawn from the registry chapters; each one has a real past violation behind it.

- **No new `:root` token.** Component-scoped custom properties only (§2 uses the
  blessed form).
- **No second owner for a selector.** `.ev-lb-panel`'s background gets pinned in
  `styles-lightbox.php` *or* the base sheet — not both.
- **No CPT / taxonomy / meta registration outside apollo-core.**
  `surface-contract.php` deliberately registers none; keep it that way.
- **No renaming shell contract ids**, and no new Apollo+ shell path —
  `apollo_plus_open()` / `apollo_plus_close()` only.
- **No `__return_true` on a write route.** The fragment endpoint is public
  *read-only* by design and gated by `can_view` — that is the only reason its
  `__return_true` is acceptable. Do not copy the pattern to anything that writes.
- **Forbidden vocabulary** in any new copy or identifier: venue/location→`loc`,
  interesse/bookmark→`fav`, like/heart/reaction→`wow`, comment/review→
  `depoimento`. And no follow buttons or follower counts, anywhere.
- **apollo-events is HIGH security-risk** (score 69) and the most actively-edited
  plugin in the ecosystem. Every edit there is security-sensitive.

---

## 6 · Verification gates

```
node apollo-events/_sandbox/build-portal-harness.mjs
```

31 assertions today; **E17/E18** already guard the surface contract and the
"declared in PHP, never hard-coded in JS" rule. Extend it as you go:

| New assertion | Asserts |
|---|---|
| **E20** | `.ev-lb-panel` background is a literal opaque colour — no `var()`, no `rgba()` with alpha < 1 |
| **E21** | `.ev-lb-bd` carries no alpha and no `backdrop-filter` |
| **E22** | the shell always contains exactly one `[data-ev-lb-close]` inside `.ev-lb-panel` |
| **E23** | every surface with a callable renderer also declares `can_view` |

Then, and only then, the live pass — there is no PHP binary and no headless
browser here, so this part is manual and **belongs to you**:

1. Save → **wait 30 s** for RealTimeSync. Testing sooner reads the old revision.
2. `/eventos` — a card still opens, panel is solid white in **both** themes,
   ✕ visible and legible, Escape closes, URL updates, back button closes.
3. `/dj/{slug}` renders **byte-identically** to before the context extraction.
4. A DJ card on `/eventos` opens the DJ fragment, not the event one
   (cache is type-scoped: `event:5` ≠ `dj:5`).
5. Console: `APOLLO_SURFACES` lists `event`, `dj`, `loc`; exactly one
   `[data-ev-lightbox]` in the document.

---

## 7 · Sequence

| # | Change | Risk | State |
|---|---|---|---|
| 1 | §2 white-panel pin + backdrop, `styles-lightbox.php` | low — CSS, one block | **DONE 2026-08-17** |
| 2 | §2 nested-comment fix, same cell | low — restores intended comment | **DONE** |
| 3 | §2b `hydrateScripts()` in the shared runtime | medium — HIGH-risk plugin, but additive; event path byte-identical | **DONE** |
| 4 | Harness **E20–E24** | none — sandbox only | **DONE** |
| 5 | `apollo-djs/includes/surface.php` docblock correction | none — comment only | **DONE** |
| 6 | Version bumps: events 1.7.1→1.7.2, djs 1.0.4→1.0.5 (docblock **and** constant) | none | **DONE** |
| 7 | Registry chapters updated with dated annotations | none | **DONE** |
| 8 | `apollo_loc_single_context()` + `_render_single()` + `_can_view()` | **medium — `/local/{slug}` is live and works today** | open |
| 9 | Convert DJ/loc card call sites to `apollo_surface_open_attrs()` | low | open |
| 10 | `rest-fragment-cache.php` + apollo-core bump | medium — highest-coupling plugin | open |
| 11 | Gap D cleanup (25 asset sites, `.event-row` href) | low, tedious | open |

**Step 8 is now the one that deserves the caution** — the same cut-and-paste
into a file that deploys the instant it is saved, on a page that works today.
Do it against the harness, save once, wait 30 s, verify `/local/{slug}` before
touching anything else.

Nothing in steps 1–7 changed a PHP execution path. The only behavioural change
is in JavaScript (`hydrateScripts`) and CSS, both additive, both harness-gated.
