# PLAN — (a) Out Now releases · (b) Hostel accommodations

> Deep investigation completed 2026-08-17 across apollo-core, apollo-djs,
> apollo-adverts, apollo-lux-panels, apollo-templates, apollo-dashboard and
> apollo-hub. **No code was changed.** This document is the plan.
>
> Companions: `PLAN-surface-lightbox-global.md` (the surface contract),
> `registry/PLUGIN-DEPLOY-MAP.md` (blast radius), `AUDIT-2026-08-07-modularity.md`.

---

## 0 · What governs both tracks

From `00-registry-map.json.build_rules`:

```
forbid_direct_meta_cpt: true
require_apollo_core_master: true
```

Non-negotiable consequences for everything below:

| Rule | Where the work lands |
|---|---|
| CPTs are declared in one place | `apollo-core/config/cpts.php` — the owner plugin then registers the real one, guarded by `post_type_exists()` |
| Meta is registered in one place | `apollo-core/src/Core/MetaRegistry.php`, or contributed via the `apollo_core_register_meta` filter (the additive merge apollo-adverts already uses) |
| Structured (array-of-object) meta needs a schema chapter | `20-meta-schemas.json` |
| One sanitizer shared by **every** write path | the `_dj_tracks` precedent — REST and metabox call the same callback |
| Naming | `venue/location→loc`, `like/heart/reaction→wow`, `comment/review→depoimento`, `interesse/bookmark→fav` |
| Philosophy | no follow, no follower counts, no ego counters — anywhere |

---

## 1 · The missing primitive, shared by (a) and (b)

Both features are, underneath, the same unsolved problem: **one card, rendered
identically in many contexts, owned by one file.**

The investigation found the cost of not having that:

- An accommodation card exists **four times** with **completely different DOM** —
  `marketplace/parts/card-accommodation.php` (`.accom-*`), `templates/list-item.php`
  (`.resale-ticket`), `new-home/crash.php` (`.nh-mq-card`), and
  `apollo-dashboard/…/panel-feed.php` (`.ap-accom-embed`).
- The dashboard copy **redeclares the bare selectors** `.accom-badge`,
  `.accom-title`, `.accom-price` (`apollo-dashboard/…/dashboard/styles.php:800,869,917`)
  — a cross-plugin version of the cardinal sin.
- An "Out Now" section exists **five times**: `parts/dj-card/out-now.php`,
  `styles/base/template-parts/single/sound.php`, `new-home/tracks.php`, plus two
  orphans (`parts/dj-v3/sounds.php`, `apollo-hub/…/tracks.php`). Three shapes.
- `.accom-*` CSS **is not loaded on `/anuncios` at all** —
  `archive-classified.php:10` deliberately stopped linking `marketplace.css`, and
  the only head payload (`parts/mk/styles.php`) styles the grid but not the card.
  **The live marketplace renders accommodation cards with no card CSS.**
- `parts/mk/styles.php:42-71` styles a `.mk-ticket*` / `.mk-ac*` vocabulary that
  **no template emits**, while `.is-locked` and `.btn-accom--hostel` — which
  templates *do* emit — have **zero CSS anywhere**.

The ecosystem already solved the identical problem one level up. `apollo-core`'s
Surface Contract gives any CPT a single-page renderer, a REST fragment and a card
trigger from one `apollo_surface_register()` call. **Cards need the same wire.**

### `apollo-core/includes/card-contract.php` — new, ~180 lines

```php
apollo_card_register( 'accommodation', array(
    'renderer'  => 'apollo_adverts_render_accommodation_card', // fn(int $id, array $ctx): string
    'styles'    => 'apollo_adverts_accommodation_card_styles', // fn(): string — emitted once
    'post_type' => 'classified',
    'variants'  => array( 'grid', 'rail', 'embed' ),
) );

echo apollo_card_render( 'accommodation', $id, array( 'variant' => 'rail' ) );
```

Deliberately mirrors the surface contract, including its two proven safety
properties:

- **Dormant-safe** — a card whose renderer is not callable returns `''`. A
  consumer can be written before the card exists.
- **Print-once styles** — same ledger idea as `apollo_event_lightbox_state()`.
  This is what structurally fixes "the CSS isn't loaded on this screen": the card
  brings its own, once, wherever it is used.

**Why this and not a shortcode or a `get_template_part()` convention.** Both
already exist here and neither prevented four divergent copies — because neither
gives the card a *single owner* or a *style ledger*. The registry is what makes
"there is exactly one accommodation card" a checkable fact rather than a habit.

**To-dos**

- [ ] **P1-1** Write `apollo-core/includes/card-contract.php`: `apollo_card_register()`,
      `apollo_card_get()`, `apollo_card_render()`, `apollo_card_styles_once()`,
      `apollo_card_registry()`. Registers no CPT/meta/table.
- [ ] **P1-2** `require_once` it in `apollo-core.php` beside `surface-contract.php`.
      Bump docblock **and** `APOLLO_CORE_VERSION` together.
- [ ] **P1-3** Harness assertion: the contract registers no CPT/meta; a dormant
      card renders `''`; styles emit at most once per request.
- [ ] **P1-4** Registry: new `$card_contract` annotation in `09-plugins/apollo-core.json`.

---

# TRACK A — Out Now (track releases)

## A0 · What exists today, measured

| Layer | State |
|---|---|
| Storage | `_dj_tracks`, array-of-object post meta on the `dj` CPT, schema **v2**, registered `MetaRegistry.php:428-466` with a full `show_in_rest` schema |
| Sanitizer | `apollo_dj_sanitize_tracks_meta()` (`apollo-djs/includes/functions.php:285`) — v2, drops rows missing title/artists/duration or all four `url_*` |
| Read | `apollo_dj_get_tracks()` (`:370`) — v2-aware, emits a superset with back-compat `url`/`year`/`meta` |
| Admin authoring | `apollo-lux-panels/includes/DjPanel.php:87-120`, `repeater_card`, all 14 v2 subfields — **works** |
| Frontend authoring | **none, and structurally impossible** |
| REST | **no tracks endpoint** |
| Aggregate | `apollo_get_latest_dj_tracks()` (`:452`) — powers /casa |

### Three defects the investigation surfaced

**A0-D1 — the REST quick-add is silently non-functional.**
`DJsController::save_meta()` (`src/API/DJsController.php:509-526`) writes v1 rows
`{title,url,duration,year}`. The v2 sanitizer runs on that write and discards
**every** row, because none has `artists` or a `url_*`. The docblock at
`functions.php:269-274` claims direct `update_post_meta()` bypasses
`sanitize_meta()` — **that is factually wrong**; WP core's `update_metadata()`
calls it on every write. So the "Cadastrar novo DJ" quick-add loses tracks with
no error.

**A0-D2 — the DJ-page track renderer is v1-only.**
`assets/js/dj-card-single.js:98-129` reads `t.url`, `t.title`, `t.meta` and
nothing else. `artists`, `cover_url`, `bpm`, `album`, `label`, `genre` and the
four individual `url_*` are discarded. Playback hardcodes SoundCloud (`:138`), so
a Spotify-only or Bandcamp-only track loads a broken iframe. It works at all only
because `apollo_dj_get_tracks()` back-fills the v1 aliases. **`new-home/tracks.php`
is the only fully v2-aware render surface in the ecosystem.**

**A0-D3 — `apollo_get_latest_dj_tracks()` is an unbounded N+1.**
`get_posts(post_type=dj, -1, fields=ids)` then one `get_post_meta` per published
DJ, no cache, on every `/casa` render.

## A1 · The decision — `track` becomes a CPT

**Recommended, and it is what the brief describes.** A repeater on the DJ post
cannot deliver what was asked, for four structural reasons:

1. **Author attribution.** "DJs can add with author automatic as their names" is
   `post_author` — a post-level fact. A meta row on someone else's post has no
   author and cannot get one.
