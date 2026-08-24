# ✅ CPT COMPLIANCE FIXES — APPLIED

**Date:** 2026-07-21 · **Scope:** event, dj, location single pages now fully author-able and mockup-synced.
Every previously-flagged gap now has a complete path: **wp-admin field → sanitized save → registration/REST → live render.**

---

## EVENT (`/evento/{id}`, apollo-events)

| Fix | Files | Result |
|---|---|---|
| Removed live agent-log block (wrote `debug-e031aa.log` + echoed marker on every render) | `styles/base/single-event.php` | No disk I/O / info leak on pageview; log truncated |
| Lineup **badge** now persists end-to-end | `src/Admin/Metabox.php` (save), `assets/js/lineup-builder.js` (badge input + init + sync) | Admin types badge → saved → rendered by `lineup.php` |
| `_event_ticket_btn_style` / `_event_list_btn_style` now author-able (were REST-only) | `src/Admin/Metabox.php` (config metabox + save) | Parity with REST contract |

## DJ (`/dj/{id}`, apollo-djs)

The wp-admin metabox went from **11 → full coverage**. Added fields + sanitized save + registration:

- **Info:** long bio (`_dj_bio`), about photo (`_dj_about_photo`, distinct from hero), about video (`_dj_about_video`).
- **Links:** bandcamp, beatport, resident_advisor, facebook, twitter/X, tiktok, set_url, mix_url (added to existing website/ig/soundcloud/spotify/youtube/mixcloud).
- **Booking & EPK:** booking email (`_dj_booking`, `sanitize_email`), media kit (`_dj_media_kit_url`), rider (`_dj_rider_url`).
- **Gallery:** `_dj_gallery` (media picker, multi) — was an orphan read, now author-able + rendered.
- **Tracks:** `_dj_tracks` repeater (title / meta / url) → now **rendered in `sounds.php`** as the "SONS" card list, replacing the single-URL degradation.

Files: `includes/constants.php` (keys), `src/Registry.php` (email/url/int types), `src/Admin/Metabox.php` (3 new metaboxes + expanded renders + save), `templates/parts/dj-v3/sounds.php` (tracks render).

## LOCATION (`/loc/{id}`, apollo-loc)

| Fix | Files | Result |
|---|---|---|
| `_local_amenities` repeater (icon / name / sub) — was orphan read | `DetailsMetabox.php`, `MetaboxSaver.php`, `MetaRegistrar.php` | Matches template's `{icon,name}` render + REST array schema |
| `_local_hours` per-day grid (0=Seg…6=Dom) — was orphan read | same three files | Matches template's `$hours[$idx]` day loop + REST schema |

---

## Security posture of the new save paths
All additions reuse each plugin's existing guard pattern — **nonce + `DOING_AUTOSAVE` + `current_user_can('edit_post')` + post-type check** — with per-type sanitization: `sanitize_text_field`, `sanitize_textarea_field`, `esc_url_raw`, `sanitize_email`, `absint`, and empty-row skipping on both repeaters. Output remains escaped (`esc_html/esc_url/esc_attr`) in all touched templates.

## Verification
- Brace / paren / PHP-tag balance: **passing** on all 9 edited files (no PHP binary in sandbox; structural check used).
- Grep-confirmed every key present in its metabox + save + (registration or render).
- Badge references in builder JS: 9 (init, add, change, input, sync, render).

## One open decision (not auto-applied)
**Location capacity.** The loc mockup states capacity/lotação should *never* appear; production collects **and renders** `_local_capacity` (also shown on event cards as "X cap."). This is a genuine product-doctrine choice, so it was **left intact** rather than silently removed. To fully honor the mockup, hide `_local_capacity` from `single-local.php` (and the loc event-card) — say the word and I'll apply it.

---
*Namespace note:* the mockups' `_ap_*` annotations remain documentation-only; production keys are `_event_*/_dj_*/_local_*`. All fixes above use the real production keys, so no runtime aliasing is required.
