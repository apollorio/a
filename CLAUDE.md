# apollo::rio — plugins workspace

Project instructions for this Cowork session. Read this before touching anything.

## Deployment: this folder is live

`D:\dev\_apollo.rio.br\plugins` is mirrored to
`https://apollo.rio.br/wp-content/plugins/` by **RealTimeSync**.

- **Propagation delay: ~30 seconds.** After writing a file, wait 30s before
  testing in the local browser or via the Chrome extension. Testing sooner
  reads the previous revision and produces false negatives.
- There is no build step and no staging tier. **A saved file is a deployed
  file.** Never save a half-finished edit "to come back to it".
- Corollary: verify in the sandbox *first*, deploy *second*. A broken
  `<script>` in a client-rendered screen renders nothing at all, not a
  degraded page.

## Design system: Blank Canvas Apollo+

Two page templates carry the whole ecosystem
(`apollo-core/includes/blank-canvas-templates.php`):

| Variant | Chrome | Used by |
| --- | --- | --- |
| **Apollo** | none — bare canvas | single-event, single-dj, `/acesso` |
| **Apollo+** | fixed topbar + aside drawer | `/casa`, `/eventos`, `/portal`, `/feed`, `/mapa`, `/hub`, `/modera` |

Apollo+ is opened with `apollo_plus_open()` / closed with `apollo_plus_close()`
(`apollo-templates/includes/apollo-plus-api.php`). One entry point, one shell.

**The DS files are the source of truth. Copy from them, do not re-tune them:**

- `apollo-templates/templates/template-parts/apollo-plus/topbar-styles.php`
- `apollo-templates/templates/template-parts/apollo-plus/aside-styles.php`

### Non-negotiable rules

1. **`:root` belongs to core.js.** Never declare a design token in a template.
   Component-scoped custom properties (`.pev { --pev-band: … }`) are fine and
   encouraged; a new `:root` entry forks the token system and hides the real
   defect. See the removed-`--fsx` note at the bottom of `aside-styles.php`.
2. **Never rename the shell contract ids.** core.js binds them with zero extra
   JS: `#burger #ax-aside #ax-overlay #ic-act #ic-apps #ic-pf #apps-pop
   #aside-pill [data-close] [data-close-apps] .panel-tab .panel-tab-pane
   [data-modal] [data-modal-close] .modal-backdrop`.
3. **Geometry constants that already exist:** topbar is `top:2px; height:56px`
   → its bottom edge is **58px**. `html.ax-body` gets `padding-top:56px`. The
   aside pins only at **≥1000px**, width `clamp(200px,20vw,225px)`. `.ax-main`
   at that breakpoint owns the page gutters — a screen adding its own doubles
   them.

### The DS visual language

```
glass panel   rgba(var(--rgb-theme),.25) + blur(20px) saturate(180%)
etched bevel  inset 0 1px 0 0 rgba(var(--rgb-theme),.9),
              inset 0 0 0 1px rgba(var(--rgb-theme),.4),
              1px solid rgba(var(--rgb-diff),.04)
mono eyebrow  var(--ff-mono), uppercase, .12–.14em tracking, 9–10px, --muted
display type  var(--ff-heading), 800, negative tracking
radii         --r-lg --r --r-sm --r-xs --r-pill
easing        --ease  --ease-snappy  --ease-smooth
```

## Architecture: modular cells

Screens are split into **cells** — one file, one concern, one owner. A theme
overrides a single cell without forking the screen.

**The cardinal sin is two cells declaring the same selector.** That is how
`.pev-chrome{position:absolute;top:58px}` survived long enough to print the
month title on top of the hero on `/eventos`: `styles-chrome.php`,
`styles-responsive.php` and `styles.php`'s shell-fit block all claimed it, and
whichever loaded last silently won.

When you touch a cell, state its ownership in the file header and delete the
duplicate declaration elsewhere — with a comment saying where it went.

### Portal de Eventos cells

`apollo-events/styles/base/template-parts/archive/portal/`