2. **Frontend authoring is impossible today.**
   `apollo-templates/src/FrontendEditor.php:900` — `sanitize_field()` returns
   `string` and has no array/repeater branch. A repeater can never be edited from
   the front end without rewriting that method. A CPT needs nothing new: the
   editor already handles scalar fields on a post.
3. **A release credits more than one artist.** Meta on one DJ cannot express a
   B2B or a remix credit without duplicating the row on both DJs — two rows that
   immediately drift.
4. **`/lancamentos` already has a link and no destination.**
   `new-home/tracks.php:51` points at `home_url('/lancamentos')`; grep finds no
   route, template or page. A CPT with `has_archive` supplies it for free.

**Migration is additive and reversible.** `_dj_tracks` stays registered and
readable for one release; `apollo_dj_get_tracks()` becomes a merge of CPT rows
and legacy meta rows, so nothing on screen changes on the day the CPT ships.

### Naming — decision required before P-A2

`15-conventions.json` has no ruling here. `track` is the field name already in
use (`_dj_tracks`, `tracks[]`, `#trkList`). **Proposed CPT slug `track`, rewrite
`/lancamento`, archive `/lancamentos`, `rest_base` `tracks`** — archive matches
the link that already exists. Confirm before registering; a CPT slug is a
migration once live.

## A2 · Phases

### Phase A2 — register the CPT · *no UI yet*

- [ ] **A2-1** `apollo-core/config/cpts.php`: add `track` beside `dj`
      (`:56-79` is the template) — `owner => apollo-djs`, `rewrite => 'lancamento'`,
      `archive => 'lancamentos'`, `rest_base => 'tracks'`,
      `supports => ['title','editor','thumbnail','author']`,
      `menu_icon => 'dashicons-album'`.
- [ ] **A2-2** `apollo-djs/src/Registry.php`: register the real CPT on `init:5`,
      guarded by `post_type_exists()`, mirroring `:35-79`.
      `capability_type => 'post'`, `map_meta_cap => true`.
- [ ] **A2-3** Meta via the `apollo_core_register_meta` filter (additive merge,
      `Registry.php:26,107-144`). Flat scalars, one per v2 subfield —
      `_track_artists`, `_track_duration`, `_track_bpm`, `_track_release_date`,
      `_track_album`, `_track_label`, `_track_genre`, `_track_cover_id`,
      `_track_url_soundcloud`, `_track_url_spotify`, `_track_url_bandcamp`,
      `_track_url_download`. **Flat, not a repeater** — that is the whole point:
      scalars are frontend-editable, a repeater is not.
- [ ] **A2-4** Relation to DJs: `_track_dj_ids` (array of int), mirroring
      `_event_dj_ids`. One release, many artists.
- [ ] **A2-5** `20-meta-schemas.json`: record the `track` field contract and mark
      `_dj_tracks` v2 as **superseded, retained for migration**.
- [ ] **A2-6** Taxonomy: reuse `sound`, do **not** create a genre taxonomy.
      `_track_genre` in the v2 schema becomes a `sound` term.

### Phase A3 — one read path, one write path

- [ ] **A3-1** `apollo_track_query( array $args ): WP_Query` in
      `apollo-djs/includes/tracks.php` — the single query. **Kills A0-D3**:
      `posts_per_page` + `meta_key=_track_release_date` ordering replaces the
      per-DJ loop.
- [ ] **A3-2** Rewrite `apollo_get_latest_dj_tracks()` as a thin wrapper over it.
      Keep the name — `new-home/tracks.php:31` and its consumers stay untouched.
- [ ] **A3-3** `apollo_dj_get_tracks( $dj_id )` returns CPT rows for that DJ
      **merged with** legacy `_dj_tracks` rows, de-duplicated on title+release_date.
      Same output shape. Nothing downstream changes.
- [ ] **A3-4** **Fix A0-D1.** Either delete the v1 quick-add fields from
      `DJsController::save_meta()` (`:509-526`) or route them through a real
      track insert. Correct the false docblock at `functions.php:269-274`.
