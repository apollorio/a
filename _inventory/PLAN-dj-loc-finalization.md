# PLAN — dj + loc finalization: add/edit surface → approved single pages

**Goal:** make the frontend *add* and *edit* surfaces for `dj` and `loc` premium
and complete, so both single pages render as their approved mockups.

> Audit 2026-08-17 against the real mockups at
> `D:\dev\_dev web\screen\single cpt\{dj,location}\*-single-page.html`
> (now connected) and the live plugin code. **Plan only — nothing executed.**

---

## 0 · Four findings that block "finalize", in priority order

### 0.1 · There is no *add* path. Only edit. 🔴

`FrontendEditor::render_form()` calls `get_post( $post_id )` and returns early
when there is no post. The route is `/editar/{cpt}/{post_id}/`. Every field
definition is registered under `apollo_editor_config_dj` with
`page_title = "Editar DJ"`.

**A DJ cannot create their profile from the front end. Neither can a venue.**
The brief says "add and edit"; only edit exists. Everything downstream assumes
a post already exists, created in wp-admin by staff.

### 0.2 · The loc mockup is annotated against meta keys that do not exist 🔴

```
@field: meta._ap_address        → NOT REGISTERED
@field: meta._ap_geo            → NOT REGISTERED
@field: meta._ap_marquee_tags   → NOT REGISTERED
@field: meta._ap_venue_name     → NOT REGISTERED
```

Grep of `MetaRegistry.php` for all four: **zero hits.** The live keys are
`_local_address`, `_local_lat`/`_local_lng`, `_local_name`.

So the loc mockup cannot be wired as annotated — someone authored it against a
different (or intended) key scheme. **This must be resolved before any generator
slices it**, or the cells bind to nothing.

`_ap_venue_name` additionally uses **`venue`**, which `15-conventions`
`namingRules.FORBIDDEN_TERMS` bans outright (venue → `loc`).

### 0.3 · Both mockups still contain the philosophy violations 🟠

Measured in the source files:

| Term | dj mockup | loc mockup |
|---|---|---|
| `Seguidores` | 2 | 0 |
| `Seguir` | 6 | 6 |
| `follow` | 29 | 20 |
| `heart` | 7 | 7 |
| `venue` | 7 | 12 |

These are exactly VIO-01…VIO-04 recorded in `21-mockup-field-contract.json` —
still present, unfixed, in the approved artefacts. `01-philosophy` forbids
follow buttons and follower counts **outright**: `PARTY_MODEL`,
`NO_SELECTIVE_FOLLOW`, `NO_EGO_COUNTERS`.

> **Fix the mockups before generating from them.** A generator that slices these
> bakes a follow button into production, and the fix is copy + icons only — the
> `fav` storage already exists.

### 0.4 · The edit form cannot fill what the page renders 🟠

| | dj | loc |
|---|---|---|
| Fields in the **frontend form** | **14** | **16** |
| Fields in the **admin Lux panel** | **35** | **21** |
| Missing from the frontend | **21** | **8** |

The dj gaps include **every social and platform link**:
`_dj_instagram · _dj_facebook · _dj_twitter · _dj_tiktok · _dj_youtube ·
_dj_soundcloud · _dj_spotify · _dj_bandcamp · _dj_beatport · _dj_mixcloud ·
_dj_resident_advisor · _dj_website`

plus `_dj_banner · _dj_image · _dj_gallery · _dj_home_city · _dj_eyebrow ·
_dj_tracks · _dj_booking_status · _dj_footer_image · _dj_media_kit_stats ·
_dj_name_lines`.

**The dj mockup explicitly renders `_dj_soundcloud`, `_dj_spotify` and
`_dj_bandcamp` — three fields a DJ editing their own page cannot touch.** They
can only be set by an administrator in wp-admin.

loc gaps: `_local_amenities · _local_description · _local_facebook ·
_local_founded_year · _local_hours · _local_rooms · _local_tagline ·
_local_whatsapp`.

Reverse gap: the frontend owns `_local_image_1…5`, `_local_types` and
`_local_areas`, which the **admin panel lacks** — so neither surface is a
superset. They diverge in both directions, which is the two-declarations defect
recorded in `PLAN-cpt-and-admin-panels.md §3.2`.

---

## 1 · What the mockups actually demand

