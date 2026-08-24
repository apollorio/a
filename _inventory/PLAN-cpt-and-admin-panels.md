# CPT PLAN — registration truth, admin panels, and one wire for all of it

> Deep audit 2026-08-17. Every claim below was read out of PHP, not out of the
> registry. Where the registry disagrees with code, **the code is reported and
> the registry is wrong.** No code was changed while producing this document.
>
> Companions: `CPT-CONTENT-MATRIX.md` (per-page field map for event/loc/dj),
> `PLAN-outnow-and-hostel.md`, `registry/PLUGIN-DEPLOY-MAP.md`.

---

# 0 · STOP — four things are broken in production right now

These are not plan items. They are live defects found while mapping, ordered by
how fast they should be fixed.

### 0.1 · The DJ and Local edit screens fatal on PHP 8 🔴

```php
// apollo-lux-panels/includes/Panel.php:323
$cols = $f['opts']['cols'];        // unconditional
$grid = 'cols-' . min( count( $cols ), 5 );
```

Two fields declare `type => 'repeater'` **without a `cols` key**:

| Field | File |
|---|---|
| `_dj_name_lines` | `apollo-lux-panels/includes/DjPanel.php:172-177` (`opts` has `max`, `fields`) |
| `_local_rooms` | `apollo-lux-panels/includes/LocPanel.php:50-56` (`opts` has `fields`) |

`$cols` resolves to `null`, then `count(null)` is a **TypeError** in PHP 8 — and
the plugin header declares `Requires PHP: 8.1`. **Editing a DJ or a Local in
wp-admin throws a fatal.** Both were added in the 2026-08-11 "Phase 2.5" pass.

Fix is one line each: give them `cols`, or make `render_repeater()` tolerate its
absence (`$f['opts']['cols'] ?? []`). Do both — the guard *and* the data.

### 0.2 · Debug beacons are shipping to production 🔴

Not four sites — **ten files**, in two flavours:

**Client-side `fetch()` to `http://127.0.0.1:7514/ingest/…`**, which runs in
every visitor's or editor's browser and is mixed-content on an HTTPS site:

- `apollo-events/src/Admin/Metabox.php:417`
- `apollo-events/assets/js/apollo-events-create-wire.js:39`
- `apollo-events/assets/js/apollo-events-create-form.js:1491`
- `apollo-loc/src/Admin/Metabox/GalleryMetabox.php:97`
- `apollo-templates/assets/js/frontend-editor.js:39`
- `apollo-events/src/Plugin.php:400` (port 7623)

**Server-side writes to a path from a different project on the developer's
machine** — `D:/dev/_livro.rvalle.com.br/…`:

- `apollo-lux-panels/includes/Panel.php:559-560`
- `apollo-events/src/API/EventsController.php:552-553, 2183`
- `apollo-events/src/Admin/Metabox.php:776-777`
- `apollo-events/includes/functions.php:295-296, 400`
- `apollo-events/src/Import/UrlImportController.php:317`

This class of leak was cleaned once before (`CPT-CONTENT-MATRIX.md:66`, and
`registry/09-plugins/apollo-events.json:3617` records removing a beacon from
`eveCardHTML()`). It came back. **Delete all ten, then add a harness assertion**
so it cannot come back a third time.

### 0.3 · The hostel switch is unprotected in wp-admin 🟠

`APOLLO_ADVERTS_ADMIN_ONLY_META` claims to be *"enforced at the write boundary,
not just hidden in the UI."* That is true for REST — `includes/cpt.php:194`
branches the `auth_callback` to `manage_options`. **It is not true for the
metabox.** `apollo-adverts/src/Plugin.php:787-797` guards on nonce +
`current_user_can('edit_post')` only, then writes `_classified_hostel` (`:858`)
and `_classified_hostel_url` (`:864`).

**Anyone who can edit a classified can flip the switch that bypasses the
marketplace auth gate.** Add `current_user_can('manage_options')` around the
write, and stop rendering those rows for non-admins.