| Cell | Owns |
| --- | --- |
| `bootstrap.php` | mount, Lenis/ScrollTrigger sync, modal scroll lock |
| `data.php` | `window.APOLLO_EVENTS` payload |
| `helpers.php` | `APOLLO_PORTAL` — pure derivation, holds no data |
| `app.php` | `skeleton()`, render functions, dock, feeds |
| `styles.php` | loader (cascade order matters) + shell fit |
| `styles-masthead.php` | **`--pev-*` rhythm contract, masthead rail, scroll dock** |
| `styles-chrome.php` | month stepper + filter menu controls only |
| `styles-hero.php` | hero band, slider, fallback layers, recent column |
| `styles-rails.php` | compact rows, rails, card grid |
| `styles-browse.php` | taxonomy chips, section headers |

`styles-masthead.php` is required **last** and is the declared last-word cell
for spacing and masthead geometry.

## Plugin ecosystem: registry map & safe-edit rules

`_inventory/registry/` is the chaptered SSOT (single source of truth) for all 46
`apollo-*` plugin entries — architecture layers, coupling edges, security risk
bands, open backlog, naming/philosophy guards, and the canvas shell contract
history. Full synthesis, with tables, lives in
`_inventory/registry/PLUGIN-DEPLOY-MAP.md` — **read it before editing any
`apollo-*` plugin you haven't touched recently.** Highlights:

- **Six registry entries have no folder on disk** and must not be edited or
  built against: `apollo-cena`, `apollo-classifieds` (absorbed into
  `apollo-adverts`), `apollo-pwa`, `apollo-runtime`, `apollo-shortcodes`
  (absorbed into `apollo-core`), `apollo-suppliers`.
- **`apollo-login`, `apollo-events`, `apollo-membership` are the HIGH
  security-risk plugins** — extra scrutiny on any edit, not just
  security-labeled ones.
- **`apollo-core`, `apollo-templates`, `apollo-admin`, `apollo-pane-engine`**
  each have ~24–31 dependent plugins — check `10-coupling.json` before
  changing a shared function, hook, or REST contract there.
- **CPT/taxonomy/meta registration is exclusively apollo-core's job**
  (`apollo-core/src/Core/MetaRegistry.php`). A plugin declaring its own is a
  registry violation even if it technically works.
- The three-shell divergence documented in `18-canvas-shell.json`
  (`$unification_2026_08_05`) is a real past instance of this file's cardinal
  sin — `apollo_plus_open()`/`apollo_plus_close()` is the only canonical
  shell entry point; don't resurrect the two legacy paths for new work.
- `apollo-core`'s registry chapter itself lags disk — it once flagged the
  plugin as stuck at version 6.2.3, but live source is already at 6.2.9 with
  docblock and `APOLLO_CORE_VERSION` agreeing (checked 2026-08-14). Don't
  trust registry version numbers without spot-checking the live file; do
  keep bumping the docblock and constant together on every apollo-core edit.

## Verification before delivery

Nothing ships without a sandbox pass. There is no PHP binary and no headless
browser in this environment, so verification is structural + a runnable file:

```
node apollo-events/_sandbox/build-portal-harness.mjs
```

It stitches the real cells (reads them, never copies) into
`_sandbox/portal-harness.html` — openable in any browser — and asserts:

- **A** every emitted `<script>` parses
- **B** `skeleton()` and `heroFallbackHTML()` are tag-balanced
- **C** every `#id` the JS queries exists in the markup it builds
- **D** no selector is declared by two style cells

Extend the assertions when you add a cell. A green run is the gate, not a
formality — it has already caught a three-way owner conflict on `.pev-recent`.

## Verified facts

- `https://assets.apollo.rio.br/iframe/highlighted-fallback_portal-events.html`
  is live and returns `text/html` (title: *Apollo Shimmer*). It is the final
  fallback layer for the portal hero when no event carries `highlight:true`.
- `highlights()` in `helpers.php` deliberately does **not** fall back to
  `featured()`. Labelling the month's best-ranked event "Destaque" asserts an
  editorial decision nobody made. No `_event_highlighted`, no highlight.

## Environment notes

- Sandbox has Node 22, no PHP, no Chromium (the Playwright download is blocked
  by the network allowlist).
- Claude in Chrome was not connected during the 2026-08-01 session — live
  visual verification had to be handed to the user.
