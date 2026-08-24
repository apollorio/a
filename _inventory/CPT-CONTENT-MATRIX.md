# 🗺️ CPT CONTENT MATRIX — full-data render map (evento · loc · dj)

**Date:** 2026-07-21 · Every information spot on each single page as if fully populated, with its storage key and its input surface. Legend: **F** = frontend form, **M** = wp-admin metabox, **R** = REST `apollo/v1`.

---

## 1 · `/evento/{slug}` — apollo-events `styles/base/single-event.php` (+ 20 `template-parts/single/*`)

| Page spot (part) | Data | Storage | Input surfaces |
|---|---|---|---|
| Hero title (hero) | post_title (+auto accent split) | `wp_posts` | F `#ev-title` · M · R `title` |
| Hero video bg (hero/video) | `_event_video_url` (YouTube id parsed) | meta | F `#ev-video` · M · R |
| Page bg tint | `_event_bg_color` (hex, validated) | meta | F `#ev-bg-color` · M · R |
| Banner/cover (hero + og) | `_event_banner` (attachment id → thumbnail) | meta+thumb | F `#ev-banner` (wp.media) · M · R + upload endpoint |
| Date facts (facts) | `_event_start_date/_end_date` via `apollo_event_parse_date` | meta | F dtp panel → hidden `#start_date…` · M · R |
| Time facts (facts) | `_event_start_time/_end_time` | meta | F · M · R |
| Loc block (venue/location-mini) | `_event_loc_id` → loc post (name, address, lat/lng map) | meta→CPT loc | F venue combobox `#ev-loc-id` (+quick-create loc via REST) · M · R |
| Lineup (lineup) | `_event_dj_ids` → dj posts (name, thumb) | meta→CPT dj | F as2 combobox (+quick-create dj) · M · R |
| Timetable (timetable) | `_event_dj_slots` [{dj_id,start,end,badge}] | meta | F slots UI · M · R |
| Sounds chips (marquee/facts) | tax `sound` (shared w/ dj) | taxonomy | F `.ev-genre` checkboxes · M · R `sounds` |
| Season | tax `event_season` | taxonomy | F `#ev-season` · M · R `seasons` |
| About (about) | post_content (the_content) | `wp_posts` | F `#ev-about` · M · R `content` |
| **Ticket widget** (ticket-widget) | `_event_ticket_url` + `_event_ticket_price` + `_event_coupon_code` + `_event_list_url` | meta | **F `#ev-tickets-url` `#ev-ticket-price` (ADDED 2026-07-21)** + `#ev-coupon-code` + **`#ev-list-url` (ADDED)** · M · R |
| Access buttons (access) | `_event_access_buttons` repeater [{kind,style,label,sub,url}] (legacy fallback: earlybird/lista_geral/lista_fem) via `apollo_event_build_access_payload` | meta | F repeater `#ev-access-buttons` · M · R |
| Lista CTA label (access) | `_event_lista_cta_label` | meta | **F `#ev-lista-cta-label` (ADDED)** · M · R |
| Ticket availability badge | `_event_ticket_status` (free/available/soldout_soon/sold_out) | meta | F `#ev-tickets` · M · R |
| Gallery (gallery) | `_event_gallery` (attachment ids) | meta | F `#ev-gallery` (wp.media multi) · M · R |
| Audio embed | `_event_audio_url` | meta | F `#ev-audio` · M · R |
| RSVP block (rsvp/rsvp-warmup) | `apollo_event_rsvp` table (going/interested + checked_in) | custom table | R participantes endpoints (runtime, not form) |
| Depoimentos (depoimentos) | apollo-comment | comments | runtime |
| Resale (resale) | classifieds linked (`_classified_*`) | CPT classified | apollo-adverts |
| Status overlay (cancelled/postponed) | `_event_status` | meta | F `#ev-status` · M · R |
| Privacy gate | `_event_privacy` | meta | F `#ev-privacy` · M · R |
| Expired state | `_event_is_gone` (auto, 30min after end) | meta | Expiration cron (no input — correct) |
| SEO head | apollo-seo `apollo/seo/head` → meta + Event JSON-LD | derived | auto |
| Coauthors (manage-only, never public byline) | via `apollo_event_save_coauthors` | meta | F coauthor picker · R `coauthors` |

