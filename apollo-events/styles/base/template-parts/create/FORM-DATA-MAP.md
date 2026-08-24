# novo-evento — form → database map

`/novo-evento` / `/eventos/novo` (create) and `?edit={id}` (edit) are the **same form**.
Create sends `POST /apollo/v1/eventos`, edit sends `PUT /apollo/v1/eventos/{id}`.

```
form part (PHP)  →  hidden input  →  collectPayload()  →  EventsController
                                                             ├ save_event_meta()      → post meta
                                                             ├ save_event_taxonomies() → terms
                                                             └ save_event_coauthors()  → team
```

Canonical matrix: `_sandbox/CREATE-FIELD-MATRIX.json`. Auditor: `_sandbox/audit-create-form.mjs`.

## Field map (verified 2026-08-06)

| Form input | Payload key | Meta key | Sanitizer |
|---|---|---|---|
| `#ev-title` | `title` | *post_title* | `sanitize_text_field` |
| `#ev-about` | `content` | *post_content* | `apollo_event_kses_about` |
| `#start_date` | `start_date` | `_event_start_date` | strict `Y-m-d` |
| `#end_date` | `end_date` | `_event_end_date` | strict `Y-m-d` |
| `#start_time` | `start_time` | `_event_start_time` | strict `HH:MM` |
| `#end_time` | `end_time` | `_event_end_time` | strict `HH:MM` |
| `#ev-banner` | `banner` | `_event_banner` + thumbnail | `sanitize_image_ref` |
| `#ev-gallery` | `gallery` | `_event_gallery` | `sanitize_image_ref[]` |
| `#ev-video` | `video_url` | `_event_video_url` | `esc_url_raw` |
| `#ev-audio` | `audio_url` | `_event_audio_url` | `esc_url_raw` |
| `#ev-loc-id` | `loc_id` | `_event_loc_id` | `absint` — **only venue write** |
| `#ev-dj-ids` | `dj_ids` | `_event_dj_ids` | `intval[]` |
| `#ev-dj-slots` | `dj_slots` | `_event_dj_slots` | rows without `dj_id` discarded |
| `#ev-access-buttons` | `access_buttons` | `_event_access_buttons` | repeater sanitizer |
| `#ev-coupon-code` | `coupon_code` | `_event_coupon_code` | uppercased, `[A-Z0-9_-]` |
| `#ev-tickets-url` | `ticket_url` | `_event_ticket_url` | `esc_url_raw` |
| `#ev-ticket-price` | `ticket_price` | `_event_ticket_price` | `sanitize_text_field` |
| `#ev-tickets` | `ticket_status` | `_event_ticket_status` | enum |
| `#ev-list-url` | `list_url` | `_event_list_url` | legacy hidden preserve |
| `#ev-lista-cta-label` | `lista_cta_label` | `_event_lista_cta_label` | legacy hidden preserve |
| `#ev-privacy` | `privacy` | `_event_privacy` | enum |
| `#ev-status` | `event_status` | `_event_status` | enum |
| `#ev-bg-color` | `bg_color` | `_event_bg_color` | `sanitize_hex_color` |
| `.ev-genre:checked` | `sounds` | *taxonomy* `sound` | term slugs |
| `#ev-season` | `seasons` | *taxonomy* `season` | term slugs |
| `#ev-coauthors` | `coauthors` | `_event_coauthors` | `intval[]` |

**Display-only (not persisted):** `#lat`, `#lon` (weather; filled from local CPT via `applyLoc()`). `#ev-venue-search` is UI only.

**Not on form (intentional):** `_event_local_name`, `_event_lat`, `_event_lng` (Cena / URL-import only). `tags` taxonomy (API-only). `ticket_btn_style` / `list_btn_style` (no UI — omitted from payload so saves do not overwrite).

**Legacy, intentionally not sent:** `earlybird_*`, `lista_geral_*`, `lista_fem_*` — replaced by `access_buttons`.

## What was broken, and why

| Symptom | Root cause |
|---|---|
| **500 on save** | Ecosystem hook typed arg #2 as string; fixed. |
| **Cover / photos dead** | Binding gaps; fixed in create-wire.js. |
| **Loc not selecting** | `<datalist>` yielded no id; combobox now sets `#ev-loc-id`. Datalist markup removed 2026-08-06. |
| **DJs not found** | Free-text without `dj_id`; combobox now carries CPT id. |
| **Orphan btn styles** | `collectPayload` sent hard-coded `ticket_btn_style`/`list_btn_style` with no DOM — removed 2026-08-06. |

## Files that own this

- `assets/js/apollo-events-create-wire.js` — cover, gallery, loc picker, DJ picker
- `assets/js/apollo-events-create-bridge.js` — payload assembly + REST calls
- `src/API/EventsController.php` — validation, sanitizing, persistence
- `includes/functions.php` — `apollo_event_prepare_form_payload()` (edit prefill)
- `_sandbox/audit-create-form.mjs` — static gate
