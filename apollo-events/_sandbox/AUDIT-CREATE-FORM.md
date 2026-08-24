# `/eventos/novo` create-form audit — sign-off

**Date:** 2026-08-06  
**Scope:** Regular create/edit form (`/eventos/novo` = `/novo-evento`) → event CPT.  
**Out of scope:** `/eventos/url` `loc-resolve` known_defect (unchanged).

## Results

| Gate | Result |
|------|--------|
| `node _sandbox/audit-create-form.mjs` | **11/11 PASS** |
| `node _sandbox/build-portal-harness.mjs` | **22/22 PASS** |
| `php -l` EventsController / create-event / FrontendForm | **PASS** |
| Registry `known_defect` for novo | **None added** — no new novo defects |

## Fixes applied

1. **Removed stale `<datalist>`** from `form-venue.php` — combobox in `create-wire.js` is the only loc picker; single write path `#ev-loc-id` → `loc_id` → `_event_loc_id`.
2. **Stopped orphan payload** — `ticket_btn_style` / `list_btn_style` no longer sent from `collectPayload()` (no DOM; was overwriting meta with hard-coded defaults).
3. **Synced `FORM-DATA-MAP.md`** to live element ids (`#start_date`, `#ev-tickets`, etc.).
4. **Added** `_sandbox/CREATE-FIELD-MATRIX.json` + `_sandbox/audit-create-form.mjs` + `_sandbox/smoke-create-event.sh`.

## Loc storage (admin column `event_loc`)

| Layer | Key |
|-------|-----|
| Form | `#ev-loc-id` `name="loc_id"` |
| REST payload | `loc_id` (always sent, including `0` to clear) |
| Post meta | `_event_loc_id` (int FK → CPT `local`) |
| Admin column | `event_loc` → `apollo_event_get_loc()` → local `post_title` |

**Not stored on the event:** address / lat / lng (those live on the `local` CPT as `_local_*`).

### Bugs fixed 2026-08-06 (loc follow-up)

1. `collectPayload` sometimes omitted `loc_id` → meta never written; now always sends + resolves typed name to catalog id.
2. `apollo_event_get_loc()` required `publish` → pending quick-add locals showed **—** in `event_loc` column despite valid `_event_loc_id`.
3. Removed `oninput`/`onblur` venue handlers that fought the combobox.
4. `applyVenue()` now writes `#ev-loc-id` when `v.id` is present.


## Integration smoke (LocalWP)

Script: `_sandbox/smoke-create-event.sh`  

```bash
# From LocalWP Site Shell, with WP-CLI:
export WP_PATH=/path/to/app/public
bash wp-content/plugins/apollo-events/_sandbox/smoke-create-event.sh
```

Asserts `_event_start_date` / `_event_start_time`, optional `_event_loc_id`, and **absence** of `_event_local_name` / `_event_lat` / `_event_lng`.

## Re-run

```bash
cd plugins/apollo-events
node _sandbox/audit-create-form.mjs
node _sandbox/build-portal-harness.mjs
```