**Not rendered on the single (by design), so not in the form:** `categories/types/tags` taxonomies (REST+metabox only), `ticket_btn_style`/`list_btn_style` legacy pickers (defaults `main`/`lista`; per-row styles live in the access repeater). Post status: form always saves **draft** (`post_status:'draft'`), publish happens on the dashboard (`PUT status:publish`) or wp-admin — deliberate moderation gate.

## 2 · `/local/{slug}` — apollo-loc `styles/base/single-local.php` (prefix `_local_*`)

| Page spot | Storage |
|---|---|
| Name | `_local_name` ?: post_title |
| Address block | `_local_address` `_local_city` `_local_state` `_local_postal` |
| Map | `_local_lat` `_local_lng` (Leaflet) |
| Description | `_local_description` |
| Facts | `_local_capacity` `_local_phone` `_local_price_range` |
| Link row | link_defs loop (site/social metas) |
| Image strip | `_local_image_1..N` |
| Agenda at this loc | events by `_event_loc_id` (+start_date/time, ticket_url, dj_ids) |
| Depoimentos | `_local_testimonials` |
| Hours / Amenities | `_local_hours` `_local_amenities` |

Inputs: wp-admin metabox + REST `apollo/v1/local` (quick-create from event form posts `name/address/lat/lon`). No dedicated frontend editor yet (P3 — same FrontendEditor pattern as dj is the natural fit).

## 3 · `/dj/{slug}` — apollo-djs `templates/single-dj-v3.php` (12 parts, active since APOLLO_DJ_DEFAULT_STYLE='apollo-v3')

Fully mapped in `ROUTING-FIX-EVENT-DASHBOARD.md` §v4: hero (`_dj_name/_dj_image/_dj_banner`), bios, 3 projects, sounds tax, 16 link/asset URLs (music/social/pro/assets → EPK), verified badge, agenda (events by `_event_dj_ids`), gallery `_dj_gallery` (admin-only input, P3), depoimentos, booking. Form `/editar/dj/{id}` = 1:1 with rendered fields; readonly enforced server-side.

---

## Fixes applied in this pass

1. **form-access.php** — added "Ingresso Principal" card: `#ev-tickets-url` (`ticket_url`), `#ev-ticket-price` (`ticket_price`), `#ev-list-url` (`list_url`), `#ev-lista-cta-label` (`lista_cta_label`). These four were rendered on the single + saved by REST + present in metabox + **already read by `collectPayload()`/`loadEventFromPayload()`** in create-bridge.js — the markup inputs were the only missing link. Server-side prefill from `$edit_payload` for edit mode.
2. **apollo-events-create-bridge.js** — removed 3 leftover debug beacons POSTing to `http://127.0.0.1:7755/ingest/…` on boot/venue-search/DJ-build (mixed-content + data-leak + console noise in prod).
3. **create-event.php** — dropped the now-orphaned `_debugCatalog` from the public form config.

## Stability verification

- `php -l` (host PHP): form-access.php ✓ · create-event.php ✓ (earlier files ✓ in prior passes)
- `node --check`: apollo-events-create-bridge.js ✓ · apollo-events-create-form.js ✓
- Save pipeline: form → bridge `collectPayload()` → `POST/PUT apollo/v1/eventos` (nonce `wp_rest`) → `save_event_meta()` per-key sanitizers → meta → single template escaped render. Round-trip field names verified identical at every hop.
- Live regression (after FreeFileSync window): `/evento/dismantle-2/` 200 + full render; REST collection 200; login gates intact on /dashboard `/painel` /novo-evento.
