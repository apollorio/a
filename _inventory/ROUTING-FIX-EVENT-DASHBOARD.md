# 🧭 Event Dashboard & Add-New Routing — Diagnosis + Fix

**Date:** 2026-07-21 · **Symptom:** `/painel/eventos`, `/portal`, `/novo-evento`, `/criar-evento`, `/add-evento` all 302-redirect to `/feed` instead of rendering the events panel / create form.

---

## Where the "blank canvas Apollo+" template lives

It is **not a single file** — it's a rendering pattern:

- **`apollo-core/src/Traits/BlankCanvasTrait.php`** → `render_blank_canvas($template, $vars)`: kills output buffers, forces `is_404=false / is_page=true`, `include`s the template, `exit`s. This is the "blank canvas" engine.
- **`apollo-core/includes/blank-canvas-templates.php`** + **`document-head.php`** → the shared `<head>` (Apollo / Apollo+ variants).
- The **event dashboard** template is `apollo-events/styles/base/dashboard-event.php` (app-shell + modular `template-parts/shared/*` + `template-parts/dashboard/*`), and the **add-new** template is `apollo-events/styles/base/create-event.php`. Both are already built on the same `form.html` app-shell and are mockup-aligned — they were simply **never being reached**.

## Why everything fell through to `/feed` (root causes)

