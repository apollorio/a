# Event ingestion — `/eventos/novo` ↔ `/eventos/url` ↔ DB

Deep audit, 2026-08-05. Updated **2026-08-06** for strict `/eventos/url` → CPT contract.

---

## 1 · The canonical contract

`EventsController::save_event_meta()` is the **single source of truth** for
param→meta on `/eventos/novo`. URL import writes the **same meta keys** directly
via `UrlImportController::import()` (then banner via `apollo_event_set_banner()`).

| Field | meta / core | Notes |
| --- | --- | --- |
| title | `post_title` | |
| about | `post_content` | |
| start / end date+time | `_event_start_*` / `_event_end_*` | overnight end derived when missing |
| banner = featured | `_event_banner` + `_thumbnail_id` | **same attachment id** |
| video | `_event_video_url` | written only when provider has one |
| ticket URL | `_event_ticket_url` | |
| coupon | `_event_coupon_code` | |
| venue | `_event_loc_id` | FK → CPT `local` (slug e.g. `dedge`) — **always required** |
| DJs | `_event_dj_ids` / `_event_dj_slots` | only when provider supplies non-empty |

---

## 2 · Faults found in the ingestion path

### F1 — payload shape mismatch — FIXED
Importer used to post `meta_input` shape that `create_event()` ignored.
`normalize_payload_shape()` fixed the create path.

### F2 — a dateless event is INVISIBLE — FIXED (guard)
Hard-error `422 apollo_import_no_date` / `apollo_import_no_title`.

### F3 — browser-side CORS fetch — FIXED for BlueTicket
UI no longer scrapes BlueTicket HTML. Uses
`POST apollo/v1/eventos/importar-url/preview` + `importar-url`.
`BlueTicketProvider` hits the CDN detail API server-side.

### F4 — banner ≠ featured — FIXED STRICT (2026-08-06)
Cover is **required**. `apollo_event_set_banner()` sideloads and forces
`_event_banner === _thumbnail_id`. Failure → `422` + delete new post.

### F5 — venue not linked — FIXED STRICT (2026-08-06)
`match_loc()` always compact-slugs (`D-Edge Rio` → `dedge`). Miss → `422`.
Scan includes `publish|pending|private`. `link_loc=false` cannot bypass.

---

## 3 · Pipeline, end to end (strict)

```
/eventos/importa  (301 from /eventos/url)
  ├─ BlueTicket + Shotgun (server-side)
  │    ├─ POST …/importar-url/preview  → CoverResolver + diagnostics
  │    └─ POST …/importar-url           → ImportPipeline
  │         ├─ ProviderRegistry → fetch
  │         ├─ GUARD: title, start_date, cover, venue
  │         ├─ GUARD: match_loc > 0  → _event_loc_id
  │         ├─ insert/update post (title / about)
  │         ├─ meta: dates, ticket, coupon, video, djs…
  │         └─ CoverSideloader(cover)  [fail → rollback new]
  └─ (legacy) HTML paste fallback in UI only
```

---

## 4 · Display SSOT (cards + single)

| Surface | cover | venue | dates | ticket | coupon | video / djs |
| --- | --- | --- | --- | --- | --- | --- |
| Portal `/eventos` | `apollo_event_get_banner` | `apollo_event_get_loc` → `venue.name` | `_event_start_*` | `_event_ticket_url` | (card: n/a) | lineup via `apollo_event_get_djs` |
| Single `/evento/…` | `apollo_event_get_banner` | `apollo_event_get_loc` | start/end meta | `apollo_event_build_access_payload` | same → `access.php` | `_event_video_url` / DJ slots |

---

## 5 · Verification

| Check | Result |
| --- | --- |
| BT API 41379 cover + venue `D-Edge Rio` → slug `dedge` | ✓ live |
| `php -l` UrlImportController + BlueTicketProvider | ✓ |
| UI wires `importPreviewPath` / `importPath` for BlueTicket | ✓ |
| Listing cards use `apollo_event_get_banner` / `apollo_event_get_loc` | ✓ |
| Single uses same helpers + access payload for coupon/ticket | ✓ |

**After deploy:** preview → import → REST `thumbnail` non-null, banner not grain,
ids equal, `loc_id` set, listing card shows cover + venue. Re-import broken #87.

---

## 6 · Remaining gaps

| Gap | Impact |
| --- | --- |
| Shotgun PHP provider | **DONE v1.7.15** — ShotgunProvider + HtmlEventParser |
| Broken pre-fix events | Re-import; no self-heal |
| `apollo/v1/loc-resolve` | Unregistered; BlueTicket uses server `match_loc` |
| BT checklist edits | Ignored on send (server re-fetch is SSOT); coupon/status from UI still applied |
