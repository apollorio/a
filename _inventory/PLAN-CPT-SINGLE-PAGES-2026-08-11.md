# Plan — `dj` and `local` single pages, mockup-exact

**Date:** 2026-08-11
**Goal:** `/dj/{slug}` and `/local/{slug}` render byte-for-byte what the mockups
render, with every printed value coming from the database.
**Current coverage:** `dj` **16 %** · `local` **22 %** (form inputs ÷ registered keys).
**Blocking rule:** no template is written until the value it prints has a writer.

---

## 0 · Definition of done

A phase is done when its gate passes, not when the code exists.

| # | Gate |
| --- | --- |
| G1 | Every meta key has **exactly one** registrar. `grep -rn "register_post_meta(" apollo-*` returns only `apollo-core`. |
| G2 | Every registered key has **exactly one** writer, and it is schema-driven. |
| G3 | `apollo_get_dj_context()` / `apollo_get_loc_context()` return every key the mockup prints, with no `''` for a field that has data. |
| G4 | Harness: no selector has two owners, no geometry `var()` without a fallback, every id the JS queries exists in the printed markup. |
| G5 | Side-by-side diff of the mockup and the rendered page shows only real data differences. |

**Non-goals.** No redesign. No new sections. The mockups are the spec; where they
are wrong (§1) the fix is named and scoped, not improvised.

---

## 1 · Phase 0 — decisions only you can make

These block downstream phases. Each needs one word from you.

| ID | Decision | Why it blocks |
| --- | --- | --- |
| D1 | **VIO-01** — the `4.1k Seguidores` pill. Delete, or replace with "Anos em atividade" (derived from first played event)? | 01-philosophy forbids the concept. The hero has three pills; the layout assumes three. |
| D2 | **Metric 4, "Amanheceres"** — make `_event_end_time` required and count events ending after 05:00, or cut the metric to three? | Changes the `event` CPT contract, so it lands in Phase 1 or not at all. |
| D3 | **`_local_testimonials`** — route depoimentos through `apollo-comment` (registered vocabulary) and retire the meta, or register a writer? | The Depoimentos section cannot be built either way until this is answered. |
| D4 | **`local` metabox owner** — `apollo-lux-panels/LocPanel` or `apollo-loc/GalleryMetabox`? | Two systems on one edit screen. This is the shape of the `_dj_tracks` duplicate-editor incident. |
| D5 | **`_dj_gallery` + `_dj_original_project_1..3`** — build the two missing DJ sections, or retire four keys? | They have metabox inputs and no section. The mockup has neither. |

VIO-02/03/04 need no decision — they are copy and icon fixes, already specified in
`registry/21-mockup-field-contract.json`.

---

## 2 · Phase 1 — data layer integrity

**Owner:** apollo-core, apollo-loc · **Gate:** G1

- [ ] **1.1** Convert `apollo-loc/src/CPT/MetaRegistrar.php` to contribute via the
      `apollo_core_register_post_meta` filter. `MetaRegistry::apply_external_definitions()`
      already exists for this. Removes 21 double registrations where the later hook
      silently wins the `sanitize_callback`.
- [ ] **1.2** Register `_local_amenities` and `_local_hours` in `MetaRegistry`.
      Both are already written by LocPanel; neither is known to core.
- [x] **1.3** ~~Delete `apollo-core/config/meta.php`.~~ **CORRECTED 2026-08-11 —
      do NOT delete.** It is loaded by `src/Config/ApolloMeta.php` at three call
      sites (`for_cpt()`, `user_keys()`, `definition()`). It is a *lookup table*,
      not a registrar — but it is reachable, and deleting it would fatal any caller.
      Done instead: stamped with a header marking it non-authoritative and naming
      `MetaRegistry` as the registrar. **Follow-up, own commit:** point `ApolloMeta`
      at `MetaRegistry::get_post_meta_definitions()` and retire the file. The two
      sets are already out of sync — 11 `dj` / 13 `local` here vs 38 / 27 there.
- [ ] **1.4** (if **D2** = keep) register `_event_end_time` as required on `event`.
- [ ] **1.5** Bump `apollo-core` **and** its version constant together. `19-ssot-audit`
      already records 6.2.3 with uncommitted contract changes; WordPress reads the
      docblock, the constant drives cache-busting, and a mismatch means cache-busting lies.

**Verify:** `grep -rn "register_post_meta(" apollo-* | grep -v apollo-core` → empty.

---

## 3 · Phase 2 — input surfaces, schema-driven