1. **Rewrite rules were never flushed.** Both `apollo-events` and `apollo-dashboard` add their `/painel/*`, `/portal`, `/novo-evento`… rules on `init`, but the flush was gated on a **plugin-version option that was already current**. So WP's stored `rewrite_rules` never contained these patterns → the URLs matched nothing → WP fell back to the **posts index (`is_home() === true`)**.
2. **`mural-router` hijacked every `is_home` request.** `apollo-templates/includes/mural-router.php` (template_redirect **priority 5**) redirects logged-in users to `/feed` whenever the request looks like the landing/blog index. Because an unmatched virtual URL *is* `is_home`, mural-router fired on `/portal`, `/painel/eventos`, `/novo-evento`… and 302'd them to `/feed` **before** their owning plugin could render.
3. **`/painel/eventos` had no event-owned route** (it belonged to `apollo-dashboard`'s generic `dashboard.php`, not the event dashboard), and **`apollo-events`' `parse_request` fallback missed `novo-evento` / `criar-evento`** — so those were flush-only and thus dead.

## The fix (4 changes, deploy-safe, no manual "Save Permalinks")

**1 — `apollo-templates/includes/mural-router.php`** — never hijack a reserved Apollo route:
```php
if ( get_query_var('apollo_event_page') || get_query_var('apollo_dashboard_page') || … ) return;
if ( apollo_is_reserved_virtual_path() ) return;
```
This alone stops the `/feed` redirect for every virtual route regardless of flush state.

**2 — `apollo-events/src/Plugin.php` `route_map()` / `path_map()`** — single source of truth for the routes, used by both the rewrite rules and the flush signature:

| Route | → renders |
|---|---|
| `/painel/eventos`, `/portal`, `/portal/eventos`, `/meus-eventos` | **dashboard-event.php** (events panel — mockup `dashboard/dashboard.html`) |
| `/novo-evento`, `/criar-evento`, `/add-evento` | **create-event.php** (add-new — mockup `add-new/add-new-event.html`) |

**3 — `parse_request_fallback()`** — flush-independent + authoritative: sets `apollo_event_page` for these paths and **unsets `apollo_dashboard_page` / `pagename`**, so the event templates win over apollo-dashboard's generic panel and work even before a flush (nginx-safe).

**4 — signature-gated flush** — `flush_rewrites_if_needed()` now flushes when the **rule set changes** (`md5` of `route_map()`), not just on a version bump — so new/edited routes always go live on the next request, forever.

Plus `handle_virtual_pages()` now cleanly `return`s after each render, and each create/dashboard branch is login-gated (guests → `/acesso`).

## To-do 002 UX ("new tab, as if a new event, save → panel")
- Dashboard "Novo Evento" CTAs (`dashboard/header.php` + empty state) now `target="_blank" rel="noopener"` → open the create form in a new tab.
- `/novo-evento` with no `?edit=` renders a **fresh event** in create mode.
- On save, `create-event.php`'s JS config `dashboardUrl` now points at **`/painel/eventos`**, so saving name + date lands back on the events panel.

## Files changed
- `apollo-templates/includes/mural-router.php`
- `apollo-events/src/Plugin.php`
- `apollo-events/styles/base/dashboard-event.php`
- `apollo-events/styles/base/template-parts/dashboard/header.php`
- `apollo-events/styles/base/create-event.php`

## Verify after deploy
1. Load any event edit once (or hit any front URL) → `flush_rewrites_if_needed()` fires (signature changed).
2. Logged in: `/painel/eventos`, `/portal`, `/meus-eventos` → events dashboard; `/novo-evento`, `/criar-evento`, `/add-evento` → create form. None redirect to `/feed`.
3. If your host aggressively caches rewrites, one visit to **Settings → Permalinks → Save** applies the hard flush (soft flush already updates the option).

*No PHP binary in this sandbox — verified via brace/paren/bracket balance + route-map alignment trace; run `php -l` before shipping.*

---

# 🧭 v2 — 2026-07-21 (later): /painel + /dashboard claimed for the events dashboard + REST hardening

## Routing (2 files)

**`apollo-events/src/Plugin.php`** — `route_map()` + `path_map()` now also claim:

| Route | → renders |
|---|---|
| `/painel` | **dashboard-event.php** (was apollo-dashboard's generic panel, default tab **feed** → this was the "dashboard corrupted with feed code") |
| `/dashboard` | **dashboard-event.php** (was completely unrouted → `is_home` → mural-router 302'd it to `/feed`) |

`parse_request_fallback()` already unsets `apollo_dashboard_page`/`apollo_dashboard_tab` for claimed paths, so apollo-dashboard's `^painel/?$ → tab=feed` rule is dead for `/painel` even before any flush. The `route_map()` md5 signature changed → soft flush fires automatically on next request. apollo-dashboard sub-tabs (`/painel/favoritos`, `/painel/grupos`, `/painel/configuracoes`) remain untouched.

**`apollo-core/includes/route-helpers.php`** — added `'dashboard'` to `apollo_get_reserved_virtual_slugs()` (`'painel'` was already there) so mural-router can never hijack `/dashboard` pre-flush.

## REST hardening (`apollo-events/src/API/EventsController.php`, 4 edits)

1. `GET /eventos/{id}/estatisticas` — `__return_true` → `can_edit_event`. Was flagged **HIGH / PUBLIC_UNAUTHENTICATED** in both deep-audit registries; also violates NO_EGO_COUNTERS and leaked RSVP totals of private events.
2. `POST /eventos/{id}/participantes/{user_id}/check-in` — `current_user_can('edit_posts')` → `can_edit_event` (any Contributor could check in attendees on *someone else's* event).
3. `POST/DELETE /eventos/{id}/participantes` (RSVP) — added `validate_event_id` on `id` (blocked junk `apollo_event_rsvp` rows + stray author notifications for arbitrary post IDs).
4. `POST /eventos/{id}/notificar-warmup` — added `validate_event_id` on `id`.

## Audit verdicts (unchanged code, verified healthy)

- **Form → DB**: create/edit form posts JSON to `apollo/v1/eventos` with `X-WP-Nonce` (`wp_rest`); `save_event_meta()` sanitizes per meta key (absint / `sanitize_hex_color` / `esc_url_raw` / enum whitelists); repeaters (`dj_slots`, `access_buttons`, `gallery`) strictly shaped; non-publishers forced to `draft`; taxonomies via slug/ID whitelist; `dashboardUrl` → `/painel/eventos/`.
- **Dashboard**: server-rendered from `apollo_event_get_user_manageable_events()` (author OR co-author), all output escaped, publish action → `PUT /eventos/{id}` with nonce.
- **Single `/evento/{slug}`**: CPT rewrite slug `evento`, `single-event-runtime.php` → `single-event.php` via TemplateLoader; only raw echo is the self-built `$extra_head` buffer (standard, phpcs-ignored). RSVP tables checked via `SHOW TABLES` + `$wpdb->prepare` everywhere.
- `_debugCatalog` in `create-event.php` form config is **referenced by `apollo-events-create-bridge.js`** — left in place; gate behind `apollo_is_dev_mode()` in a future pass.

## Blank Canvas Apollo+ / Design System alignment (verified, no edits needed)

Both `dashboard-event.php` and `create-event.php` render through the full **Blank Canvas Apollo+** chain: `render_blank_canvas()` (BlankCanvasTrait) → `apollo_render_document_open()` (apollo-core/includes/document-head.php) → **shared/topbar.php + shared/aside.php** + overlay/panels. The shell markup matches the Design System contract in `apollo-rio-design-system/project/components/wp-map.md`: `shell/AppTopbar → .ax-top .ax-burger .ax-ic .ax-avb` (topbar.php ✓) and `shell-aside` copied from `apollo.theme.showcase.html` (aside.php `.ax-aside` ✓). `shell-styles.php` never redeclares `:root` — tokens come from core.js, as both mockups mandate.

## Verified
`php -l` (real PHP, host machine): **No syntax errors** in all 3 edited files.

---

# 🟢 v3 — 2026-07-21 (deploy verification, live apollo.rio.br)

Probe semantics: the fetcher returns a body only for HTTP 200 (verified via guaranteed-404 `/wp-json/apollo/v1/eventos/999999` → empty). Non-200 (301/302/401/403/404) → empty.

| Live probe | Result | Verdict |
|---|---|---|
| `GET /wp-json/apollo/v1/eventos?per_page=1` | 200, full event JSON (id 25) | REST core ✓ |
| `GET /dashboard` (guest) | non-200 (redirect) | **Route claimed** — old bug would 200 the posts index; login gate → `/acesso` ✓ |
| `GET /painel` (guest) | non-200 (redirect) | Same ✓ — feed tab dead |
| `GET /wp-json/apollo/v1/eventos/25/estatisticas` (guest) | non-200 (forbidden) | **REST hardening live** ✓ |
| `GET /evento/dismantle-2/` | **was: fatal** `apollo_event_parse_date() undefined @ single-event.php:38` | Prod ran a **stale pre-2.2.1** copy — FreeFileSync had not propagated the defensive-load version |
| `GET /evento/dismantle-2/` (after v2.2.2 force-sync bump + ~30s) | 200 — full render: apollo-seo head (og/twitter/canonical), hero dates, RSVP/Warm-Up, Ingressos from `_event_ticket_url`, YouTube embed, CSP nonce | **Fatal healed** ✓ |

**Incident note (FreeFileSync):** `single-event.php` is a repeat sync-straggler (v2.2.1 was already a "force-sync marker"). When prod shows a fatal for a function that exists locally, suspect a stale file FIRST — bump a comment/version marker to re-queue the copy. Consider switching FreeFileSync to content-compare (not date/size) for `apollo-events/styles/**`.

**Cross-plugin chain (verified in code, all consumed endpoints/helpers exist):** `apollo/v1/djs` (apollo-djs DJsController), `apollo/v1/local` (apollo-loc LocalsController, rest_base `local`), coauthor helpers (apollo-events functions.php + functions-global.php; apollo-coauthor byline filters wired), `apollo/seo/head` → `Meta::output_head_tags_only()` → meta + `Schema::build()` Event JSON-LD on blank-canvas pages (apollo-events' own wp_head StructuredData dormant there — no duplication), apollo-dashboard sub-tabs intact.

**Still needs a logged-in eyeball (can't be probed as guest):** `/dashboard` events panel render, `/novo-evento` form, save→`/painel/eventos` round-trip.

*P4 cleanup noted: apollo-seo `Meta.php:335` canonical `home_url('/portal/eventos')` describes a now login-gated route (dead config on blank canvas — no live harm).*

---

# 🎧 v4 — 2026-07-21 (later): DJ single `/dj/{slug}` → mockup v3 template

## Root cause (why /dj/{slug} never matched `dj-single-page.html`)

`APOLLO_DJ_DEFAULT_STYLE` was `'apollo-v1'` — **a style folder that does not exist** (`styles/` contains only `apollo-v3/` and `base/`). `TemplateLoader::locate()` therefore silently fell through to the minimal `styles/base/single-dj.php` on every request. The mockup build (`templates/single-dj-v3.php` + 12 `parts/dj-v3/*` sections: hero → metrics → agenda → marquee → sounds → bio → epk → gallery → depoimentos → booking → footer → fab-menu, MusicGroup JSON-LD) was fully present but unreachable.

**Fix:** `apollo-djs/includes/constants.php` → `APOLLO_DJ_DEFAULT_STYLE = 'apollo-v3'`. The v3 bridge (`styles/apollo-v3/single-dj.php`) delegates to the master template. `/djs` archive unaffected (no v3 archive file → base fallback unchanged).

**Live verified:** `/dj/bernardo-campos/` now serves v3 (title `— DJ · apollo.rio.br`, hero/marquee/bio/gallery/booking/fab all render; empty-profile sections hide via their conditionals).

## Add/Edit DJ form ↔ single page field chain (verified 1:1)

Form = **`/editar/dj/{id}`** (apollo-templates `FrontendEditor` + `apollo-djs/includes/frontend-fields.php`).

| Form input (meta) | Rendered on single v3 |
|---|---|
| `_dj_name`, `_dj_bio_short`, `_dj_bio` | hero + bio + meta description + schema |
| `_dj_original_project_1..3` | projects |
| `_dj_sounds` (tax `sound`, shared with events) | sounds section + schema `genre` |
| 16 link keys (`_dj_website/instagram/soundcloud/spotify/youtube/mixcloud/facebook/twitter/tiktok/bandcamp/beatport/resident_advisor/set_url/media_kit_url/rider_url/mix_url`) | grouped links (music/social/pro/assets) + EPK |
| `_dj_banner` (cover), `_dj_image` (avatar) | hero/banner via `apollo_dj_get_banner/image` |
| `_dj_verified` (readonly), `_dj_user_id` (hidden) | verified badge / owner link |

Save path security: `check_ajax_referer('apollo_frontend_editor')`, per-post capability (author / `manage_options` / `_dj_user_id`-linked user via `apollo_editor_can_edit_dj`), per-type sanitization (`esc_url_raw` for all URL fields), **readonly enforced server-side** (skipped in save loop → users cannot self-verify).

## Gaps / flags

1. **`_dj_gallery` has no frontend-editor input** (admin metabox only; FrontendEditor lacks a gallery field type). Gallery/Moments + EPK press photos render on the single but DJs can't self-manage them from `/editar/dj/{id}`. P3.
2. **Prod displays PHP notices** (visible `Deprecated: the_block_template_skip_link…` in the footer; earlier the parse_date fatal printed a Query Monitor panel). `WP_DEBUG_DISPLAY` / `display_errors` is ON in production — set `WP_DEBUG_DISPLAY false` + `@ini_set('display_errors','0')` in wp-config. **P1 hygiene, host-side** (wp-config is outside the plugins tree).
3. DJ post author display name on REST reads `security.js` — looks like a leftover dev/admin account name on prod. Cosmetic but public. P4.
4. Mockup file `D:\dev\_dev web\screen\single cpt\dj\dj-single-page.html` is outside the mounted folders this session — byte-level diff not run; v3 is the build derived from that mockup and its section inventory matches. Mount `_dev web` for a literal diff pass.