Related: `_classified_hostel_id` — the relation added today that supersedes the
boolean — **has no admin input anywhere**, so the only editable switch is the
deprecated one.

### 0.4 · The Lux event panel is decorative 🟠

`event` has **two live metabox systems writing the same keys**:

- `apollo-lux-panels/includes/EventPanel.php` (registered at `plugins_loaded:10`)
- `apollo-events/src/Admin/Metabox.php` — six boxes, **unguarded**, instantiated
  at `apollo-events/src/Plugin.php:640` (`plugins_loaded:15`)

Both emit `name="_event_ticket_price"` and ~18 other shared names inside the
same `<form>`. **PHP keeps the last occurrence in DOM order**, and apollo-events
renders after Lux. So for every shared key, what the editor types into the
beautiful Lux panel is discarded and the legacy plain box wins.
`reorder_metabox()` (`apollo-lux-panels.php:125`) moves Lux to the visual top,
which makes it *look* authoritative.

Also: `apollo-events/src/Admin/Metabox.php:701` writes `_event_dj_ids`
**unconditionally**, outside its own `isset` guard — so saving the event screen
without the JSON field present blanks the line-up.

---

# 1 · Ground truth — 17 CPTs, not 16

| # | slug | declared in core config | owner registration | live args come from |
|---|---|---|---|---|
| 1 | `event` | yes | apollo-events `Registry.php:69` | **core** |
| 2 | `dj` | yes | apollo-djs `Registry.php:56` | **core** |
| 3 | `track` | yes | apollo-djs `Registry.php:122` | **core** |
| 4 | `hostel` | yes | **none** | core (fallback only) |
| 5 | `local` | yes | apollo-loc `CPT/CPTRegistrar.php:29` | **core** |
| 6 | `classified` | yes | apollo-adverts `includes/cpt.php:42` | **core** |
| 7 | `supplier` | yes | **none** — plugin not on disk | core (fallback only) |
| 8 | `doc` | yes | apollo-docs `Core/Registrar.php:31` | **core** |
| 9 | `email_aprio` | yes | apollo-email `Core/CPT.php:32` | **core** |
| 10 | `hub` | yes | apollo-hub `Registry.php:58` | **core** |
| 11 | `apollo_sheet` | yes | apollo-sheets `Plugin.php:113` | **core** |
| 12 | `appointment` | yes | apollo-scheduler `Registry.php:70` | **core** |
| 13 | `service` | yes | apollo-scheduler `Registry.php:70` | **core** |
| 14 | `resource` | yes | apollo-scheduler `Registry.php:70` | **core** |
| 15 | `apollo_agent_log` | yes | **none** | core (fallback only) |
| 16 | `journal_news` | **no** | apollo-journal `Plugin.php:117` | owner (`init:4`) |
| 17 | `journal_nota` | **no** | apollo-journal `Plugin.php:168` | owner (`init:4`) |

## 1.1 · The structural fact everything else follows from

> **apollo-core always wins the `init:5` race. Every owner registration for a
> config-declared slug is unreachable.**

Core adds its callback during `apollo_core_bootstrap()` on `plugins_loaded:1`;
every owner plugin boots at `plugins_loaded:15`. Same-priority `init` callbacks
run in registration order, so core's runs first, registers the CPT, and every
owner's `post_type_exists()` guard then returns early.

`CPTRegistry::is_owner_active()` (`:92`) — the function that would implement the
"fallback only when the owner is inactive" philosophy in its own docblock — is
**defined and never called**. The only gate is `post_type_exists()`.

This is not academic. `register_cpt()` (`:143`) takes only `labels`, `public`,
`rewrite`, `has_archive`, `menu_icon`, `supports`, `rest_base` from config and
**hardcodes the rest**. It never passes `taxonomies`, never sets `map_meta_cap`,
never sets `rest_namespace`, never sets `exclude_from_search`. So:

| CPT | What the owner intended | What is actually live |
|---|---|---|
| **`apollo_sheet`** | `show_ui:false`, `show_in_rest:false`, no rest_base — "custom REST controller instead" | `show_ui:true`, `show_in_menu:true`, **exposed at `/wp-json/wp/v2/sheets`** with the stock controller |
| **`track`** | `taxonomies:[sound]` | **`sound` is not attached.** `config/taxonomies.php:24` sets `$sound_cpts = ['event','dj']` |
| **`classified`** | supports includes `excerpt` and `comments` | `comments` re-added by a patch at `init:20`; **`excerpt` silently absent** |
| **`doc`** | `revisions`, `rest_namespace:'apollo/v1'` | no revisions; served at `wp/v2/docs` |
| **`email_aprio`** | `rest_namespace:'apollo/v1'` | served at `wp/v2/email-templates` |
| **`hub`** | `exclude_from_search:true` | searchable |
| all owners | `map_meta_cap:true` | never set |

**`track` losing `sound` contradicts the rationale written into `config/cpts.php`
and into the registry entry added today.** It is a real regression in
today's work: the owner registration passes the taxonomy, and the owner
registration never runs.

## 1.2 · Duplicate registrations inside one plugin

`apollo-loc` registers `local` **twice**:

- `src/CPT/CPTRegistrar.php:29` — live, wired at `src/Plugin.php:31`,
  `has_archive:'locais'`
- `src/Registry.php:56` — **dead**, class never instantiated,
  `has_archive:'local'`, and its own docblock claims `rewrite="gps"`,
  `archive="gps"`, `rest_base="locals"` — three values that appear nowhere in its
  body

The same file also duplicates the taxonomy registrations *and* a full metabox
(`:192`). It is a landmine: harmless only because nothing constructs it.

---

# 2 · Registry corrections required

| Chapter | Defect |
|---|---|
| `16-summary.json` | `total_cpts_unique: 16` → **17**. `all_cpt_slugs` is missing `apollo_agent_log` |
| `09-plugins/apollo-djs.json:29` | `"cpts": ["dj"]` — missing **`track`**, which this plugin owns and registers |
| `09-plugins/apollo-adverts.json:31` | `"cpts": ["classified"]` — missing **`hostel`**, which config assigns to it |
| `09-plugins/apollo-membership.json:37` | `"cpts": []` — config names it owner of `apollo_agent_log` |
| `09-plugins/apollo-core.json:67-79` | `MASTER_REGISTRY.cpts` lists **`loc`**, which is not a post type (the slug is `local`). Omits track, hostel, local, apollo_sheet, appointment, service, resource, apollo_agent_log. Wrongly includes journal_news/journal_nota, which core does not declare. `taxonomies` has the same defect (`loc_type`/`loc_area` vs real `local_type`/`local_area`) |
| every per-plugin chapter | nested `registered_locally: false` / `via_constant: true` flags are a scanner artifact — every one of these plugins registers locally |
| `config/cpts.php` docblock | says "All 9 Custom Post Types"; there are 15 keys |

**One-word bug found via the same `loc`/`local` confusion:**
`apollo-coauthor/includes/constants.php:25` lists `loc` in
`APOLLO_COAUTHOR_POST_TYPES`. There is no `loc` post type, so **co-authors are
silently not available on venues**, and
`register_taxonomy_for_object_type('coauthor','loc')` targets nothing.

---

# 3 · Admin editing surface, per CPT

| CPT | Admin surface today | Verdict |
|---|---|---|
| `event` | Lux EventPanel **+** apollo-events 6-box legacy **+** SEO **+** coauthor | **conflict — legacy wins** |
| `dj` | Lux DjPanel + SEO + coauthor (apollo-djs legacy guarded off) | **fatals** (§0.1) |
| `local` | Lux LocPanel **+** 4 live apollo-loc cells + SEO | **fatals** (§0.1), overlapping |
| `classified` | apollo-adverts only + SEO + coauthor | works; **hostel keys unguarded** |
| `hub` | apollo-hub only + SEO | works |
| `doc` | apollo-docs only + coauthor | works |
| `email_aprio` | apollo-email only | works |
| `journal_news` / `journal_nota` | NREP box (`_nrep_code`) only | thin |
| **`track`** | **none** — stock editor only | **12 meta keys with no input** |
| **`hostel`** | **none** — stock editor only | **5 meta keys with no input** |
| `supplier` | none (plugin absent) | n/a |
| `apollo_sheet` | none | stock editor |
| `appointment` / `service` / `resource` | none | stock editor |