- [ ] **A3-5** One-shot migration `apollo-djs/_sandbox/migrate-tracks.php`:
      dry-run by default, `--write` to commit, idempotent, sets
      `post_author` = the DJ's `_dj_user_id` when present, else an admin.

### Phase A4 — the card, and the surfaces that consume it

- [ ] **A4-1** `apollo_card_register('track', …)` in apollo-djs. **One** renderer,
      variants `rail` (/casa), `list` (/dj/{id}), `grid` (/lancamentos).
- [ ] **A4-2** **Fix A0-D2.** Rewrite `dj-card-single.js:98-129` against the v2
      shape: `artists`, `cover_url`, and per-platform playback instead of a
      hardcoded SoundCloud iframe.
- [ ] **A4-3** Repoint the three live Out Now surfaces at `apollo_card_render`:
      `parts/dj-card/out-now.php`, `styles/base/template-parts/single/sound.php`,
      `new-home/tracks.php`. Guest masking on /casa (blurred covers, `● ● ●`)
      becomes a card variant, not a fourth copy.
- [ ] **A4-4** `/lancamentos` archive template — Apollo+ shell via
      `apollo_plus_open()`/`apollo_plus_close()`, `apollo_listing_header()` for
      the filter band. **No new shell path.**
- [ ] **A4-5** `apollo_surface_register('track', …)` — single track opens in the
      lightbox everywhere, for free.

### Phase A5 — authoring

- [ ] **A5-1** Admin: a `track` metabox in **apollo-lux-panels** (the declared
      metabox owner), flat fields. Retire the `_dj_tracks` `repeater_card` from
      `DjPanel.php` only after migration is verified.
- [ ] **A5-2** Frontend, DJ-authored: reuse `FrontendEditor`. `can_edit()`
      (`:592-630`) already accepts post author **or** `_{cpt}_user_id` match —
      wire `_track_user_id`, or rely on `post_author`.
- [ ] **A5-3** Create path copies the proven apollo-adverts shape:
      `post_author => get_current_user_id()`,
      `post_status => current_user_can('publish_posts') ? 'publish' : 'pending'`
      (`DJsController.php:220-229` already does exactly this for `dj`).
- [ ] **A5-4** Moderation: `pending` tracks route to apollo-mod, not a new queue.
- [ ] **A5-5** REST `POST /apollo/v1/tracks` — `permission_callback` =
      `is_user_logged_in()`, ownership guard on update/delete matching
      `ClassifiedsController.php:486`.

---

# TRACK B — Hostel accommodations

## B0 · What exists today, measured

Most of the mechanism is **already built**. This is closer to a correction than a
construction.

| Piece | State |
|---|---|
| Type split | `_classified_type` meta (`MetaRegistry.php:814-821`) — **not** a taxonomy |
| Two spellings per kind | `ticket`/`ticket_sell`, `accommodation`/`rent_space`, normalised by `apollo_adverts_canonical_type()` (`cpt.php:257-265`) |
| Hostel flag | `_classified_hostel` (bool) + `_classified_hostel_url` — **already exist** |
| Admin-only | `APOLLO_ADVERTS_ADMIN_ONLY_META` (`constants.php:205-215`) gates both behind `manage_options` in `auth_callback` |
| Switch | `apollo_adverts_is_hostel()` (`cpt.php:350-352`) |
| Card | `marketplace/parts/card-accommodation.php` — already has a hostel CTA branch (`:73-95`) |
| Query | `parts/mk/section.php:16-57` |

### The gap, stated precisely

> **The lock is per-CTA, never per-query.**

A logged-out visitor today sees **every published accommodation** — user offers
included — with only the seller identity and the CTA withheld. What was asked is
the opposite: logged-out visitors should see **only** the hostel's fixed
inventory, in the same card and the same slot.

### Four defects found alongside it

**B0-D1 — LIVE PII LEAK. The REST route gives away everything the templates
protect.** Verified line by line, 2026-08-17:

```php
// ClassifiedsController.php:340-342 — prepare_item()
foreach ( APOLLO_ADVERTS_META_KEYS as $key => $config ) {
    $meta[ $key ] = get_post_meta( $post->ID, $key, true );   // ← EVERY key
}
…
'author'      => (int) $post->post_author,                     // :355
'author_name' => get_the_author_meta( 'display_name', $post->post_author ),  // :356
```

`APOLLO_ADVERTS_META_KEYS` (`constants.php:39-156`) contains
`_classified_contact_phone` and `_classified_contact_whatsapp`. The loop dumps
the **whole** set — there is no allow-list and no `is_user_logged_in()` check
anywhere in the method. The route is `permission_callback => '__return_true'`
(`:40`).

> An anonymous `GET /wp-json/apollo/v1/classifieds` returns **every seller's user
> ID, display name, phone number and WhatsApp**.

This is precisely what `$seller_privacy_leak_fix_2026_07_28` was created to stop.
That fix hardened five templates and left the API wide open — the classic shape
of a fix applied at the render layer instead of the data layer.

**Independent of both features and fixable today.** It is also the reason Phase B2
gates at the query and the payload, not in templates: template-level gating is
how this happened.

**B0-D2 — `single-classified.php` is unreachable.** apollo-adverts registers
**no `single_template` filter** (unlike apollo-djs, apollo-hub, apollo-loc,
apollo-events, which all do). The only `template_include` is `Plugin.php:117`,
archive-only. **All 304 lines — including the hostel "Regras da estadia" block
(`:151-210`) — are dead code that has never rendered.**

**B0-D3 — the live marketplace has no card CSS.** `.accom-*` lives in
`assets/css/marketplace/_accommodation.css`, imported by `marketplace.css`, which
`archive-classified.php:10` deliberately stopped linking. `.is-locked` and
`.btn-accom--hostel` have **zero CSS anywhere in the plugin**.

**B0-D4 — the registry is stale here.** `17-backlog.json:205-211` (TBD-017) and
`16-summary.json:324-328` both claim `archive-classified.php`,
`single-classified.php` and `create-classified.php` are missing. **All three
exist on disk.** The real defect is different: one is unreachable.

## B1 · Phases

### Phase B1 — make the hostel a first-class owner, not a flag

`_classified_hostel` is a boolean on each listing. That cannot answer "show me
*this* hostel's page", and the registry already flags the tension
(`apollo-adverts.json:2025`): accommodation exists on two surfaces with different
post types — `classified` here, `local` on /casa — and the hostel switch is
registered only on `classified`.

**Decision required.** Two viable shapes:

| Option | Shape | Trade-off |
|---|---|---|
| **B1-a** *(recommended)* | Hostel **is** a `local` post. `_classified_hostel_id` (int) on the listing replaces the boolean. | Reuses the registered `local` CPT, its address/coords/gallery meta and its single page. Resolves the two-surface divergence instead of deepening it. One migration: bool → id. |
| **B1-b** | Keep the boolean, add `_classified_hostel_slug`. | No migration; leaves the divergence and gives the hostel no page of its own. |

**B1-a to-dos**

- [ ] **B1-1** Register `_classified_hostel_id` (int) via the
      `apollo_core_register_meta` filter; keep it in `APOLLO_ADVERTS_ADMIN_ONLY_META`.
- [ ] **B1-2** `apollo_adverts_is_hostel()` becomes `_classified_hostel_id > 0`,
      keeping the boolean as a read fallback for one release.
- [ ] **B1-3** Migration script, dry-run first: bool `1` → the matching `local` id.
- [ ] **B1-4** Hostel page = the existing `local` single page **plus** an
      accommodations band, mounted with `apollo_plus_part()`. Not a new template.

### Phase B2 — gate at the QUERY, in one place

- [ ] **B2-1** `apollo_adverts_public_scope_args(): array` — the **single**
      declaration of what a logged-out visitor may see:

      ```php
      // Guests see official inventory only. Members see everything published.
      if ( is_user_logged_in() ) return array();
      return array( array(
          'key'     => '_classified_hostel_id',
          'compare' => 'EXISTS',
      ) );
      ```

