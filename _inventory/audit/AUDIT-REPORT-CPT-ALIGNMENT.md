# 🧬 APOLLO CPT SINGLE-PAGE ALIGNMENT AUDIT
### DB structure ↔ wp-admin metabox ↔ mockup contract ↔ live PHP render

**Date:** 2026-07-21 · **Scope:** 3 CPTs audited one-by-one (event, dj, location)
**Method:** read each mockup's data-binding contract, then the plugin's meta registration (DB), wp-admin metabox form, and the production single template — then reconciled the four layers.

**Route → CPT → owner plugin → mockup**

| Route | CPT (internal) | Owner | Mockup |
|---|---|---|---|
| `/evento/{id}` | `event` | apollo-events | `event/single-event/event-single-page.html` |
| `/dj/{id}` | `dj` | apollo-djs | `dj/dj-single-page.html` |
| `/loc/{id}` | `local` | apollo-loc | `location/loc-single-page.html` |

---

## 0. Macro Overview — the whole picture first

**Two rendering paradigms, one contract.** Every mockup is a *reusable skeleton* bound at runtime from a single JS object (`APOLLO_EVENT_SIMULATED`, `APOLLO_DJ`, `APOLLO_LOC`) with a strict **graceful-hide law**: any field with no data removes its element (`.apollo-hidden → display:none`). Production does the *same thing on the server* — each PHP template-part opens with `if ( empty($x) ) return;` and prints escaped HTML. The paradigms differ (client-bind vs server-echo) but both honor "never show an empty label, broken image, or dead link." This is architecturally sound; the audit's job is whether **the same data actually reaches the page** through the DB → form → render path.

**The five cross-cutting findings (apply to all three CPTs):**

1. **Namespace drift — mockup `_ap_*` vs production `_event_*/_dj_*/_local_*`.** Every mockup annotates its fields with intended meta keys in an `_ap_*` namespace (`_ap_sc`, `_ap_geo`, `_ap_venue_name`, `_ap_lineup`, `_ap_amenities`…). **None of these keys exist in production.** The real keys are `_dj_soundcloud`, `_local_lat`/`_local_lng`, `_event_dj_ids`, etc. This is documentation aspiration, not a functional break — but anyone building a REST payload from the mockup annotations will target keys that return nothing.