## 3.1 · The declarative system that already exists

`apollo-lux-panels/includes/Panel.php` is genuinely good and is the right
foundation. Abstract `Panel` → `post_type()` + `schema()` returning
`{title, subtitle, tabs => {tab => {label, icon, cards => [{label, icon, fields}]}}}`,
one metabox per CPT, dispatched through a single `switch ($type)` at `:134`.

**15 field types today:** `textarea`, `wysiwyg`, `select`, `toggle`, `taxonomy`,
`media_single`, `media_gallery`, `repeater`, `repeater_card`, `hours`, `lineup`,
`map`, `user_multiselect`, `color`, and `default` covering
`text|url|email|number|date|time`.

**Guards:** nonce → autosave → revision → `current_user_can('edit_post')` →
post-type match (`:473-491`).

### Four gaps in it

1. **`sanitize_callback` is honoured for exactly one type.** `save_repeater_card()`
   reads it (`:700-703`); nothing else does. A `sanitize_callback` on a `text`,
   `select` or `repeater` field is **silently ignored** — a trap, because
   declaring one reads as protection.
2. **`select` is not validated on save.** `save_field()` runs
   `sanitize_text_field` and never re-checks the value against the field's own
   `opts` whitelist. Any value can be posted.
3. **No concept of a restricted key.** One `edit_post` check, then everything in
   the schema is written. There is no way to declare `_classified_hostel_id` as
   admin-only *within* a panel — which is exactly what §0.3 needs.
4. **Three malformed field declarations** ship today: `_dj_name_lines` and
   `_local_rooms` (fatal, §0.1); `_dj_media_kit_stats` uses `'key' =>` where
   `render_repeater_card_row()` reads `$sf['name']` (`Panel.php:398`), so every
   input is named `[][]` and **nothing persists**; `_dj_booking_status` passes
   `opts => ['choices' => …]` where the renderer iterates `opts` as a flat
   `value => label` map, emitting one option named `choices`.

**Nothing validates a schema.** All four would have been caught by a check that
runs at registration.

## 3.2 · Admin and frontend declare the same fields twice, by hand

`apollo-templates/src/FrontendEditor.php` is a parallel, incompatible system:
fields come from `apply_filters("apollo_frontend_fields_{$post_type}", [])`, and
`sanitize_field()` (`:900`) is type-driven with **no `sanitize_callback` support
at all** and a different type vocabulary.

Only `dj` and `local` have frontend definitions. Both overlap their Lux panel
almost entirely — and diverge in both directions:

- `_dj_verified` is `checkbox` on the front end, `toggle` in admin
- `_dj_user_id` is `hidden` vs `number`
- frontend `local` owns `_local_image_1…5` and the two taxonomies **that
  LocPanel lacks**
- LocPanel owns `_local_hours`, `_local_amenities`, `_local_description`,
  `_local_tagline`, `_local_founded_year`, `_local_rooms`, `_local_whatsapp`,
  `_local_facebook` **that the frontend lacks**

Two declarations of one truth, drifting in opposite directions. This is the
`_dj_tracks` duplicate-metabox incident again, at schema level.

---

# 4 · The plan

## Phase 0 — stop the bleeding *(no design work, do it first)*

- [ ] **0-1** Give `_dj_name_lines` and `_local_rooms` a `cols` array **and**
      make `render_repeater()` default it (`?? []`). Fixes the fatal twice over.
- [ ] **0-2** Fix `_dj_media_kit_stats` (`key` → `name`) and `_dj_booking_status`
      (flatten `opts`).
- [ ] **0-3** Delete all ten debug beacons (§0.2). Harness assertion: no
      `127.0.0.1`, no `_livro.rvalle`, no `/ingest/` in any shipped file.