- [ ] **B2-2** Apply it in **all three** consumers, or the gap reopens where it
      is missed: `parts/mk/section.php:16-57`,
      `ClassifiedsController::get_items()` (`:101-154`), `SearchController`.
- [ ] **B2-3** **Fix B0-D1.** `prepare_item()` must drop `author`, `author_name`,
      `_classified_contact_phone`, `_classified_contact_whatsapp` for anonymous
      callers. The template-level fix is not sufficient and never was.
- [ ] **B2-4** Harness assertion: no accommodation query anywhere may run without
      passing through `apollo_adverts_public_scope_args()`.

> **Why one function and not three `if` blocks.** Three copies of the same rule is
> exactly how the template fix shipped while the API stayed open. The scope is a
> value, declared once, consumed everywhere.

### Phase B3 — one card, both origins

The requirement is that a hostel room and a user offer are **the same component**.

- [ ] **B3-1** `apollo_card_register('accommodation', …)` pointing at
      `card-accommodation.php`, promoted to the single renderer.
- [ ] **B3-2** Card takes an `origin` of `official` | `member`. Same DOM, same
      classes, same grid slot; origin drives only the badge and the CTA branch
      (already three-way at `:73-95`).
- [ ] **B3-3** **Fix B0-D3.** The card ships its own styles through the contract's
      print-once ledger. Add the missing `.is-locked` and `.btn-accom--hostel`
      rules. Delete the unused `.mk-ticket*` / `.mk-ac*` block
      (`parts/mk/styles.php:42-71`).
- [ ] **B3-4** Repoint `templates/list-item.php`, `new-home/crash.php` and
      `apollo-dashboard/…/panel-feed.php` at `apollo_card_render()`. **Delete the
      duplicate `.accom-badge` / `.accom-title` / `.accom-price` declarations in
      `apollo-dashboard/…/dashboard/styles.php:800,869,917`** — a cross-plugin
      instance of the cardinal sin.
- [ ] **B3-5** **Fix B0-D2.** Register the missing `single_template` filter so
      `single-classified.php` renders, or delete it. Do not leave 304 lines of
      unreachable hostel logic in the tree.

### Phase B4 — surface + shell

- [ ] **B4-1** `apollo_surface_register('classified', …)` — needs
      `apollo_classified_render_single()` + `apollo_classified_can_view()`.
      **`can_view` must encode the guest rule**, or the fragment endpoint becomes
      the fourth way around the gate.
- [ ] **B4-2** Hostel page mounts the accommodations band via `apollo_plus_part()`.
- [ ] **B4-3** Guest CTA continues to `/acesso?redirect=` — already correct.

---

# 2 · Dead-code deletion manifest

Every entry below was verified by grepping the identifier across **all 46
plugins** and finding only its definition. Delete in a single reviewed pass, not
piecemeal.

### apollo-djs

| Target | Evidence |
|---|---|
| `templates/parts/dj-v3/` — **15 files** | Only consumer of `templates/parts/` is `single-dj-v3.php:55`, which points at `parts/dj-card/`. No include of `dj-v3` anywhere |
| `templates/single-dj.php` + `templates/parts/dj/` — **6 files** | `TemplateLoader::locate()` (`src/TemplateLoader.php:53-61`) searches only `styles/{style}/` and `styles/base/`. `includes/surface.php:25-26` already documents it as not the surface path |
| `src/Admin/Metabox.php` — **489 lines** | Instantiated only at `src/Plugin.php:37-52` behind `!class_exists('\Apollo\LuxPanels\DjPanel')`. Its v1 writes would be nulled by the v2 sanitizer anyway, so the stated fallback rationale no longer holds |
| `DJsController::can_edit()` `:548-550` | Definition only; routes use `can_edit_dj` / `is_dj_creator` |
| Caps `apollo_manage_djs`, `apollo_edit_djs`, `apollo_create_djs`, `apollo_edit_own_djs` | Granted at `apollo-core/src/Core/ActivationHandler.php:175,203,220,225`; **zero `current_user_can()` checks**. CPT is `capability_type => 'post'` — these are decorative |
| `styles/base/_legacy/single-dj.monolith.php` | Rollback copy |