| | dj | loc |
|---|---|---|
| Sections | hero · stmt · playedOn · numbers · playedWith · sound · kit · about · footer | hero · stmt · facts · agenda · galeria · estrutura · comoChegar · depoimentos · footer |
| Cell architecture today | **17 cells, live** (`APOLLO_DJ_SINGLE_PARTS`) | **none — monolith** `styles/base/single-local.php` |
| Generator | `_sandbox/build-dj-cells.py` | none |
| Mockup in repo | yes (corrupted copy) | **no** |

**Good news on the corruption.** Both *source* mockups are clean UTF-8 —
`0` mojibake lines each. The damage recorded in
`apollo-djs.json $mockup_corrupted_generator_disarmed_2026_08_17` happened
**during the copy into `_sandbox/`**, not at source.

> **That changes the repair: re-copy from source. No byte-surgery needed.**
> `--repair-mockup` stays as a safety net but is not the path.

---

## 2 · The plan

### Phase D0 — unblock the artefacts *(no code, do first)*

- [ ] **D0-1** Fix VIO-01…VIO-04 **in the source mockups**: remove the
      "4.1k · Seguidores" pill, rename `#dockFollow` → favoritar with
      `ri-shining-2-line`, rewrite "Siga o local…" copy, delete the `.map-note`
      lat/lng debug print. Copy and icons only — `apollo_favorites` already exists.
- [ ] **D0-2** Resolve the `_ap_*` prefix question (**decision 1**). Either
      re-annotate the loc mockup to `_local_*`, or register the `_ap_*` keys as
      a deliberate rename with a migration. **Do not slice until decided.**
- [ ] **D0-3** Replace `venue` with `loc` throughout both mockups' annotations
      and identifiers (display copy in Portuguese may keep "local").
- [ ] **D0-4** Re-copy the clean dj mockup into `apollo-djs/_sandbox/`,
      overwriting the corrupted one. Verify `0` mojibake, then re-run the
      generator and review the diff.

### Phase D1 — the missing *add* path

The cheapest correct shape reuses what exists rather than building a second form.

- [ ] **D1-1** `FrontendEditor::render_form()` accepts `post_id = 0` when the
      caller passes an explicit `create` flag — the same narrow, explicit
      exception the card contract uses for placeholders.
- [ ] **D1-2** Route `/novo/{cpt}/` alongside `/editar/{cpt}/{id}/`.
- [ ] **D1-3** Create path mirrors the proven apollo-adverts shape:
      `post_author = get_current_user_id()`,
      `post_status = current_user_can('publish_posts') ? 'publish' : 'pending'`.
- [ ] **D1-4** On first save, redirect to `/editar/{cpt}/{new_id}/` so the user
      lands in the full editor with the hero and image fields available.
- [ ] **D1-5** One `dj` per user (`_dj_user_id`) unless staff. A roster with
      duplicate profiles for one person is worse than no self-service.

### Phase D2 — close the field gap *(the premium part)*

**Do not hand-write 21 more field definitions.** That is what produced two
diverging declarations in the first place.

- [ ] **D2-1** Derive the frontend field list **from the panel schema**
      (`apollo_panel_registry()`), per `STRATEGY-integration.md §3`. One
      declaration, two consumers.
- [ ] **D2-2** Central type map — `toggle`↔`checkbox`, `media_single`↔`image`,
      `taxonomy`↔`terms` — declared once, not per plugin.
- [ ] **D2-3** `'contexts' => ['admin','frontend']` per field, so `_dj_user_id`
      stays admin-only and `_dj_verified` stays staff-only without a second file.
- [ ] **D2-4** Add the panel's missing keys (`_local_image_1…5`, `_local_types`,
      `_local_areas`) so the panel is a true superset before deriving from it.
- [ ] **D2-5** Retire `apollo-djs/includes/frontend-fields.php` and
      `apollo-loc/includes/frontend-fields.php` **only after** the derived list
      is proven field-for-field identical.

> **Why derivation and not addition.** Adding 21 definitions by hand makes the
> drift worse: two files, 35 and 35, that must now be kept in sync forever. One
> schema with two renderers cannot drift from itself.

### Phase D3 — loc gets the cell treatment dj already has

`loc` is where `dj` was before its refactor: a monolith with context inline.