**Owner:** apollo-lux-panels, apollo-djs, apollo-loc · **Gate:** G2

This is where the 16 %/22 % is actually closed. The forms are hand-listed today,
which is *how* they drifted — nobody notices a missing input.

- [ ] **2.1** Add `MetaRegistry::get_cpt_meta( string $cpt ): array` to the public
      surface (the method exists at line 1906 — confirm it is callable and returns
      labels/types/enums, extend if not).
- [ ] **2.2** **Rebuild `[apollo_add_dj]` off the schema.** Iterate
      `get_cpt_meta('dj')`, map `type` → field renderer, honour `enum`, skip keys
      flagged `admin_only`. 6 inputs → 38.
- [ ] **2.3** Same for `[apollo_add_loc]`. 6 → 27.
- [ ] **2.4** Wire `apollo-loc/src/API/Endpoints/CreateEndpoint.php` — it currently
      writes **zero** `_local_*` meta.
- [ ] **2.5** Add the 9 new panel fields. Every type already exists in `Panel.php`:

      _dj_home_city        text          _local_tagline        text
      _dj_booking_status   select        _local_founded_year   number
      _dj_media_kit_stats  repeater_card _local_rooms          repeater
      _dj_eyebrow          text
      _dj_name_lines       repeater
      _dj_footer_image     media_single

- [ ] **2.6** Resolve **D4**; delete the losing metabox.
- [ ] **2.7** Taxonomy inputs: `sound` on the DJ form, `local_type` + `local_area`
      on the loc form. `Panel.php` has a `taxonomy` field type; the frontend forms
      have none at all.

**Verify:** a script asserting `registered_keys − form_inputs − admin_only == ∅`
for both CPTs. Add it to the harness so it cannot regress.

---

## 4 · Phase 3 — read layer

**Owner:** apollo-djs, apollo-loc · **Gate:** G3

The mockups print 21 `dj` fields and 20 `local` fields. **6 of the 41 are derived**
and have no storage — they need query builders, and they are the slow ones.

- [ ] **3.1** `apollo_get_dj_context( $id )` — extend to the full 21. Missing today:
      `playedOn`, `playedWith`, `stats`, and the 6 new keys.
- [ ] **3.2** `playedOn` — `WP_Query` on `event` where `_event_dj_ids` contains the
      DJ, past dates, newest first. **Meta `LIKE` on a serialised array does not
      scale**; decide now whether this needs a relation table or an indexed
      `meta_key` per DJ. Cache per `dj_id` with `wp_cache_set(..., 'apollo', 320)`
      following the `data.php` pattern in `06-cdn`.
- [ ] **3.3** `playedWith` — co-DJs from shared line-ups. Derived from 3.2's result
      set; must not issue N queries per event.
- [ ] **3.4** `stats` — `apollo_dj_compute_stats()`. Events, distinct venues,
      distinct cities. Metric 4 per **D2**.
- [ ] **3.5** `apollo_get_loc_context( $id )` — the full 20. `events` (upcoming, 8),
      `eventsHosted` (COUNT), `gallery` (all five slots), `marquee`, `amenities`,
      `depoimentos` per **D3**.
- [ ] **3.6** Fallback chain per field, explicit and documented: `_dj_eyebrow` →
      derivation → hide. `_dj_footer_image` → newest playedOn cover → hide. Never
      ship the Unsplash placeholder.
- [ ] **3.7** `apollo_time_ago()` everywhere a date is relative. Never
      `human_time_diff()` — `15-conventions` forbids it and it breaks
      `.time-ago`/`.when-ago` CSS targeting.

**Verify:** dump both contexts for a seeded post; assert no key is `''` where the
DB has a value, and that every derived key is present.

---

## 5 · Phase 4 — mockup → cells

**Owner:** apollo-djs, apollo-loc · **Gate:** G4

Reuse the pattern already proven on `/casa`: a manifest, one file per concern,
each declaring what it owns. Not a new invention — `cells/_manifest.php` and the
`motion` / `lightbox-event` cells are the reference implementation.

**`dj` — 11 cells** (mockup blocks: `hero · stmt · marquee · playedOn · numbers ·
playedWith · sound · kitSection · about · footer · dock`)

**`local` — 11 cells** (`hero · marquee · stmt · facts · agenda · galeria ·
estrutura · comoChegar · depoimentos · footer · dock`)

- [ ] **4.1** `cells/_manifest.php` per plugin, same contract: `id · file · owns ·
      needs · layer · guest`.
