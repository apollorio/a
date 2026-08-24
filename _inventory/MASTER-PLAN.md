# apollo::rio — Master Modularisation Plan

**Status:** authoritative. Consolidates the Phase 0-6 architecture plan, the
P1-P5 lightbox globalisation plan, and the Rail Flow masthead spec into one
ordered programme.

**Written:** 2026-08-06
**Scope:** 41 `apollo-*` plugins in `D:\dev\_apollo.rio.br\plugins`
**Sandbox:** `https://apollo.rio.br/` — RealTimeSync, **~30s propagation**
**Related specs:** `apollo-events/_sandbox/{PORTAL-MAP,SPEC-rail-masthead,IMPORT-MAP}.md`

---

## 0 · Governing rules

### R1 — Extreme modularity = many CELLS, few PLUGINS

`apollo-events/styles/base/template-parts/archive/portal/` is the proof: **19
files, one concern each, one owner, one plugin.** Every phase below replicates
that pattern *inside* existing plugins.

**Evidence for not splitting further:** the registry records 6 entries with no
folder on disk, and **two were absorbed back** — `apollo-classifieds` →
`apollo-adverts`, `apollo-shortcodes` → `apollo-core`. That experiment has
already been run and reversed.

**Evidence of boundary cost:** `10-coupling.json` gives `apollo-core`,
`apollo-templates`, `apollo-admin`, `apollo-pane-engine` **24-31 dependents
each.** Every new plugin boundary adds an activation-order edge, a
`function_exists` guard, and a way to half-deploy.

**Decisive evidence:** the three worst bugs of the 2026-08-05/06 sessions were
*all* plugin-boundary failures, none were module failures:

| Bug | Boundary that failed |
| --- | --- |
| Unstyled shell on 9 screens | topbar CSS in apollo-events, markup in apollo-templates |
| Publish fatal in 3 plugins | `apollo/event/published` arg contract drift |
| Double `<head>` | two blank-canvas mechanisms across two plugins |

A new plugin is created **only** where an integration genuinely fails
independently (e.g. `apollo-soundcloud` — already on disk, correct call).

### R2 — Save = deploy

30s propagation, no staging tier. **Never leave a file half-edited.** Verify in
the sandbox harness *first*, deploy *second*. Each phase below ends at a gate;
nothing lands without its gate green.

### R3 — The three self-correcting patterns (already proven in-tree)

These are the "smart intelligent self-adjusting code" requirement. No AI needed
— they are structural.

1. **Feature-detect at every boundary, never assume load order.**
   `if (function_exists('apollo_plus_open')) { … } else { /* real fallback */ }`
   → this is the "unplug here, plug there" behaviour.

2. **Fail loudly at the write boundary, never write a record you know is broken.**
   `422 apollo_import_no_date` — refusing to create an invisible event beats
   creating one and returning `201`.

3. **One owner per contract, guard-enforced — the second implementation
   self-disables.**
   `if (defined('APOLLO_PLUS_TOPBAR_STYLES')) { return; }`

### R4 — Contract-first

A contract is `data-*` attributes + a documented function signature + a REST
route. Contracts are **documented and tested**, never re-implemented per
consumer.

---

## PHASE 0 — Contract test *(the safety net — do first)*

**Problem:** all three fatals were cross-plugin contract drift, and **none** was
caught before production.

Build `_inventory/contract-test.mjs`, a static scanner over all 41 plugins.