- [ ] **D3-1** Extract `apollo_loc_single_context( int $id ): array` out of
      `styles/base/single-local.php` — cut and paste; the test is that rendered
      output does not change.
- [ ] **D3-2** `apollo_loc_render_single()` + `apollo_loc_can_view()`.
      **The dormant `loc` surface lights up the moment these exist** — the
      registration already points at them.
- [ ] **D3-3** Copy the clean loc mockup into `apollo-loc/_sandbox/`.
- [ ] **D3-4** `build-loc-cells.py`, modelled on the dj generator **including**
      its integrity guard, `--repair-mockup` and `check_ranges()`.
- [ ] **D3-5** Nine cells: hero, stmt, facts, agenda, galeria, estrutura,
      comoChegar, depoimentos, footer.

### Phase D4 — premium UX on both forms

Only after the fields exist. Ordered by how much each is felt.

- [ ] **D4-1** Live preview — the edit form renders the actual single-page cell
      beside the field, so "premium" is verifiable by the person editing.
- [ ] **D4-2** Autosave draft + dirty-state guard. The event form already has
      this shape; reuse rather than reinvent.
- [ ] **D4-3** Per-section completeness meter driven by the schema, not a
      hardcoded list.
- [ ] **D4-4** Image fields: drag-drop, crop-to-aspect, instant thumbnail. The
      hero and footer images are full-bleed in both mockups; a wrong aspect is
      the most visible possible defect.
- [ ] **D4-5** Inline validation from the schema's `sanitize_callback`, so the
      form rejects what the saver would reject.

---

## 3 · Performance method — where the cost actually is

| Concern | Decision | Why |
|---|---|---|
| Context building | One `apollo_{cpt}_single_context()` per request, statically cached | The dj version already does this. The loc monolith re-reads meta per section — nine sections, nine passes |
| Related queries | One `WP_Query` with `post__in`, never a loop of `get_post()` | The `apollo_get_latest_dj_tracks()` N+1 is the cautionary tale |
| Images | `wp_get_attachment_image_src` at the size actually rendered; never full-size in a card | Full-bleed hero at full size is the single biggest byte cost on both pages |
| Cells | `include`, not `file_get_contents` + `eval` | Opcache caches an include; it cannot cache a string |
| Styles | Through the card/panel style ledger, printed once | The `.accom-*` defect: a stylesheet a template stopped linking |
| Frontend editor | Fields derived once per request, not per field render | 35 fields × per-field `apply_filters` is 35 filter chains |

---

## 4 · Sequencing

| # | Phase | Risk | Note |
|---|---|---|---|
| 1 | D0 artefacts | **none — no code** | Blocks everything; mockups are not yet safe to slice |
| 2 | D3-1/D3-2 loc context + renderer | **medium** — `/local/{slug}` is live | Same cut-and-paste as dj. Output must not change |
| 3 | D1 add path | medium — new write path | Reuses the editor; apollo-adverts shape is proven |
| 4 | D2 derivation | medium — touches both forms | Prove identical before retiring the old files |
| 5 | D3-3…D3-5 loc cells | medium | Generator guards ported from day one |
| 6 | D4 premium UX | low, incremental | Each item ships alone |

**D0 first, and it is not optional.** Slicing a mockup that contains a follow
button and unregistered meta keys would bake both into production, and the cells
are generated — the mistake would be reproduced on every regeneration.

---

## 5 · Decisions needed

1. **`_ap_*` vs `_local_*`** — is the loc mockup's prefix a mistake to correct,
   or an intended rename to migrate to? *(Recommend: correct the mockup. A
   rename costs a migration across 20 registered keys for no functional gain.)*
2. **Who may create** — any member, or only users with a linked `_dj_user_id`?
   *(Recommend: any member may create one `dj`; staff may create any number.)*
3. **Moderation** — do self-created `dj`/`loc` publish immediately or land in
   `pending`? *(Recommend: `pending`, matching apollo-adverts.)*
4. **`_dj_verified` and `_dj_booking_status`** — staff-only, or self-service?
   *(Recommend: `_dj_verified` staff-only via `'cap' => 'manage_options'`;
   booking status self-service.)*
5. **loc ownership** — can a venue owner claim and edit their `loc`, and by what
   meta? There is no `_local_user_id` equivalent to `_dj_user_id` today.