### apollo-adverts

| Target | Evidence |
|---|---|
| `apollo_adverts_is_identity_locked()` `cpt.php:362-367` | Never called; the card computes the boolean inline |
| `apollo_adverts_hostel_url()` `cpt.php:378-383` | Never called; `card-accommodation.php:26-28` reads the meta directly |
| `apollo_adverts_is_ticket()` `cpt.php:285-288` | Never called |
| `apollo_adverts_get_directory_path()` `functions.php:62-64` | Never called |
| `templates/marketplace/classifieds-page.php` — 161 lines | `@package Apollo\Classifieds`; every `get_template_part()` points into `wp-content/plugins/apollo-classifieds/`, **which has no folder on disk**. Would render an empty document |
| `parts/accommodation-grid.php`, `parts/ticket-carousel.php`, `parts/page-header.php`, `parts/info-box.php`, `parts/section-header.php`, `parts/filters-row.php` | Reachable only from `classifieds-page.php` or `_legacy/archive-classified.monolith.php` |
| `templates/_legacy/archive-classified.monolith.php` | Rollback copy |
| `includes/demo-data.php` | Not in `apollo_adverts_load_includes()` (`apollo-adverts.php:92-128`); no require anywhere |
| `templates/manage.php` | No loader |
| `RelatedAds::display_related()` + `templates/parts/related-ads.php` | Hooked to `apollo/classifieds/single/after_content`, **never fired**; the target template does not exist |
| `apollo_adverts_templates_register()` / `apollo_adverts_shortcodes_register()` (`integrations.php:386-425`) | Hooked to `apollo/templates/registered` / `apollo/shortcodes/registered`, **never applied** |
| `parts/mk/styles.php:42-71` — `.mk-ticket*` / `.mk-ac*` | No template emits these classes |
| `carousel-state.js:22` | Queries `#ticketCarousel`, which exists only in the dead `ticket-carousel.php` |

### Cross-cutting

| Target | Evidence |
|---|---|
| `apollo-hub/templates/home/partials/tracks.php` | Hardcoded 5-row demo array; superseded by `new-home/tracks.php` |
| `.bak` files **inside the deploy folder** | `MetaRegistry.php.bak-2026-08-11`, `DjPanel.php.bak-2026-08-11`, `LocPanel.php.bak-2026-08-11`, `navbar.php.bak` ×2 — **RealTimeSync mirrors these to the live server** |

### Duplication to resolve, not delete

- **Two DJ context builders**: `apollo_get_dj_context()`
  (`functions.php:665-734`) and `apollo_dj_single_context()`
  (`render-single.php:119-258`). Both call `apollo_dj_get_tracks()`, both emit a
  `tracks` array into `window.APOLLO_DJ`, with **different sibling keys**. This is
  the `_dj_tracks` duplicate-metabox incident one level up. Converge on
  `apollo_dj_single_context()` — it is the surface-contract path.
- **Three accommodation queries**: `mk/section.php:49-56` (live),
  `accommodation-grid.php:14-26` and `classifieds-page.php:111-121` (both dead,
  and both hardcode `'accommodation'` with no `rent_space` alias — they would miss
  rows).

### Latent bug, one line

`apollo_adverts_config()` (`functions.php:33-45`) defines `moderate_new`;
`ClassifiedsController.php:181` and `apollo-classified-form.php:74` read
`$config['moderation']`. Undefined key → always falls through to `publish`.
**Moderation has never been enforceable.** Fix the key name before B2 relies on it.

---

# 3 · Registry updates required

Nothing ships until the SSOT matches disk.

- [ ] **R-1** `09-plugins/apollo-core.json` — `$card_contract` annotation; `track`
      CPT in `cpts`; new meta keys in `meta.post`.