- [ ] **4.2** One `motion` cell per CPT, or promote the `/casa` one to a shared
      part. **Note:** `Apollo.whenLenisReady()` does not exist in core.js v1.0.0 —
      bind `window.lenis` directly, or listen once for `apollo:lenis-ready`.
      `new-home.js:605` has the same phantom call.
- [ ] **4.3** Each cell owns one selector prefix. `.cev-*` proved the pattern.
- [ ] **4.4** Zero `:root` declarations. Tokens come from core.js — verified:
      `--rgb-theme/-diff`, `--white-1..20`, `--gray-1..20`, `--black-1..10`,
      `--fs-h1..h6`, `--fs-p1..p6`, `--s-1..7`, `--r/-sm/-xs/-lg/-pill`,
      `--ease/-smooth/-snappy`, `--accent` (209,134,10), `--safe-top/-bottom`.
- [ ] **4.5** Carry the **LUXE PASS** across: no borders, no shadows. Depth is
      plane (`--surface` → `--surface-hover`) and air (`--s-6`/`--s-7` rhythm).
- [ ] **4.6** Every geometry `var()` carries a fallback. Non-negotiable — an
      unresolved token invalidates the declaration at computed-value time and the
      property reverts to its *initial* value, not a sane one.
- [ ] **4.7** Apply VIO-02/03/04 fixes while converting, not after.

---

## 6 · Phase 5 — `/dj/{slug}`

**Gate:** G5 · **Blocked by:** Phases 1–4

- [ ] **5.1** Route is registered (`apollo-djs` pages: `dj/{slug}` →
      `styles/base/single-dj.php`). Confirm it survives `mural-router` — `/casa`
      taught us a redirect can silently own a route.
- [ ] **5.2** **Blank Canvas Apollo** (bare, no chrome) per `18-canvas-shell` —
      `single-dj` is explicitly listed. Not Apollo+.
- [ ] **5.3** Assemble cells through the manifest.
- [ ] **5.4** Out-now lightbox: reuse the `.cev-*` cell shape; `_dj_tracks` reads
      v2 with back-compat aliases (`$tracks_read_path_migration` — gap closed).
- [ ] **5.5** SEO: `apollo-seo` metabox is already registered for the CPT; confirm
      OG tags come from real fields, not the mockup's hardcoded strings.
- [ ] **5.6** Bump `apollo-djs` + constant.

### Phase 5b — verify the rendered DJ page

- [ ] Harness green (G4).
- [ ] Every one of the 21 fields renders from the DB on a seeded DJ.
- [ ] Empty-state pass: a DJ with only `title` set must render a coherent page,
      not a skeleton of empty sections.
- [ ] x-axis clean at 320 / 360 / 390 / 768 / 800 / 1440.
- [ ] Reduced-motion pass; CDN-blocked pass (nothing invisible forever).
- [ ] **Live visual confirmation is yours** — no Chromium in this sandbox.

---

## 7 · Phase 6 — `/local/{slug}`

Same shape, after 5b passes. Route `local/{slug}` →
`apollo-loc/styles/base/single-local.php`, Blank Canvas Apollo.

Local-specific: Leaflet map from `_local_lat`/`_local_lng`; agenda rail
(6 cards + "ver todas" → full list); gallery lightbox across all five image slots;
Uber/99 deep links derived from coordinates; **`.map-note` deleted** (VIO-04).

Then Phase 6b, identical checklist.

---

## 8 · Risk register

| Risk | Where | Mitigation |
| --- | --- | --- |
| `playedOn` meta-`LIKE` on a serialised array | 3.2 | Decide the storage shape **before** writing the query. This is the one that will not be fixable later without a migration. |
| Two metabox systems for `local` | D4 / 2.6 | Decide first. `_dj_tracks` already showed what happens. |
| `apollo-core` version drift | 1.5 | Bump docblock and constant together. |
| Live folder deploys in ~30 s | all | Harness before save; one concern per save; never a half-finished file. |
| Mockup drift while phases run | 4 | Mockups are annotated and backed up (`.pre-annotate.bak`). Re-diff before Phase 4. |

---

## 9 · Sequencing — the one rule

**Nothing in Phase 4+ starts before G2 passes.** A template written against a key
with no writer looks finished and renders blank, and the blank is discovered on a
live page rather than in a gate. That is the failure mode this whole plan exists
to avoid — and it is exactly what `/casa`'s dead event card was.

**Suggested first pass:** answer D1–D5, then run Phases 1–2 as a single strict
batch and re-measure coverage. If it does not read 100 % / 100 %, Phase 3 does
not start.
