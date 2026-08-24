# Fixes Applied — Apollo Audit (2026-07-21)

Strict "minor adjust → major result" mode. Only evidence-confirmed changes. No refactors, no invented features.

## Session 002 addendum — forms (001) + single/mockup parity (002)

### 001 — closed the last zero-input gap (loc media)
`_local_image_1..5` were READ by `single-local.php:98` (hero + galeria) but had **no input surface** anywhere. Added, mirroring apollo-djs's proven wp.media pattern:
- **NEW** `apollo-loc/src/Admin/Metabox/GalleryMetabox.php` — 5-slot wp.media picker writing the exact keys the single consumes.
- `MetaboxManager.php` — registers `new GalleryMetabox()`.
- `MetaboxSaver.php::save()` — loop 1..5: absint (attachment ID) or esc_url_raw (URL), delete on empty.
- `php -l` clean on all three. Admin-only → zero risk to public render.
- Result: loc hero + gallery now populate from real uploads instead of the (already de-mocked) placeholder.

Not built (DECISION_REQUIRED, would be inventing): dj-gallery *frontend* field (admin metabox already collects it); loc `_local_testimonials` writer (storage.key `_local_testimonials|apollo-comment` implies it is plausibly comment-fed by design, not admin JSON).

### 002 — singles vs mockups: verified, no template edits needed
Mockup section inventory (extracted from the 3 mockup HTMLs) vs live templates:
- **event** (`event-single-page.html`): all slots dynamic; the only `cancel` match in the mockup is `cancelAnimationFrame` (JS) — confirms event mockup has NO status/season/privacy/sold-out visual slot, so those fields being "single render absent" is correct, not a gap.
- **dj** (`dj-single-page.html` → O artista / Em números / Tocou em·com / Out now / Kit de Imprensa): all covered by active v3 parts.
- **loc** (`loc-single-page.html` → O espaço / Estrutura / Como chegar / Quem já viveu / Próximas noites): all present in `single-local.php` (description:48, amenities:295/1188, leaflet map:323/1295, testimonials:231, agenda) rendered from live meta.

Live verification (post-35s sync): `/evento/dismantle-2` 200 full render; `/dj/magri` 200 v3 render (real image); `/local/baiuca` → `baiuca-dj-bar` 200 — rich template active (full SEO head), sparse sections hide per `empty_behavior=hide` (correct — baiuca needs data, not code).

Registry: `local.images_input` gap → **RESOLVED**; `$phase001_002` provenance added.

### Re-flag (host-side, outside plugins tree — cannot fix here)
`/dj/magri` still prints a live `Deprecated: the_block_template_skip_link` notice → `WP_DEBUG_DISPLAY` is ON in production wp-config. Turn it off so notices stop leaking into pages.

---


## Code (1 line — production mock removal)

**`apollo-loc/styles/base/single-local.php:109`** — loc single hero fallback
```diff
- $hero_image = $gallery[0] ?? 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=1400&h=900&fit=crop';
+ $hero_image = $gallery[0] ?? 'https://assets.apollo.rio.br/img/bg/grain-001.jpg';
```
Why: a production single silently served an external Unsplash stock photo when a location had no image (violates plan Phase 9 "singles MUST NOT depend on mock data" + Phase 12 "no stale fallback"). Replaced with the Apollo-canonical placeholder already used by the event single (`apollo-events/includes/functions.php:233`). This was the ONLY stock-image mock across all three singles (verified by sweep).

## Registry (`data-registry.json` — gaps reclassified with evidence, plan Phase 15)

- 4 DJ high-severity gaps **closed** (verdict ALREADY_FIXED): `dj.bio_short`, `dj.statement`, `dj.hero_image`, `dj.bio`. The v3 DJ template (active since `APOLLO_DJ_DEFAULT_STYLE=apollo-v3`) reads these from `_dj_*` meta, and the meta now flows from the `/editar/dj` form. Evidence: `parts/dj-v3/hero.php`, `single-dj-v3.php:17,28`, `about.php`.
- `dj.stats` → `INTENTIONAL_DERIVED` (computed from event history, not meta).
- `local.marquee` → `PARTIALLY_FIXED` (built from `_local_amenities` + event sounds).
- Added 2 evidence-backed gaps: `local.images_input` (HIGH) and `local.testimonials_input` (MEDIUM) — both `STILL_BROKEN` / `DECISION_REQUIRED`.
- `totals.gaps` recomputed (11) and `$phase11_verdicts` provenance added.

## NOT done (deliberately — DECISION_REQUIRED, not a "minor adjust")

- **Loc image input surface** (`_local_image_1..5`): single reads them but no metabox/frontend/REST writer exists. Building a media uploader (metabox + saver + REST + registration + reload) is a full-lifecycle feature per plan Phase 14 — flagged, not fabricated.
- **Loc testimonials input** (`_local_testimonials`): same class.
- **Loc imagery naming drift** (`_loc_gallery` vs `_local_image_N` vs `_local_gallery`): requires a data migration decision — documented in `db-matrix.json`, not auto-migrated (plan Phase 5 forbids silent mass migration).