- [ ] **R-2** `09-plugins/apollo-djs.json` — `track` CPT ownership, REST routes,
      the Out Now consolidation, dead-code removals.
- [ ] **R-3** `09-plugins/apollo-adverts.json` — `_classified_hostel_id`, the
      query-scope rule, card contract adoption, dead-code removals.
- [ ] **R-4** `20-meta-schemas.json` — `track` field contract; mark `_dj_tracks`
      v2 **superseded, retained for migration**.
- [ ] **R-5** `08-architecture-layers.json` — `track` under L2_content.
- [ ] **R-6** `16-summary.json` — CPT count 14 → 15, meta/REST/page counts.
      **Correct the stale divergence** claiming the three classified templates are
      missing.
- [ ] **R-7** `17-backlog.json` — close TBD-017 (stale: templates exist); open
      entries for B0-D1 (REST privacy leak) and B0-D2 (unreachable single).
- [ ] **R-8** `10-coupling.json` — new edges apollo-djs→apollo-core (card),
      apollo-adverts→apollo-core (card), apollo-adverts→apollo-loc (hostel as local).
- [ ] **R-9** `14-routing.json` — `/lancamentos`, `/lancamento/{slug}`.
- [ ] **R-10** Run `node _inventory/registry/build.js` — it fails on a chapter
      that does not parse or a plugin-count mismatch. Then `python3 verify.py`.

---

# 4 · Sequencing and risk

| # | Phase | Risk | Why |
|---|---|---|---|
| 1 | P1 card contract | **low** | Additive, dormant-safe, no consumer yet |
| 2 | Dead-code deletion | **low** | Evidence-backed; do it before building so new work isn't written against corpses |
| **0** | **B0-D1 alone — the REST PII leak** | **low effort, ship first** | Verified live: anonymous callers get every seller's user id, display name, phone and WhatsApp. Independent of both features. An allow-list in `prepare_item()` plus dropping `author`/`author_name` for guests |
| 3 | A0-D1, `moderation` key | **low, high value** | Two more real bugs, each a few lines |
| 4 | A2 track CPT | medium | New CPT; additive, nothing reads it yet |
| 5 | A3 read/write consolidation | medium | Touches `/casa`. `apollo_get_latest_dj_tracks()` keeps its name |
| 6 | B1 hostel as `local` | medium | Migration, admin-only meta |
| 7 | B2 query gating | **high — changes what the public sees** | Ship behind a flag, verify logged-out in a private window before enabling |
| 8 | A4/B3 card adoption | medium | Visual. One surface at a time, eyes on each |
| 9 | A5 authoring | medium | New write path into a HIGH-risk plugin |
| 10 | B4 surface | low | Contract does the work |
| 11 | R-* registry | low | But **mandatory** — an unbuilt registry is a broken SSOT |

**Two rules for the whole plan.** Every phase ends with a harness run before
save; `apollo-events`, `apollo-login` and `apollo-membership` are HIGH
security-risk and every edit there is security-sensitive regardless of what the
task is about.

---

# 5 · Open decisions — answer before Phase A2 / B1

1. **Track CPT slug** — `track` + `/lancamento` + `/lancamentos`?
   The archive matches the link `new-home/tracks.php:51` already emits.
2. **Hostel shape** — B1-a (hostel is a `local` post, recommended) or B1-b
   (keep the boolean)? B1-a resolves the two-surface divergence the registry
   already flags; B1-b is cheaper and leaves it.
3. **Guest scope, exactly** — hostel accommodations only, or hostel
   accommodations **plus** all resell tickets? The plan implements the first;
   the second is a one-line change to `apollo_adverts_public_scope_args()`.
4. **Existing `_dj_tracks` rows** — migrate to CPT posts, or leave as legacy read
   and require re-entry? The plan migrates.
5. **Track moderation** — do DJ-submitted tracks publish immediately or land in
   `pending` for apollo-mod? The plan follows apollo-adverts: `pending` unless the
   author has `publish_posts`.
