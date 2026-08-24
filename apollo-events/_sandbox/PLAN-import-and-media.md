# Plan — URL importer hardening + create-form media fix

Written 2026-08-05. Phases are ordered so each one is independently shippable
and verifiable; nothing later depends on guessing.

---

## Part A — the media bug on `/eventos/novo`

### What I verified (so we do NOT chase these)

| Checked | Result |
| --- | --- |
| `#coverUpload` exists in markup | ✔ `form-media.php:14` |
| `wireCover()` is invoked | ✔ `apollo-events-create-wire.js:761` |
| Init is `readyState`-safe (not a late-DOMContentLoaded miss) | ✔ lines 32-33 |
| File input is appended to `<body>` before `.click()` | ✔ line 195 — so it is **not** the classic detached-input block |
| `CFG.nonce` is populated | ✔ `create-event.php:246` → `APOLLO_EVENT_FORM.nonce` |
| The wire script is emitted on the page | ✔ `create/scripts.php:85` |
| The 2026-08-05 beacon→console edit | ✔ parses, structurally sound |

### The hypothesis this points to

**Both symptoms are one root cause, not two.** "Click does nothing" *and*
"upload does nothing" together are what a **single early JS throw** looks like:
the module aborts before reaching `wireCover()` at line 761, so nothing binds —
and with nothing bound, no upload can be attempted either. Two independent bugs
that happen to hit the same feature is the less likely story.

Ranked candidates:

1. **Early throw in the wire module** — anything above line 761 that references
   a node or global that is absent on this page. My shell conversion of
   `create-event.php` removed the local `shared/{topbar,overlay,panels,aside}.php`
   requires; if any wire code queries a node those emitted, it now gets `null`
   and may throw. **This is the first thing to check and it is my change.**
2. **Script 404** — `$apollo_js()` builds the src; a wrong path yields a silent
   404 and zero bindings.
3. **`upload_files` capability** — promoters may not have it. `POST /wp/v2/media`
   → 401, and `.then(r => r.ok ? r.json() : null)` swallows the body, so the UI
   says "Falha ao enviar a imagem" with no cause. Explains symptom 2 only.

### Phase A1 — Confirm, do not guess *(30 min, no code)*
Open `/eventos/novo` logged in, DevTools console + network:
- Any red error? → candidate 1, note the file+line.
- `apollo-events-create-wire.js` → 200 or 404? → candidate 2.
- Click the cover box: does a file dialog open? Does `POST /wp-json/wp/v2/media`
  appear, and with what status? → candidate 3.

**Gate:** do not write code until this returns a specific status/error. Every
fix below is cheap once the branch is known and worthless if it is guessed.

### Phase A2 — Fix the root cause *(scoped by A1)*
- If (1): guard the offending lookup, restore whatever the conversion removed.
- If (2): fix the asset path.
- If (3): either grant `upload_files` to the promoter role, or route uploads
  through an Apollo REST endpoint that sideloads server-side under an explicit
  capability — the same `media_handle_sideload()` path the importer already uses.

### Phase A3 — Make failure legible *(always, regardless of A1)*
`.then(r => r.ok ? r.json() : null)` is the reason this was invisible for so
long. Replace with: read the body on non-2xx, surface `code`/`message` in the
toast, `console.error` the full response. Also handle `413` (file larger than
`upload_max_filesize`) with a real message instead of a generic failure.

### Phase A4 — Wire the gallery + regression pass
Confirm `[data-gallery-slot]` uses the same fixed path; verify cover + 3 gallery
images survive save→reload→edit.

---

## Part B — URL importer, to production grade

### Phase B1 — Ship and verify what already exists *(blocking)*
Written but **never executed end-to-end**: `BlueTicketProvider`, `PtDate`,
`UrlImportController`, and the `normalize_payload_shape()` shim.
- `php -l` all four files (unavailable to me all session).
- Confirm `apollo/v1/eventos/importar-url{,/preview}` appear in
  `/wp-json/apollo/v1`.
- Import BlueTicket 41379 → assert post has title `INNERSOUNDS`,
  `_event_start_date=2026-08-08`, `_event_start_time=23:00`, banner attachment,
  `_event_ticket_url` carrying `?c=apollo`.
- Re-import the same URL → asserts update, not duplicate.
- **Re-import the two blank events** — they will not self-heal.

### Phase B2 — Shotgun provider
The only platform still returning `422 unsupported`. `PtDate` already parses its
`quinta 6 ago de 18:00 a 01:00` format including the midnight rollover.
Same method that worked for BlueTicket: read their bundle, find the API the SPA
calls, fetch server-side. **Do not DOM-scrape** — verify first whether their page
is SSR or client-rendered, exactly as was done for BlueTicket.

### Phase B3 — Retire the browser-side fetch
`events-by-url.html:255` still does `fetch(rawUrl, {mode:'cors'})` + a proxy
fallback. Both are dead weight now. Repoint the page at `importar-url/preview`
→ show the parsed card → `importar-url` to commit. Deletes the CORS path and the
proxy dependency outright.

### Phase B4 — Close the known defects
- `apollo/v1/loc-resolve` is referenced in the boot payload and **registered
  nowhere** — register it or change the default (recorded in the registry).
- Decide loc policy: match-only (current) vs. create-on-miss behind an explicit
  operator confirmation.

### Phase B5 — Field coverage to parity with `/eventos/novo`
The create form posts 25 params; the importer currently fills 8. Extend where
the source genuinely carries the data — `end_date`/`end_time` (Shotgun ranges),
`ticket_price`, `coupon_code`, sound/season taxonomies. **Leave the rest empty
rather than inventing defaults**; a wrong `ticket_status` is a claim about
availability the site has no basis for.

### Phase B6 — Batch + resilience
Queue N URLs, per-row status, resumable. Rate-limit provider calls. Cache
`detail` responses briefly so preview→import does not double-fetch.

---

## Part C — Test harness *(runs after B1)*

| Level | Coverage |
| --- | --- |
| **Smoke** | Route registered; `preview` on a known URL returns 200 with `title` + `start_date`. |
| **Unit** | `PtDate` fixtures — both real strings, year rollover across Dec→Jan, midnight rollover, garbage input → `''`. `split_presenter()` for each separator. `loc_slug()` for D-Edge Rio / SP. |
| **Integration** | Full import → assert every meta key. Re-import → no duplicate. Missing-date payload → `422`, **nothing written**. |
| **Functional** | Imported event appears on `/eventos` (proves the `_event_start_date` INNER-JOIN visibility trap is closed), renders in the lightbox, ticket CTA carries the coupon. |

Extend `_sandbox/build-portal-harness.mjs` — it is already the project's gate
and currently passes 22/22.

---

## Suggested order

**A1 → A3 → B1** first. A1 is 30 minutes and unblocks a broken production
feature; A3 stops the next media bug hiding for weeks; B1 makes real four files
that currently exist only on disk. Then B2 (Shotgun) as the biggest functional
win, B3 to delete the dead CORS path, then B5/B6.

Deliberately **not** planned: rewriting `app.php`'s single IIFE into cells, and
converting the remaining Path-C shell consumer. Both are real, both are logged in
the registry, neither belongs inside this thread of work.