2. **MASTER_REGISTRY rule violated by all three** (consistent with the universe audit's V-21). `apollo-events/src/Registry.php`, `apollo-djs/src/Registry.php`, and `apollo-loc/src/CPT/MetaRegistrar.php` each call `register_post_meta()` directly instead of routing through apollo-core. **Event meta is double-registered**: `apollo-core/config/meta.php` (partial set, includes `_event_budget`, missing earlybird/lista/ticket_status/btn_style) *and* `apollo-events/src/Registry.php` (full 31-key set). REST exposure and sanitization can diverge depending on which registration wins.

3. **Metabox coverage is wildly uneven.** Event = comprehensive (≈30 fields, near-total mockup coverage). Location = strong on core venue data. **DJ wp-admin metabox = severely under-built (11 fields)** — it leans on a separate frontend self-edit form for most data.

4. **Orphan reads — templates read meta no form writes.** Event: `_event_ticket_btn_style`, `_event_list_btn_style`. DJ: `_dj_gallery`. Location: `_local_amenities`, `_local_hours`. On admin-only editing these render empty forever.

5. **A live debug artifact in the event render path.** `single-event.php` still contains the `e031aa` agent-log block that `file_put_contents` to `debug-e031aa.log` **on every page view** and echoes a `<!-- apollo-events-single:1.2.7 -->` marker into production HTML. This is the same session flagged as V-02 in the universe audit — but here it is in the *live* template, not a stray script.

**Bottom line per CPT:**

| CPT | Core-data coverage | Mockup fidelity | Verdict |
|---|---|---|---|
| **event** | Excellent (metabox ≈ mockup) | Very high — 11 parts mirror the skeleton with payload-builder SSOTs | **Healthy** (bar the live debug log + badge bug) |
| **dj** | Partial via wp-admin; full only via frontend form | High *structurally*, but admin cannot fill it | **Warning** — data-entry gap |
| **location** | Strong on address/geo/contact | High, minus amenities/hours orphans | **Warning** — orphan reads + capacity philosophy conflict |

---

## 1. EVENT — `/evento/{id}` (CPT `event`, apollo-events)

### 1.1 Four-layer field matrix

Legend: ✅ present · ⚠️ derived/partial · ❌ missing · 🔒 live-only (RSVP/chat tables)

| Mockup spot (`APOLLO_EVENT_SIMULATED`) | DB meta (registered) | wp-admin metabox | Live PHP part | Status |
|---|---|---|---|---|
| `share.title` / `hero.titleLines` | post_title | core WP title | hero.php (split accent) | ✅ |
| `hero.image` | `_event_banner` | ✅ Media picker | hero.php | ✅ |
| `hero.youtubeId` | `_event_video_url` | ✅ Vídeo URL | derived regex → id | ✅ |
| `hero.kicker` / `hero.meta[]` | — | — | derived (date/venue) | ⚠️ derived |
| `facts[]` | `_event_start_date/time`, `_event_ticket_price`, `_event_status` | ✅ | facts.php | ✅ |
| `rsvp.options.*.avatars` / `me` | `apollo_event_rsvp` table | n/a | rsvp.php | 🔒 live |
| `rsvp.title/lede/bullets` | — | — | hardcoded strings | ⚠️ hardcoded |
| `chat.messages[]` | chat integration | n/a | chat.php | 🔒 live |
| `genres[]` | `sound` taxonomy | ✅ checkboxes | marquee.php | ✅ |
| `lineup.djs[]` name/photo/slug/audio | `_event_dj_ids` + dj CPT join | ✅ lineup builder | lineup.php | ✅ |
| `lineup.djs[].slot{start,end}` | `_event_dj_slots` | ✅ builder | lineup.php | ✅ |
| `lineup.djs[].badge` | `_event_dj_slots[].badge` | ⚠️ collected in JS | reads `$slot['badge']` | ❌ **stripped on save** |
| `lineup.djs[].featured` | — | — | derived (index 0 = Headliner) | ⚠️ derived |
| `timetable.rows[]` | `_event_dj_slots` + dj join | ✅ | timetable.php | ✅ |
| `gallery[]` | `_event_gallery` (max 3) | ✅ CSV IDs | gallery.php | ✅ |
| `about.paragraphsHTML` | post_content | core editor | about.php | ✅ |
| `spotify` | `_event_audio_url` | ✅ | about/embed | ✅ |
| `venue.*` (name/addr/geo/photos) | `_event_loc_id` → **loc CPT join** | ✅ loc select | venue.php | ✅ cross-CPT |
| `access.tickets[]` | `_event_ticket_url/price/status`, earlybird | ✅ | access.php (payload SSOT) | ✅ |
| `access.coupon` | `_event_coupon_code` | ✅ | access.php | ✅ |
| `access.listas[]` / `listaCta` | `_event_lista_*`, `_event_list_url` | ✅ | access.php | ✅ |
| `footer.*` | derived (banner/title) | — | footer-visual.php | ⚠️ derived |
| — | `_event_ticket_btn_style` | ❌ no field | read by orchestrator | ❌ **orphan read** |
| — | `_event_list_btn_style` | ❌ no field | read by orchestrator | ❌ **orphan read** |
| — | `_event_budget` (core reg only) | ❌ no field | not on single page | ⚠️ gestor-owned |

### 1.2 Micro findings

- **[CRITICAL – production hygiene] Live agent-log in `single-event.php:73–95`.** Writes `debug-e031aa.log` via `file_put_contents(... FILE_APPEND | LOCK_EX)` **on every event render**, and `echo`es `<!-- apollo-events-single:1.2.7 parse=… -->` into the HTML. Disk I/O + lock contention per pageview, information disclosure, and a web-readable log file. **Delete lines 73–95.**
- **[HIGH – data loss] Lineup badge round-trip bug.** `Metabox::render_local_lineup()` builds `badge` into the slots JSON (line ~160) and `lineup.php` reads `$slot['badge']`, but `Metabox::save()` (lines 526–532) rebuilds each slot with **only** `dj_id/start_time/end_time` — **badge is silently dropped**. Any admin-entered badge (e.g. "B2B", "LIVE") never persists; only the auto "Headliner" fallback for index 0 shows. Fix: include `badge` in the save() map.
- **[MEDIUM] Orphan reads** `_event_ticket_btn_style` / `_event_list_btn_style` — read by the orchestrator (defaults `main`/`lista`) but no metabox field exists, so button styling is not authorable.
- **[LOW] Venue map is more lenient than the mockup.** `venue.php` falls back to Rio-center coords (`-22.9068,-43.1729`) when the loc has no lat/lng, so the map always renders; the mockup hides the map when coords are absent.
- **[LOW] Double registration** — `_event_budget` exists in `apollo-core/config/meta.php` but not in the metabox (written by apollo-gestor), while earlybird/lista/ticket_status keys exist only in `apollo-events/Registry.php`. Consolidate to one registrar.

### 1.3 Security of the data path — event
`Metabox::save()` is **solid**: nonce (`wp_verify_nonce`) ✅, `DOING_AUTOSAVE` guard ✅, `current_user_can('edit_post', $id)` ✅, post-type check ✅, per-type sanitization (`sanitize_text_field`, `esc_url_raw`, `absint`, JSON-decode + `absint` map for lineup) ✅. Templates escape consistently (`esc_html/esc_url/esc_attr`). The only render-path defect is the debug log above.

---

## 2. DJ — `/dj/{id}` (CPT `dj`, apollo-djs)

### 2.1 Four-layer field matrix

| Mockup spot (`APOLLO_DJ`, annotated `_ap_*`) | Real DB meta | wp-admin metabox | Frontend self-edit | v3 template | Status |
|---|---|---|---|---|---|
| `name` | post_title | ✅ title | ✅ | hero/footer | ✅ |
| `soundcloud` (`_ap_sc`) | `_dj_soundcloud` | ✅ | ✅ | ✅ | ✅ |
| `spotify` (`_ap_sp`) | `_dj_spotify` | ✅ | ✅ | ✅ | ✅ |
| `bandcamp` (`_ap_bc`) | `_dj_bandcamp` | ❌ **no field** | ✅ | ✅ | ⚠️ admin-blind |
| `bookingEmail` (`_ap_booking_email`) | `_dj_booking` | ❌ **no field** | ✅ | booking.php (form+nonce) | ⚠️ admin-blind |
| `mediaKitUrl` (`_ap_kit_drive_url`) | `_dj_media_kit_url` | ❌ **no field** | ✅ | ✅ | ⚠️ admin-blind |
| `videoUrl` (`_ap_about_video`) | `_dj_about_video`* | ❌ **no field** | ⚠️ | epk/bio | ❌ weak |
| `aboutPhoto` (`_ap_about_photo`, ≠ hero) | `_dj_image`/`_dj_banner` only | ⚠️ hero+banner only | ⚠️ | bio.php | ⚠️ no distinct about-photo |
| `genres[]` (`_ap_genres`) | `sound` taxonomy / `_dj_sounds` | ❌ not in this metabox | ✅ | marquee/sounds | ⚠️ admin-blind |
| `tracks[]` (`_ap_tracks` repeater) | `_dj_set_url` + `_dj_mix_url` (singular) | ❌ | ⚠️ single URLs | sounds.php + SC shelf | ❌ **repeater → 2 links** |
| `playedOn[]` | reverse `WP_Query(event)` via `_event_dj_ids` | n/a (derived) | n/a | agenda.php | ✅ derived |
| `playedWith[]` | derived co-lineup | n/a | n/a | agenda/roster | ✅ derived |
| about paragraph | `_dj_bio` (long) | ❌ (only `_dj_bio_short`) | ✅ | bio.php | ⚠️ admin gets short only |
| socials (ig/yt/mixcloud) | `_dj_instagram/_dj_youtube/_dj_mixcloud` | ✅ | ✅ | footer/dock | ✅ |
| extra socials (fb/tw/tiktok/beatport/RA) | `_dj_facebook/_dj_twitter/_dj_tiktok/_dj_beatport/_dj_resident_advisor` | ❌ | ✅ | ✅ | ⚠️ admin-blind |
| gallery | `_dj_gallery` | ❌ | ❌ | gallery.php reads it | ❌ **orphan read** |
| verified badge | `_dj_verified` | ✅ | ✅ | ✅ | ✅ |

\* `_dj_about_video` implied by v3/epk but not in the registered 19-key set — treat as unregistered.

### 2.2 Micro findings

- **[HIGH – data-entry gap] The wp-admin DJ metabox exposes only 11 keys** (`_dj_bio_short`, `_dj_image`, `_dj_banner`, `_dj_website`, `_dj_instagram`, `_dj_soundcloud`, `_dj_spotify`, `_dj_youtube`, `_dj_mixcloud`, `_dj_user_id`, `_dj_verified`). The v3 template + mockup need **bandcamp, booking email, media kit, long bio, set/mix URL, sounds/genres, facebook/twitter/tiktok/beatport/RA, gallery**. An admin editing a DJ in wp-admin **cannot populate most of the page** — those sections stay empty unless the DJ uses the *frontend* self-edit form (`includes/frontend-fields.php`, which covers ≈25 keys). Data *can* be injected, just not from wp-admin. This is the headline compliance gap for DJ.
- **[MEDIUM] `tracks[]` repeater degrades.** The mockup's 4-item "SONS" list (per-track title/meta/url) has no `_dj_tracks` repeater in production — only single `_dj_set_url`/`_dj_mix_url` plus an on-demand SoundCloud shelf. The rich track list cannot be authored 1:1.
- **[MEDIUM] `_dj_gallery` orphan read** — `templates/parts/dj-v3/gallery.php` reads it, but it is neither registered nor editable in either form.
- **[LOW] No distinct "about photo".** Mockup requires `aboutPhoto` explicitly different from the hero image; production only has `_dj_image` (hero) + `_dj_banner`, so the about figure reuses hero-tier media.
- **[POSITIVE] Booking is richer than the mockup.** The mockup uses a `mailto:`; production `booking.php` ships a real booking-request form with `_dj_booking_nonce`.
- **[PRIVACY note] `_dj_user_id` is registered meta.** If `show_in_rest` is true (DJsController exposes 5 public `__return_true` routes per the universe audit), the DJ→WP-user mapping is publicly readable. Confirm `_dj_user_id` and `_dj_booking` are `show_in_rest ⇒ false`.

### 2.3 Security of the data path — dj
`Metabox::save()` is **solid but narrow**: nonce ✅, autosave ✅, `current_user_can('edit_post')` ✅, post-type ✅, `sanitize_textarea_field`/`sanitize_text_field`/`esc_url_raw`/`absint` ✅. The risk here is *coverage*, not injection — plus the REST-exposure question on `_dj_user_id`.

---

## 3. LOCATION — `/loc/{id}` (CPT `local`, apollo-loc)

### 3.1 Four-layer field matrix

| Mockup spot (`APOLLO_LOC`, annotated `_ap_*`) | Real DB meta | wp-admin metabox | Live template | Status |
|---|---|---|---|---|
| `name` (`_ap_venue_name`) | `_local_name` / post_title | ✅ | ✅ | ✅ |
| `addr` (`_ap_address`) | `_local_address` (+city/state/postal/country) | ✅ Address metabox | ✅ | ✅ |
| `lat`/`lng` (`_ap_geo` pair) | `_local_lat` + `_local_lng` (**split**) | ✅ | ✅ map | ✅ (split, functional) |
| contact (phone/whatsapp/site/ig/fb) | `_local_phone/_local_whatsapp/_local_website/_local_instagram/_local_facebook` | ✅ Contact metabox | ✅ | ✅ |
| `eventsHosted` (COUNT) | derived count | n/a | `_local_count_upcoming_events` helper | ✅ derived |
| `events[]` | reverse `WP_Query(event)` via `_event_loc_id` | n/a | agenda | ✅ derived |
| `gallery[]` (`_ap_gallery`) | attached media via `LocalSchema` (≤5) | ⚠️ media library | loc-gallery.php / loc-hero.php | ✅ (schema-sourced) |
| `amenities[]` (`_ap_amenities`) | `_local_amenities` | ❌ **no field** | reads `_local_amenities` | ❌ **orphan read** |
| `marquee[]` (`_ap_marquee_tags`) | — (types/areas taxonomy) | ⚠️ taxonomy | derived | ⚠️ no repeater |
| `depoimentos[]` | apollo-comment CPT via `_local_testimonials` | n/a | testimonials part | ✅ cross-plugin |
| — | `_local_hours` | ❌ no field | read by single-local.php | ❌ **orphan read** |
| — | `_local_capacity` + `_local_price_range` | ✅ Details metabox | **rendered** | ⚠️ **philosophy conflict** |

### 3.2 Micro findings

- **[MEDIUM] Two orphan reads.** `single-local.php` reads `_local_amenities` and `_local_hours`, but **neither has a metabox field** (the registered set has no `_local_amenities`/`_local_hours`). The amenities grid and hours block render empty unless populated via REST/import. The mockup's amenities section (6 icon+text items) has no authoring path.
- **[MEDIUM – design conflict] Capacity is collected and rendered, but the mockup deliberately omits it.** The loc mockup states explicitly: *"capacidade/lotação NUNCA aparece aqui — não é um dado que a Apollo verifica ou guarda."* Production has `_local_capacity` + `_local_price_range` fields in the Details metabox **and** `single-local.php` renders capacity. Production over-collects and over-displays relative to the intended design philosophy. Decide: honor the mockup (drop capacity from render) or update the design doctrine.
- **[LOW] No marquee repeater** — the mockup's `_ap_marquee_tags` becomes derived from `_local_types`/`_local_areas` taxonomies; acceptable but not 1:1.
- **[POSITIVE] Best core-data alignment of the three.** Address/geo/contact are fully authored in modular metaboxes (Address/Contact/Details) with a dedicated `MetaboxSaver`, and the template consumes them cleanly. Gallery works via `LocalSchema` (attached media, ≤5).

### 3.3 Security of the data path — location
Modular metaboxes (`AddressMetabox`/`ContactMetabox`/`DetailsMetabox`) with a central `MetaboxSaver` — consistent nonce + capability + sanitization pattern (matches event/dj). `LocalSchema` bounds gallery to 5 images. No injection issues observed in the read paths; escaping is consistent in the template parts.

---

## 4. Consolidated Remediation Plan

### Priority 1 — Production hygiene (immediate)
1. **Remove the live agent-log block** in `apollo-events/styles/base/single-event.php:73–95` (the `file_put_contents('debug-e031aa.log')` + echoed version marker). Delete the `debug-e031aa.log` file. (Ties to universe-audit V-02.)

### Priority 2 — Data-entry compliance (form ↔ mockup)
2. **Build out the DJ wp-admin metabox** (or make the frontend-form the documented single source and say so): add fields for `_dj_bandcamp`, `_dj_booking`, `_dj_media_kit_url`, `_dj_bio` (long), `_dj_set_url`/`_dj_mix_url`, sounds/genres, `_dj_facebook/_dj_twitter/_dj_tiktok/_dj_beatport/_dj_resident_advisor`, and a distinct about-photo.
3. **Add a DJ `tracks[]` repeater** (`_dj_tracks`: title/meta/url) to match the mockup "SONS" list, or formally accept single-URL degradation.
4. **Add location `_local_amenities` (icon+text repeater) and `_local_hours` fields**, or stop reading them in `single-local.php`.
5. **Fix the event lineup badge round-trip**: include `badge` in `Metabox::save()`'s slot map so admin-entered badges persist.

### Priority 3 — Registry & namespace hygiene
6. **Consolidate meta registration into apollo-core** for all three CPTs (remove the double registration of event meta; move `apollo-djs`/`apollo-loc` `register_post_meta` behind apollo-core MASTER_REGISTRY).
7. **Reconcile the `_ap_*` mockup annotations with the real `_event_*/_dj_*/_local_*` keys** — either rename in a data-contract doc or add REST aliases, so payload authors target real keys.
8. **Remove orphan reads / add fields**: event `_event_ticket_btn_style`/`_event_list_btn_style`; dj `_dj_gallery`.

### Priority 4 — Design-doctrine decisions
9. **Resolve the location capacity conflict** — either drop `_local_capacity`/`_local_price_range` from the render (honor the mockup) or amend the philosophy doc to allow it.
10. **Align lenient fallbacks with the mockup contract** (event venue map should hide when coords are absent, matching the graceful-hide law) and confirm `_dj_user_id`/`_dj_booking` are not `show_in_rest`.

---

*Companion machine-readable summary: `cpt-alignment-summary.json`.*