| ID | Assertion | Would have caught |
| --- | --- | --- |
| **C1** | every `do_action('apollo/…')` arg count == every listener's declared signature | the 3 publish fatals |
| **C2** | every `register_rest_route` WRITE method has a real `permission_callback` — **must be multi-method aware** | — (registry's own scanner gives false positives here; verified) |
| **C3** | no CSS selector declared by two files | `.ax-main` two-owner bug |
| **C4** | every cross-plugin `apollo_*()` call is `function_exists`-guarded or in a manifest | — |
| **C5** | registry `09-plugins/*.json` routes == live `route_map()` | 7 undocumented routes found 2026-08-05 |

> **C2 implementation note:** one `register_rest_route()` call holds MULTIPLE
> method entries. A naive parser attributes the READ entry's `__return_true` to
> the write methods. Parse per method-entry, not per route.

- **Gate:** runs clean on current tree (fix or explicitly whitelist findings).
- **Test:** N/A — this *is* the test.
- **Deliverable:** `_inventory/contract-test.mjs` + `CONTRACT-WHITELIST.json`

---

## PHASE 1 — Boundary hardening *(no new files, no new plugins)*

Apply R3's three patterns across the 4 hub plugins (24-31 dependents each):
`apollo-core`, `apollo-templates`, `apollo-admin`, `apollo-pane-engine`.

**To-do**
1. Audit every cross-plugin call for a `function_exists` guard + real fallback.
2. Audit every write path for a loud-failure guard.
3. Audit every shared asset/CSS emitter for a `defined()` self-disable guard.
4. Fix the `apollo/v1/loc-resolve` route referenced in `/eventos/url`'s boot
   payload but **registered nowhere** (recorded `known_defect`).

- **Gate:** C1 + C4 green.
- **Test:** `/eventos`, `/portal`, `/feed`, `/anuncios`, `/comunas`, `/hub`,
  `/mapa`, `/eventos/meus`, `/anuncios/meus` all render.

---

## PHASE 2 — Entity Popup service *(biggest win; absorbs the P1-P5 lightbox plan)*

### What already works — do NOT rebuild

The lightbox is **80% done**. Verified by harness invariants E1-E10:

- renders via `GET apollo/v1/eventos/{id}/fragmento` — **the same PHP renderer
  as `/evento/{slug}`**, so page and popup cannot drift
- contract is already generic: `[data-ev-open="{id}"]` anywhere, plus
  `ApolloEventLightbox.open(id)`
- shell portals to `<body>` (2026-08-01) so it escapes `.ax-main`'s stacking
  context
- degrades to the real permalink; middle-click / ctrl-click / new-tab preserved
- `scroller: .ev-lb-scroll` passed to all 6 ScrollTrigger call sites
- content entry animation fixed 2026-08-06 (double-rAF; the "stairs" cut)

**What's missing is packaging, not capability.**

### P2.1 — Public enqueue API *(blocking for every other plugin)*

In **apollo-templates** (everything already depends on it → zero new edges):

```php
apollo_entity_popup_enqueue();   // idempotent; any plugin, any page
apollo_entity_popup_register( 'event', 'apollo/v1/eventos/{id}/fragmento' );
apollo_entity_popup_register( 'dj',    'apollo/v1/djs/{id}/fragmento' );
apollo_entity_popup_register( 'local', 'apollo/v1/locais/{id}/fragmento' );
```

Register both runtimes + `styles-lightbox` as real handles with deps. **Each CPT
plugin keeps owning its own fragment endpoint** — apollo-events knows what an
event fragment is; a generic plugin never will.

### P2.2 — Auto-boot on `wp_footer`

Print `[data-apollo-popup]` once per request.

> **⚠ CRITICAL:** `/eventos` already calls `apollo_event_lightbox_boot()`
> directly. Both paths **must** check the same constant or the page gets two
> shells and `querySelector` picks the wrong one.

### P2.3 — Generalise the attribute

`data-ev-open="{id}"` → `data-apollo-open="{type}:{id}"`.
**Keep `data-ev-open` as a permanent alias** — it is live in three card builders.

### P2.4 — Kill the triplicated selector list

The reveal selector list is hand-duplicated in `apollo-single-event.js`,
`portal/bootstrap.php`, `portal/styles-lightbox.php` — already a recorded sync
hazard. One source (localized array), three consumers.

### P2.5 — Mobile-first pass *(mobile is priority)*

- `env(safe-area-inset-*)` on panel + close button
- `100dvh`, never `100vh`
- sheet-style drag-to-dismiss
- verify `.ev-lb-scroll` against the iOS keyboard

### P2.6 — Enter/leave viewport animation

> **⚠ This is a previously-fixed failure mode.** ScrollTriggers created with the
> default `scroller: window` **never resolve inside the popup** — the document is
> Lenis-locked, the window never moves, and elements stay stuck at GSAP's inline
> `opacity:0` forever. Registry: `$lightbox_reveal_scroller_fix_2026_08_01`.
>
> Any enter/leave animation **must** resolve `scroller` per context
> (`root.closest('.ev-lb-scroll') || undefined`) and set `toggleActions` for the
> reverse leg.

- **Gate:** harness E1-E10 green; C3 green.
- **Test:** card click opens popup on `/eventos`, `/portal`, **and** `/feed`.

---

## PHASE 3 — Card + Listing contracts *(documented, not extracted)*

Write `apollo-templates/CONTRACTS.md`:

- **Card contract:** `data-apollo-open` + real `href` + `.a-eve-*` structure
- **Listing contract:** grid ⇄ list via `.pev-view-toggle` + `sectionViews`
  — **already built**, needs documenting not building

Then per-CPT: split card markup into its own cell, mirroring `portal/`.
apollo-djs and apollo-loc adopt the same cell layout as apollo-events.

- **Gate:** C3 green.
- **Test:** DJ and loc cards open popups identically to events.

---

## PHASE 4 — Single-page cells *(NOT a plugin)*

Split each CPT's single template into cells:

```
single/{head,hero,facts,about,access,media,related}.php
```

Shared *helpers* → apollo-templates (`apollo_entity_hero()`,
`apollo_entity_facts()`). CPT-specific meta stays home.

> **Why not `apollo-single-page`:** unifying creates a switch over
> `_event_ticket_status` / `_dj_tracks` / `_local_gallery`, and hands one plugin
> the SEO + schema of every entity. **Worse coupling than today.**

- **Gate:** C3 + harness green.
- **Test:** `/evento/…`, `/dj/…`, `/local/…` render standalone AND as popups.

---

## PHASE 5 — Rail Flow masthead *(UI; independent of 0-4)*

Full spec: `apollo-events/_sandbox/SPEC-rail-masthead.md`.

**Two non-obvious blockers:**

1. **The rolling window breaks the mockup's math.** Every month op is `% 12`
   against `var YEAR = 2026`. A `−1 … +N` window crosses year boundaries, so
   `(ACTIVE+1) % 12` silently wraps Dec → Jan *of the same year*. Model months as
   **absolute index** (`year*12 + month0`), built from `now`.

2. **Two filters must both survive.** Mockup panel = **taxonomy chips**; current
   header = **period modes** (`data-pev-mode` drives `state.mode`, which drives
   the entire feed render). Deleting period stops the portal filtering.

> **⛔ DECISION REQUIRED BEFORE STEP 3** — recommended:
> **rail = month + period, panel = taxonomy.**
> This decides whether `data-pev-mode` lives in the rail or the panel.

Also: mockup `APOLLO_TAXONOMY` term ids (87, 82, 79…) are **demo data** — real
chips must come from live terms.

**Order:** markup into `skeleton()` behind existing contract ids → move
`.pev-masthead` rules to `.hd-*`/`.h1-*` **in the same commit** → wire rail +
panel reusing `data-pev-shift`/`data-pev-mode` → real taxonomy → extend harness.

- **Gate:** harness C (contract ids) + D (single owner) green.
- **Test:** `/eventos` + `/portal` month navigation and filtering.

---

## PHASE 6 — Split `app.php` *(last monolith, 1168 lines — highest risk)*

Restructure the single IIFE onto an explicit namespace object, then split at the
eight seams its own banners already mark: card, mini-card, list-row,
infinite-load, masthead, hero, fallback, open.

> A naive split breaks shared closure scope (`root`, `state`, `heroTimer`).
> Namespace restructure comes **first**, split second.

- **Gate:** harness assertion A extended per cell.
- **Test:** full `/eventos` + `/portal` regression.

---

## PHASE 7 — Helper layer *(the "rich helpers" goal)*

Boundaries now stable. Add in apollo-templates:

```php
apollo_entity_get( $type, $id );      // normalised entity array
apollo_entity_card( $type, $id );     // card markup, popup-wired
apollo_entity_url( $type, $id );      // permalink
apollo_entity_popup_enqueue();        // from Phase 2
```

- **Gate:** C4 green.
- **Test:** a scratch plugin renders event + dj + loc cards **with working
  popups** using only the helper API — zero apollo-events includes.

---

## Corrections to the original plugin proposal

| Proposed | Verdict | Reason |
| --- | --- | --- |
| `apollo-cpt-cards` | ❌ module | Card markup is a contract → Phase 3 |
| `apollo-cpt-cards-listing` | ❌ module | Splitting forces N REST calls where one `WP_Query` exists |
| `apollo-cpt-cards-popup` | ⚠ service, not plugin | → Phase 2, inside apollo-templates |
| `apollo-single-page` | ❌ harmful | → Phase 4; unifying worsens coupling |
| `apollo-soundcloud` | ✅ already on disk | Correct boundary — external, optional, fails independently |
| List ⇄ Grid switch | ❌ exists | `.pev-view-toggle` + `sectionViews`; Phase 3 documents it |

---

## Execution rules

1. **One phase per session.** Each is a clean stopping point with a green gate.
2. **Phase 0 before anything else** — it is the net for every later phase.
3. **Never start a phase you cannot finish** — save = deploy.
4. **Gate green before deploy**, then wait 30s, then verify on
   `https://apollo.rio.br/`.
5. **Delete the superseded implementation in the SAME commit** as its
   replacement. Two owners for one contract is the cardinal sin.

## Order rationale

- **0** first — safety net.
- **1** next — hardens the 4 plugins everything depends on.
- **2** is the biggest user-visible win and unblocks plug-and-play for every
  other plugin.
- **3-4** standardise the CPT surface.
- **5** is independent UI and can run in parallel with 3-4 if needed.
- **6** last — highest regression risk, benefits most from 0-1's guards.
- **7** requires 1-4 stable.

## Open decisions blocking work

| # | Decision | Blocks | Recommendation |
| --- | --- | --- | --- |
| D1 | Rail vs panel ownership of `data-pev-mode` | Phase 5 step 3 | rail = month + period, panel = taxonomy |
| D2 | Popup for `dj` / `local` — do fragment endpoints exist? | Phase 2 test | verify before P2.1; build per-CPT if missing |
