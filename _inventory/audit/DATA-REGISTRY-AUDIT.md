# DATA REGISTRY — mockup → database audit

**File:** `plugins/_inventory/data-registry.json` · **v1.0.0** · 2026-07-22  
**Sources:**  
- `screen/_official_layout/layout.html`  
- `screen/single cpt/event/single-event/event-single-page.html` (+ `simulated.data.js`)  
- `screen/single cpt/dj/dj-single-page.html`  
- `screen/single cpt/location/loc-single-page.html`

## Mandatory field template

Every row in `cpts.*.fields` / `surfaces.shell.fields` conforms to `$field_template` inside the JSON:

`id` · `label` · `cpt` · `mockup{file,payload,section,ui,hardcoded_in_html,sample}` · `storage{kind,key,type,owner_cpt,mockup_key_legacy}` · `payload_key` · `input{frontend,metabox,rest}` · `required_for_render` · `empty_behavior` · `notes`

> No external attachment was available at generation; the template is **embedded** in the JSON. If you have a stricter schema file, drop it in `_inventory` and re-run `_build-data-registry.js` against it.

## Totals

| Surface | Fields |
|---------|-------:|
| CPT `event` | 42 |
| CPT `dj` | 21 |
| CPT `local` | 20 |
| Shell (`layout.html`) | 4 |
| **All** | **87** |
| Open gaps | 10 |

## CPT identity (live)

| CPT | Slug | Plugin | Meta prefix | Context |
|-----|------|--------|-------------|---------|
| Evento | `event` | apollo-events | `_event_` | single parts + helpers |
| DJ | `dj` | apollo-djs | `_dj_` | `apollo_get_dj_context()` |
| Local | `local` | apollo-loc | `_local_` | LocalsController / single-local |

## Relations (cross-CPT)

1. `event._event_loc_id` → `local` → powers `venue.*` on event + agenda on loc  
2. `event._event_dj_ids` + `_event_dj_slots` → `dj` → lineup/timetable + DJ playedOn/playedWith  
3. `apollo_event_rsvp` → event + user → RSVP UI + aside **Eventos Radar**

## Highest-priority gaps

1. **DJ mock** still hardcodes hero bio, statement, hero image, about body, stats — must move into `APOLLO_DJ` / context.  
2. **LOC mock** still uses `_ap_*` labels; live is `_local_*` — treat `_ap_*` as legacy aliases only.  
3. **LOC marquee / gallery captions / bleed** need explicit live meta parity if the mock UI is law.

## Regenerate

```bash
node plugins/_inventory/_build-data-registry.js
```