- [ ] **0-4** Gate `_classified_hostel*` writes and rendering behind
      `manage_options` in `apollo-adverts/src/Plugin.php`.
- [ ] **0-5** Fix `apollo-events/src/Admin/Metabox.php:701` — move the
      `_event_dj_ids` write inside its `isset` guard.
- [ ] **0-6** `apollo-coauthor/includes/constants.php:25` — `loc` → `local`.

## Phase 1 — make the registration layer honest

The current design says "owner registers, core falls back" and does the
opposite. Two ways out; **1-a is recommended**.

| | Approach | Consequence |
|---|---|---|
| **1-a** | **Config becomes the single source.** Extend `register_cpt()` to pass `taxonomies`, `map_meta_cap`, `rest_namespace`, `exclude_from_search`, `show_ui`, `show_in_rest`. Move every owner's divergent arg into `config/cpts.php`. Delete the owner registrations. | One place to read, one place to change. Matches `forbid_direct_meta_cpt`. Owner plugins keep their taxonomies/meta/REST. |
| 1-b | Make the fallback real: implement `is_owner_active()` and register at `init:9` so owners win. | Preserves per-plugin autonomy but keeps two sources for every CPT. |

- [ ] **1-1** Adopt 1-a. Extend `register_cpt()` with the missing args.
- [ ] **1-2** Reconcile the seven divergences in §1.1 into config, **deciding
      each explicitly**. `apollo_sheet` is the sharp one: it is currently public
      in REST against its owner's stated intent — decide whether that is a leak
      to close or a fact to accept.
- [ ] **1-3** Add `track` to `$sound_cpts` in `config/taxonomies.php`. *(Closes
      the regression introduced today.)*
- [ ] **1-4** Add `journal_news` / `journal_nota` to config, or record
      deliberately that apollo-journal owns its own registration.
- [ ] **1-5** Delete `apollo-loc/src/Registry.php` (dead duplicate CPT +
      taxonomy + metabox) and `apollo-loc/src/Admin/Metabox.php`.
- [ ] **1-6** Harness: every slug in `config/cpts.php` is registered exactly
      once; no owner registration contradicts config.

## Phase 2 — one panel system, every CPT

Extend `apollo-lux-panels` from three CPTs to all of them. It is already the
declared metabox owner and already has the right shape.

- [ ] **2-1** **Schema validator**, run at registration, failing loud in
      `WP_DEBUG`: every field has `key` + `type`; `type` is known; `repeater` has
      `cols`; `repeater_card` sub-fields use `name`; `select` `opts` is a flat
      map. This alone would have caught all four §3.1 defects.
- [ ] **2-2** Honour `sanitize_callback` for **every** type, not just
      `repeater_card`.
- [ ] **2-3** Validate `select` against its own `opts` on save.
- [ ] **2-4** Add `'cap' => 'manage_options'` per field. Render and save both
      respect it. This is what makes `_classified_hostel_id` declarable.
- [ ] **2-5** `apollo_panel_register( $post_type, $schema )` — a registration
      function so a plugin contributes a panel without subclassing, mirroring
      `apollo_surface_register()` and `apollo_card_register()`.
- [ ] **2-6** Migrate `classified`, `hub`, `doc`, `email_aprio` onto it; delete
      their bespoke metaboxes.
- [ ] **2-7** **Retire `apollo-events/src/Admin/Metabox.php`** and move any field
      it owns that EventPanel lacks (`_event_is_gone`, `_event_highlighted`,
      `_event_lista_cta_label`, the `event_category|type|tag` taxonomies) into
      EventPanel. *Closes §0.4.*
- [ ] **2-8** Fold the four live `apollo-loc` metabox cells into LocPanel
      (`_local_image_1…5`, `local_type`, `local_area` are the gaps).
- [ ] **2-9** Build `track` and `hostel` panels — currently zero inputs for 17
      meta keys.

## Phase 3 — one schema, admin *and* frontend

- [ ] **3-1** A panel schema becomes the single declaration. `FrontendEditor`
      **derives** its field list from it instead of a parallel filter.
- [ ] **3-2** Map panel types → frontend types once, centrally (`toggle`↔`checkbox`,
      `media_single`↔`image`, …), rather than per plugin.
- [ ] **3-3** `'contexts' => ['admin','frontend']` per field, so `_dj_user_id`
      can be admin-only and `_dj_verified` staff-only without a second file.
- [ ] **3-4** Retire `apollo-djs/includes/frontend-fields.php` and
      `apollo-loc/includes/frontend-fields.php` once derivation is proven
      field-for-field identical.

## Phase 4 — wire panels to single pages and REST

- [ ] **4-1** Every panel-declared key gets `show_in_rest` from the same schema —
      no separate MetaRegistry edit for new fields.
- [ ] **4-2** Panel schema declares which render context consumes each key, so
      `CPT-CONTENT-MATRIX.md` becomes generated rather than hand-maintained.
- [ ] **4-3** `apollo_surface_register()` for every CPT that has a single page.
- [ ] **4-4** `apollo_card_register()` for every CPT that appears in a list.

---

# 5 · Readiness — the honest answer

**No, this is not "ok and ready" yet.** Two screens fatal, one privilege check is
missing, ten debug beacons ship to production, and one panel is decorative. But
the distance is short and the foundation is right.

| CPT | Registered | Meta declared | Admin inputs | Single page | REST | Ready? |
|---|---|---|---|---|---|---|
| `event` | ✅ | ✅ | ⚠️ two systems, legacy wins | ✅ | ✅ | **no — §0.4** |
| `dj` | ✅ | ✅ | 🔴 **fatal** | ✅ | ✅ | **no — §0.1** |
| `local` | ✅ | ✅ | 🔴 **fatal** + split | ✅ | ✅ | **no — §0.1** |
| `classified` | ✅ | ✅ | ⚠️ hostel unguarded | ⚠️ single unreachable | ✅ | **no — §0.3** |
| `track` | ✅ | ✅ | ❌ none | ❌ | ⚠️ stock | no |
| `hostel` | ⚠️ fallback only | ✅ | ❌ none | ❌ | ⚠️ stock | no |
| `hub` | ✅ | ✅ | ✅ | ✅ | ✅ | yes |
| `doc` | ✅ | ✅ | ✅ | n/a | ⚠️ wrong namespace | close |
| `email_aprio` | ✅ | ✅ | ✅ | n/a | ⚠️ wrong namespace | close |
| `journal_news/nota` | ⚠️ outside config | partial | ⚠️ thin | ✅ | ✅ | close |
| `apollo_sheet` | ✅ | ✅ | ❌ none | n/a | 🔴 **posture inverted** | no |
| `appointment/service/resource` | ✅ | partial | ❌ none | ❌ | ⚠️ stock | no |
| `supplier` | ⚠️ fallback, no plugin | — | ❌ | ❌ | ⚠️ stock | retire or build |
| `apollo_agent_log` | ⚠️ fallback, no owner | — | ❌ | n/a | — | retire or build |

**When it will be true:** after Phase 0 nothing is broken; after Phase 1 there is
one place to read a CPT's definition; after Phase 2 every CPT has a complete,
validated editing surface; after Phase 3 there is one schema instead of two.

Phase 0 is hours. Phases 1–2 are the real work and are where "plug and play"
actually arrives — because at that point adding a CPT is: one config entry, one
panel schema, and the surface/card/REST wiring comes free.

---

# 6 · Decisions needed before Phase 1

1. **`apollo_sheet` REST posture** — its owner wants it private with a custom
   controller; core currently publishes it at `wp/v2/sheets`. Close the exposure,
   or accept it?
2. **Registration model** — 1-a (config is the single source, delete owner
   registrations) or 1-b (make the fallback real)?
3. **`journal_news` / `journal_nota`** — bring into `config/cpts.php`, or record
   apollo-journal as a deliberate exception?
4. **`supplier` and `apollo_agent_log`** — both are registered by core with no
   owner and no admin UI. Build them, or remove the config entries?
5. **`classified` `excerpt`** — the owner asked for it, core drops it. Restore?
